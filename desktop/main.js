const { app, BrowserWindow, dialog, shell } = require('electron');
const { spawn } = require('child_process');
const fs = require('fs');
const http = require('http');
const net = require('net');
const path = require('path');

const HOST = '127.0.0.1';
const PORT_MIN = 8765;
const PORT_MAX = 8775;
const APP_NAME = 'Your Small Desk';
const REQUIRED_PHP_EXTENSIONS = ['pdo_sqlite', 'sqlite3', 'fileinfo', 'mbstring'];

let phpProcess = null;
let mainWindow = null;
let serverPort = null;

function getProjectRoot() {
  if (app.isPackaged) {
    return path.join(process.resourcesPath, 'jjh-space');
  }

  return path.resolve(__dirname, '..');
}

function getPortablePhpPath() {
  if (app.isPackaged) {
    return path.join(process.resourcesPath, 'php', 'php.exe');
  }

  return path.join(__dirname, 'php', 'php.exe');
}

function candidatePhpPaths() {
  const candidates = [];
  const devPortable = path.join(__dirname, 'php', 'php.exe');
  const packagedPortable = path.join(process.resourcesPath || '', 'php', 'php.exe');
  const portable = getPortablePhpPath();

  if (fs.existsSync(devPortable)) {
    candidates.push(devPortable);
  }

  if (app.isPackaged && fs.existsSync(packagedPortable)) {
    candidates.push(packagedPortable);
  }

  if (fs.existsSync(portable)) {
    candidates.push(portable);
  }

  if (process.env.PHP_PATH) {
    candidates.push(process.env.PHP_PATH);
  }

  candidates.push('php');
  return [...new Set(candidates)];
}

function runPhpCommand(phpPath, args) {
  return new Promise((resolve) => {
    let stdout = '';
    let stderr = '';
    const child = spawn(phpPath, args, {
      windowsHide: true,
      stdio: ['ignore', 'pipe', 'pipe']
    });

    child.stdout.on('data', (data) => {
      stdout += String(data);
    });

    child.stderr.on('data', (data) => {
      stderr += String(data);
    });

    child.once('error', (error) => resolve({
      ok: false,
      stdout,
      stderr,
      error
    }));

    child.once('exit', (code) => resolve({
      ok: code === 0,
      stdout,
      stderr,
      error: null
    }));
  });
}

function canRunPhp(phpPath) {
  return new Promise((resolve) => {
    const child = spawn(phpPath, ['-v'], {
      windowsHide: true,
      stdio: ['ignore', 'ignore', 'ignore']
    });

    child.once('error', () => resolve(false));
    child.once('exit', (code) => resolve(code === 0));
  });
}

async function findPhp() {
  for (const phpPath of candidatePhpPaths()) {
    if (await canRunPhp(phpPath)) {
      return phpPath;
    }
  }

  return null;
}

async function findMissingPhpExtensions(phpPath) {
  const result = await runPhpCommand(phpPath, ['-m']);

  if (!result.ok) {
    return REQUIRED_PHP_EXTENSIONS;
  }

  const loaded = result.stdout
    .split(/\r?\n/)
    .map((line) => line.trim().toLowerCase())
    .filter(Boolean);

  return REQUIRED_PHP_EXTENSIONS.filter((extension) => !loaded.includes(extension));
}

function isPortFree(port) {
  return new Promise((resolve) => {
    const server = net.createServer();

    server.once('error', () => resolve(false));
    server.once('listening', () => {
      server.close(() => resolve(true));
    });
    server.listen(port, HOST);
  });
}

async function findFreePort() {
  for (let port = PORT_MIN; port <= PORT_MAX; port += 1) {
    if (await isPortFree(port)) {
      return port;
    }
  }

  return null;
}

function startPhpServer(phpPath, port, projectRoot) {
  const sqlitePath = process.env.SQLITE_PATH || (
    app.isPackaged
      ? path.join(app.getPath('userData'), 'data', 'jjh_space.sqlite')
      : 'data/jjh_space.sqlite'
  );

  const env = {
    ...process.env,
    APP_ENV: process.env.APP_ENV || 'desktop',
    DEBUG: process.env.DEBUG || 'false',
    APP_URL: `http://${HOST}:${port}`,
    DB_DRIVER: process.env.DB_DRIVER || 'sqlite',
    SQLITE_PATH: sqlitePath,
    DB_CHARSET: process.env.DB_CHARSET || 'utf8mb4',
    SESSION_SECURE: 'false'
  };

  phpProcess = spawn(phpPath, ['-S', `${HOST}:${port}`, '-t', projectRoot], {
    cwd: projectRoot,
    env,
    windowsHide: true,
    stdio: ['ignore', 'pipe', 'pipe']
  });

  phpProcess.stdout.on('data', (data) => {
    if (!app.isPackaged) {
      console.log(`[php] ${String(data).trim()}`);
    }
  });

  phpProcess.stderr.on('data', (data) => {
    if (!app.isPackaged) {
      console.error(`[php] ${String(data).trim()}`);
    }
  });

  phpProcess.once('exit', (code) => {
    phpProcess = null;
    if (!app.isQuitting && code !== 0) {
      dialog.showErrorBox(APP_NAME, 'El servidor PHP local se ha cerrado inesperadamente.');
      app.quit();
    }
  });
}

function waitForServer(port, attempts = 80, intervalMs = 250) {
  return new Promise((resolve) => {
    let attempt = 0;

    const check = () => {
      attempt += 1;
      const request = http.get(`http://${HOST}:${port}/`, (response) => {
        response.resume();
        resolve(true);
      });

      request.on('error', () => {
        if (attempt >= attempts) {
          resolve(false);
          return;
        }
        setTimeout(check, intervalMs);
      });

      request.setTimeout(1500, () => {
        request.destroy();
      });
    };

    check();
  });
}

function isAllowedLocalUrl(url) {
  try {
    const parsed = new URL(url);
    return parsed.protocol === 'http:' && parsed.hostname === HOST && Number(parsed.port) === serverPort;
  } catch (error) {
    return false;
  }
}

function openExternalIfSafe(url) {
  try {
    const parsed = new URL(url);
    if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
      shell.openExternal(url);
    }
  } catch (error) {
  }
}

function createWindow() {
  const iconPath = path.join(__dirname, 'resources', 'icon.ico');
  const windowOptions = {
    width: 1280,
    height: 800,
    minWidth: 1024,
    minHeight: 700,
    title: APP_NAME,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      enableRemoteModule: false,
      sandbox: true,
      preload: path.join(__dirname, 'preload.js')
    }
  };

  if (fs.existsSync(iconPath)) {
    windowOptions.icon = iconPath;
  }

  mainWindow = new BrowserWindow(windowOptions);

  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    if (isAllowedLocalUrl(url)) {
      mainWindow.loadURL(url);
      return { action: 'deny' };
    }

    openExternalIfSafe(url);
    return { action: 'deny' };
  });

  mainWindow.webContents.on('will-navigate', (event, url) => {
    if (!isAllowedLocalUrl(url)) {
      event.preventDefault();
      openExternalIfSafe(url);
    }
  });

  mainWindow.loadURL(`http://${HOST}:${serverPort}/`);
}

function stopPhpServer() {
  if (phpProcess) {
    phpProcess.kill();
    phpProcess = null;
  }
}

async function boot() {
  app.setName(APP_NAME);
  const projectRoot = getProjectRoot();
  const phpPath = await findPhp();

  if (!phpPath) {
    dialog.showErrorBox(
      'No se encontro PHP',
      'No se encontro PHP. Anade PHP portable en desktop/php/php.exe antes de compilar o instala PHP y anadelo al PATH.'
    );
    app.quit();
    return;
  }

  const missingExtensions = await findMissingPhpExtensions(phpPath);
  if (missingExtensions.length > 0) {
    dialog.showErrorBox(
      'PHP incompleto',
      `PHP se encontro, pero faltan extensiones necesarias: ${missingExtensions.join(', ')}. Revisa desktop/php/php.ini.`
    );
    app.quit();
    return;
  }

  serverPort = await findFreePort();
  if (!serverPort) {
    dialog.showErrorBox('Puerto ocupado', 'No hay puertos libres entre 8765 y 8775 para arrancar Your Small Desk.');
    app.quit();
    return;
  }

  startPhpServer(phpPath, serverPort, projectRoot);
  const ready = await waitForServer(serverPort);

  if (!ready) {
    stopPhpServer();
    dialog.showErrorBox(APP_NAME, 'No se pudo arrancar el servidor PHP local.');
    app.quit();
    return;
  }

  createWindow();
}

app.isQuitting = false;

app.whenReady().then(boot);

app.on('before-quit', () => {
  app.isQuitting = true;
  stopPhpServer();
});

app.on('window-all-closed', () => {
  app.quit();
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0 && serverPort) {
    createWindow();
  }
});
