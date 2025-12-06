@ECHO OFF
REM -----------------------------------------------------
REM Check joomgallery code style by phpcs
REM calling cbf (jg_checkCodeBy_cs_cbf.bat)
REM -----------------------------------------------------
REM
REM -----------------------------------------------------

CLS

ECHO ----------------------------------------------
ECHO Check joomgallery code style by phpcs
ECHO ----------------------------------------------
REM ECHO.

REM -----------------------------------------------------
REM Check if PHP is available

ECHO php check

php --version >NUL 2>&1
IF errorlevel 1 (
	ECHO.
	ECHO Actual environment PATH:
	ECHO "%path%"
	ECHO.
	ECHO Please add the path to php.exe to path variable
	ECHO using "set PATH=%%PATH%%;C:\your\path\here\"
	GOTO :EOF
	ECHO.
)

REM -----------------------------------------------------
REM keep actual directory for log files
set "actualPath=%cd%"
ECHO  - 'actualPath %actualPath%'

REM -----------------------------------------------------
REM jg_basePath to the repository
REM
set "jg_basePath=..\"
IF NOT  "%~1"=="" (
 	set "jg_basePath=%~1"
)
ECHO  - 'jg base path %jg_basePath%'

REM -----------------------------------------------------
REM Move to jg_basePath

pushd  "%jg_basePath%"
ECHO Moved to path: %cd%

REM -----------------------------------------------------
REM Verify that we are in the correct working directory
REM Check for required file: joomgallery.xml
IF NOT EXIST "joomgallery.xml" (
    ECHO.
    ECHO ERROR: joomgallery.xml not found in %cd%
    ECHO This does not appear to be the JoomGallery root directory.
    ECHO Aborting to prevent accidental composer operations!
    ECHO.
    GOTO :ErrorBack
)
REM -----------------------------------------------------
REM Composer housekeeping

ECHO Install and update needed dependencies (composer)

echo "--- composer install"
call composer install --prefer-dist --no-ansi --no-interaction --no-progress
IF errorlevel 1 (
    ECHO.
    ECHO ERROR: composer install failed!
    GOTO :ErrorBack
)

ECHO Composer tasks completed successfully.
ECHO.

REM =====================================================
REM call "phpcs"

ECHO ----------------------------------------------
ECHO call "phpcs"
ECHO    may take some time

php ".\administrator\com_joomgallery\vendor\bin\phpcs" --standard=ruleset.xml .\
REM if errorlevel 1 (
REM 	ECHO Error on calling phpcs (02)
REM 	goto :ErrorBack
REM )
ECHO.

REM -----------------------------------------------------
REM Move back

:MoveBack
popd
ECHO.
ECHO Done and moved back to path: %cd%
ECHO.
GOTO :EOF

:ErrorBack
popd
ECHO.
ECHO !!! Error found !!!
ECHO and moved back to path: %cd%
ECHO.
GOTO :EOF

