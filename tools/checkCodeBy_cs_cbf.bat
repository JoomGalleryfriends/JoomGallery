@ECHO OFF
SETLOCAL EnableExtensions

REM Check JoomGallery with the same PHPCS command used by the PR workflow.
REM Run from the repository root: tools\checkCodeBy_cs_cbf.bat

SET "LOG_FILE=tools\check.phpcs.log"

IF NOT EXIST "composer.json" GOTO :WrongDirectory
IF NOT EXIST "ruleset.xml" GOTO :WrongDirectory
IF NOT EXIST "src\" GOTO :WrongDirectory

WHERE php >NUL 2>&1
IF ERRORLEVEL 1 (
  ECHO ERROR: PHP is not available on PATH.
  EXIT /B 1
)

IF NOT EXIST "vendor\bin\phpcs" IF NOT EXIST "vendor\bin\phpcs.bat" (
  ECHO ERROR: vendor\bin\phpcs is missing. Run "composer install" first.
  EXIT /B 1
)

ECHO Checking PHP code with the PR pipeline's PHPCS command
ECHO Log: %LOG_FILE%
ECHO.

php ".\vendor\bin\phpcs" --extensions=php -p --standard=ruleset.xml --runtime-set ignore_warnings_on_exit 1 src > "%LOG_FILE%" 2>&1
SET "STATUS=%ERRORLEVEL%"
TYPE "%LOG_FILE%"

ECHO.
IF "%STATUS%"=="0" (
  ECHO SUCCESS: PHPCS passes the PR pipeline check.
) ELSE (
  ECHO FAILED: PHPCS found coding-standard errors.
  ECHO Run "tools\fixCodeStyle.bat" to fix what can be fixed automatically.
  ECHO Remaining violations are listed in %LOG_FILE%.
)

EXIT /B %STATUS%

:WrongDirectory
ECHO ERROR: Run this script from the JoomGallery repository root.
EXIT /B 1

