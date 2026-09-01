@ECHO OFF
SETLOCAL EnableExtensions

REM Check JoomGallery with the same PHP-CS-Fixer command used by the PR workflow.
REM Run from the repository root: tools\checkCodeBy_cs_fixer.bat

SET "LOG_FILE=tools\check.php-cs-fixer.log"

IF NOT EXIST "composer.json" GOTO :WrongDirectory
IF NOT EXIST ".php-cs-fixer.dist.php" GOTO :WrongDirectory
IF NOT EXIST "src\" GOTO :WrongDirectory

WHERE php >NUL 2>&1
IF ERRORLEVEL 1 (
  ECHO ERROR: PHP is not available on PATH.
  EXIT /B 1
)

IF NOT EXIST "vendor\bin\php-cs-fixer" IF NOT EXIST "vendor\bin\php-cs-fixer.bat" (
  ECHO ERROR: vendor\bin\php-cs-fixer is missing. Run "composer install" first.
  EXIT /B 1
)

ECHO Checking PHP code with the PR pipeline's PHP-CS-Fixer command
ECHO Log: %LOG_FILE%
ECHO.

SET "PHP_CS_FIXER_IGNORE_ENV=true"
php ".\vendor\bin\php-cs-fixer" fix -vvv --dry-run --diff > "%LOG_FILE%" 2>&1
SET "STATUS=%ERRORLEVEL%"
TYPE "%LOG_FILE%"

ECHO.
IF "%STATUS%"=="0" (
  ECHO SUCCESS: PHP-CS-Fixer passes the PR pipeline check.
) ELSE (
  ECHO FAILED: PHP-CS-Fixer would change files.
  ECHO Run "tools\fixCodeStyle.bat" to apply the fixes.
  ECHO The proposed differences are listed in %LOG_FILE%.
)

EXIT /B %STATUS%

:WrongDirectory
ECHO ERROR: Run this script from the JoomGallery repository root.
EXIT /B 1
