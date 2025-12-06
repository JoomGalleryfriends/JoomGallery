#!/usr/bin/env bash

# -----------------------------------------------------
# Check joomgallery code style by php-cs-fixer (Linux/macOS)
# Corresponds to: jg_checkCodeBy_cs_fixer.bat
# -----------------------------------------------------

clear

echo "----------------------------------------------"
echo "Check joomgallery code style by php-cs-fixer"
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
# Keep actual directory for reference

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
# Verify correct working directory using joomgallery.xml

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

echo "Install needed dependencies (composer)"
echo

echo "--- composer install"
composer install --prefer-dist --no-ansi --no-interaction --no-progress
if [ $? -ne 0 ]; then
    echo
    echo "ERROR: composer install failed!"
    popd > /dev/null
    exit 1
fi

echo "Composer tasks completed successfully."
echo

# =====================================================
# call "php-cs-fixer"

echo "----------------------------------------------"
echo "call \"php-cs-fixer\""
echo "   may take some time"
echo

php "./administrator/com_joomgallery/vendor/bin/php-cs-fixer" \
    --dry-run --verbose --config=./.php-cs-fixer.dist.php ./
echo

# -----------------------------------------------------
# Move back

popd > /dev/null
echo
echo "Done and moved back to path: $(pwd)"
echo

exit 0
