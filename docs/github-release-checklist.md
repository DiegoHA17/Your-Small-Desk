# Checklist GitHub Release

## Antes de compilar

- Confirmar que README usa el nombre Your Small Desk.
- Confirmar que LICENSE usa Your Small Desk Community License.
- Confirmar que no hay claves ni datos reales en el repo.
- Confirmar que `.gitignore` excluye SQLite, PDFs, logs, logos reales, `.env`, `desktop/dist/` y `.exe`.
- Confirmar que el icono `desktop/resources/icon.ico` es propio o tiene permiso de uso.
- Colocar PHP portable completo en `desktop/php/`.
- Confirmar que existe `desktop/php/php.exe`.
- Confirmar que existe `desktop/php/php.ini`.
- Confirmar que existe `desktop/php/ext/`.
- Activar en `php.ini` las extensiones `pdo_sqlite`, `sqlite3`, `curl`, `openssl`, `mbstring`, `fileinfo`, `gd` y `zip`.
- Confirmar que existe `desktop/php/extras/ssl/cacert.pem`.
- Confirmar que `curl.cainfo` y `openssl.cafile` apuntan a `cacert.pem`.
- Ejecutar `desktop/php/php.exe -m` y comprobar que aparecen `pdo_sqlite`, `sqlite3`, `curl`, `openssl`, `fileinfo`, `mbstring`, `gd` y `zip`.
- Confirmar que `desktop/php/license.txt` existe para conservar la licencia de PHP portable.
- Ejecutar Composer para incluir mPDF y PHPMailer en el build.

```powershell
composer install --no-dev --optimize-autoloader
```

## Compilar instalador

Desde PowerShell:

```powershell
cd desktop
npm install
npm run dist
```

La salida esperada queda en:

```text
desktop/dist/
```

El instalador debe llamarse con el nombre Your Small Desk.

Comprobar que el build incluye PHP portable:

```powershell
Test-Path .\dist\win-unpacked\resources\php\php.exe
Test-Path .\dist\win-unpacked\resources\php\license.txt
Test-Path .\dist\win-unpacked\resources\php\extras\ssl\cacert.pem
Test-Path .\dist\win-unpacked\resources\jjh-space\vendor\autoload.php
Test-Path .\dist\win-unpacked\resources\jjh-space\vendor\phpmailer\phpmailer
Test-Path .\dist\win-unpacked\resources\jjh-space\vendor\mpdf\mpdf
```

## Subir codigo

```powershell
git status
git add .
git commit -m "Release v0.1.0"
git tag v0.1.0
git push
git push origin v0.1.0
```

## Crear Release en GitHub

1. Abrir GitHub.
2. Ir a Releases.
3. Pulsar Draft a new release.
4. Seleccionar tag `v0.1.0`.
5. Titulo: `Your Small Desk v0.1.0`.
6. Pegar el contenido de `docs/release-notes-v0.1.0.md`.
7. Adjuntar los `.exe` desde `desktop/dist/`.
8. Publicar.

## No subir al repositorio

- `desktop/dist/`
- binarios de `desktop/php/`
- instaladores `.exe`
- `data/*.sqlite`
- PDFs generados
- logos reales
- logs
- `.env`
- `node_modules`
- `vendor`
