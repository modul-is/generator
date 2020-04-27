@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0../modul-is/generator/generate
php "%BIN_TARGET%" %*
