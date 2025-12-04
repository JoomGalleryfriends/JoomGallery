#!/usr/bin/env bash

# -----------------------------------------------------
# Check joomgallery code style by phpcs (jg_checkCodeBy_cs_cbf.sh)
# -----------------------------------------------------

clear

echo "----------------------------------------------"
echo "Check joomgallery code style by phpcs"
echo "----------------------------------------------"
echo

# -----------------------------------------------------
# Check if PHP is available

echo "php check"

if ! php --version > /dev/null 2>&1; then
    echo
    echo "Actual environment PATH:"
    echo "$PATH"
    echo
    echo "Please add the path to php to PATH variable"
    echo 'using: export PATH="$PATH:/your/path/here"'
    echo
    exit 1
fi

# -----------------------------------------------------
# Save current directory for log files

actualPath="$(pwd)"
echo "  - 'actualPath $actualPath'"

# -----------------------------------------------------
# Determine repository base path
# Default: one directory up from tools/

jg_basePath="../"

if [ -n "$1" ]; then
    jg_basePath="$1"
fi

echo "  - 'jg base path $jg_basePath'"

# -----------------------------------------------------
# Move to jg_basePath

pushd "$jg_basePath" > /dev/null
echo "Moved to path: $(pwd)"

# -----------------------------------------------------
# Verify that we are in the correct directory

if [ ! -f "joomgallery.xml" ]; then
    echo
    echo "ERROR: joomgallery.xml not found in $(pwd)"
    echo "This does not appear to be the JoomGallery root directory."
    echo "Aborting to prevent accidental composer operations!"
    echo
    popd > /dev/null
    exit 1
fi

# -----------------------------------------------------
# Composer housekeeping

echo "Install and update needed dependencies (composer)"
echo

echo "--- composer dump-autoload"
composer dump-autoload
if [ $? -ne 0 ]; then
    echo
    echo "ERROR: composer dump-autoload failed!"
    popd > /dev/null
    exit 1
fi

echo "--- composer install"
composer install
if [ $? -ne 0 ]; then
    echo
    echo "ERROR: composer install failed!"
    popd > /dev/null
    exit 1
fi

echo "--- composer update"
composer update
if [ $? -ne 0 ]; then
    echo
    echo "ERROR: composer update failed!"
    popd > /dev/null
    exit 1
fi

echo "Composer tasks completed successfully."
echo

# =====================================================
# Call "phpcs"

echo "----------------------------------------------"
echo "call \"phpcs\""
echo "   may take some time"
echo

php "./administrator/com_joomgallery/vendor/bin/phpcs" --standard=ruleset.xml ./
echo

# -----------------------------------------------------
# Move back

popd > /dev/null
echo
echo "Done and moved back to path: $(pwd)"
echo

exit 0
