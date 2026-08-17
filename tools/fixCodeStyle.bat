@ECHO OFF
SETLOCAL EnableExtensions

REM Fix JoomGallery code style, then run the same checks as the PR workflow.
REM Run from the repository root: tools\fixCodeStyle.bat

SET "LOG_DIR=tools"
SET "PHP_CS_FIXER_IGNORE_ENV=true"

ECHO JoomGallery code-style repair and PR verification

IF NOT EXIST "composer.json" GOTO :WrongDirectory
IF NOT EXIST "joomgallery.xml" GOTO :WrongDirectory
IF NOT EXIST "src\" GOTO :WrongDirectory

WHERE php >NUL 2>&1
IF ERRORLEVEL 1 (
  ECHO ERROR: PHP is not available on PATH.
  EXIT /B 1
)

WHERE composer >NUL 2>&1
IF ERRORLEVEL 1 (
  ECHO ERROR: Composer is not available on PATH.
  EXIT /B 1
)

php --version

CALL :Run "Install locked dependencies" "00.composer-install.log" "composer install --prefer-dist --no-ansi --no-interaction --no-progress"
IF ERRORLEVEL 1 GOTO :Failed

CALL :Run "1/3 Apply PHP-CS-Fixer rules" "01.php-cs-fixer.log" "php .\vendor\bin\php-cs-fixer fix -vvv --diff"
IF ERRORLEVEL 1 GOTO :Failed

CALL :Run "2/3 Normalize indentation" "02.fixindent.log" "php .\tools\fixindent.php fix details"
IF ERRORLEVEL 1 GOTO :Failed

CALL :Run "3/3 Apply PHPCS fixes with PHPCBF" "03.phpcbf.log" "php .\vendor\bin\phpcbf --extensions=php -p -v --standard=ruleset.xml src"
IF ERRORLEVEL 2 GOTO :Failed

CALL :Run "Re-apply PHP-CS-Fixer after PHPCBF" "04.php-cs-fixer.log" "php .\vendor\bin\php-cs-fixer fix -vvv --diff"
IF ERRORLEVEL 1 GOTO :Failed

ECHO.
ECHO Running the exact code-style checks used by JoomGallery's GitHub PullRequest pipeline.

CALL :Run "CI check: PHP-CS-Fixer" "05.ci-php-cs-fixer.log" "php .\vendor\bin\php-cs-fixer fix -vvv --dry-run --diff"
IF ERRORLEVEL 1 GOTO :Failed

CALL :Run "CI check: PHPCS" "06.ci-phpcs.log" "php .\vendor\bin\phpcs --extensions=php -p --standard=ruleset.xml --runtime-set ignore_warnings_on_exit 1 src"
IF ERRORLEVEL 1 GOTO :Failed

IF EXIST "joomla\includes\framework.php" (
  CALL :Run "CI check: PHPStan" "07.ci-phpstan.log" "php .\vendor\bin\phpstan analyse"
  IF ERRORLEVEL 1 GOTO :Failed
) ELSE (
  ECHO.
  ECHO Skipping PHPStan exactly as CI does: joomla\includes\framework.php is absent.
)

CALL :Run "CI check: Rector dry-run" "08.ci-rector.log" "php .\vendor\bin\rector process --dry-run"
IF ERRORLEVEL 1 GOTO :Failed

ECHO.
ECHO SUCCESS: local checks match the PR pipeline.
ECHO Logs are available in tools\*.log.
EXIT /B 0

:Run
SET "STEP_TITLE=%~1"
SET "LOG_FILE=%LOG_DIR%\%~2"
ECHO.
ECHO ------------------------------------------------------------
ECHO %STEP_TITLE%
ECHO Log: %LOG_FILE%
ECHO ------------------------------------------------------------
%~3 > "%LOG_FILE%" 2>&1
SET "STEP_STATUS=%ERRORLEVEL%"
TYPE "%LOG_FILE%"
EXIT /B %STEP_STATUS%

:WrongDirectory
ECHO.
ECHO ERROR: Run this script from the JoomGallery repository root.
EXIT /B 1

:Failed
ECHO.
ECHO ERROR: A repair or CI verification step failed.
ECHO Review the last displayed output and the corresponding tools\*.log file.
EXIT /B 1
