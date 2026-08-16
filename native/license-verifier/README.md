# Softtrill Native License Verifier

This directory contains the C source code for the `softtrill_license` PHP extension.

The purpose of this extension is to provide a native binary layer for Ed25519 signature verification (`softtrill_verify_ed25519`). By compiling this logic into a binary extension (DLL or SO), it prevents casual modification of the verification logic that would otherwise exist in plain PHP.

## Prerequisites (Windows)
1. PHP SDK for your target PHP version (e.g. PHP 8.2).
2. Microsoft Visual C++ Build Tools (e.g. VS 2019 for PHP 8.2).

## Build Instructions (Windows)
1. Open the "x64 Native Tools Command Prompt for VS".
2. Set up the PHP build environment according to official PHP instructions (`phpsdk_buildtree phpdev`).
3. Place this extension directory into the `ext/` folder of your PHP source tree, named `softtrill_license`.
4. Run `buildconf`.
5. Run `configure --enable-softtrill_license=shared`.
6. Run `nmake`.
7. Retrieve the `php_softtrill_license.dll` from the `x64/Release_TS/` folder.

## Build Instructions (Linux)
1. Install `php-dev` (e.g. `apt install php8.2-dev`).
2. Run `phpize` in this directory.
3. Run `./configure`.
4. Run `make`.
5. Retrieve `softtrill_license.so` from the `modules/` directory.

## Installation on Customer Server
1. Copy the compiled `.dll` or `.so` to the PHP `ext` directory on the customer server.
2. Edit `php.ini` and add:
   ```ini
   extension=softtrill_license
   ```
3. Restart the web server (Apache/Nginx/PHP-FPM).
4. Verify the extension is loaded by running:
   ```bash
   php -m | findstr softtrill
   # or on Linux: php -m | grep softtrill
   ```
