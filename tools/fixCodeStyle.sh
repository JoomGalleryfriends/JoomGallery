#!/usr/bin/env bash

# -----------------------------------------------------
# Fix joomgallery code style (fixCodeStyle.sh)
# -----------------------------------------------------
# This script performs the following:
# 1. composer dump-autoload
# 2. composer install
# 3. composer update
# 4. php-cs-fixer
# 5. phpcbf
# 6. php-cs-fixer again
# -----------------------------------------------------

clear

echo "----------------------------------------------"
echo "Fix joomgallery code style"
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
    echo "Please add php to PATH."
    echo "Example: export PATH=\"\$PATH:/path/to/php\""
    echo
    exit 1
fi

# -----------------------------------------------------
# keep actual directory for log files

actualPath="$(pwd)"
echo "  - actualPath \"$actualPath\""

# -----------------------------------------------------
# jg_basePath to the repository
# Default: one level up from tools/

jg_basePath="../"

if [ -n "$1" ]; then
    jg_basePath="$1"
fi

echo "  - jg base path \"$jg_basePath\""

# -----------------------------------------------------
# Move to jg_basePath

pushd "$jg_basePath" > /dev/null
echo "Moved to path: $(pwd)"

# -----------------------------------------------------
# Verify correct directory using joomgallery.xml

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
# 01 call "php-cs-fixer"

echo "----------------------------------------------"
echo "01 call \"php-cs-fixer\""
echo "   log file 01.php-cs-fixer.log"
echo "   may take some time"
echo

php "./administrator/com_joomgallery/vendor/bin/php-cs-fixer" \
    --verbose --config=./.php-cs-fixer.dist.php fix ./ \
    > "${actualPath}/01.php-cs-fixer.log"

echo

# =====================================================
# 02 call "fixindent"

echo "----------------------------------------------"
echo "02 call \"fixindent\""
echo "   log file 02.fixindent.log"
echo "   may take some time"
echo

php "./tools/fixindent.php fix" > "${actualPath}/02.fixindent.log"

echo

# =====================================================
# 03 call "phpcbf"

echo "----------------------------------------------"
echo "03 call \"phpcbf\""
echo "   log file 03.phpcbf.log"
echo "   may take some time"
echo

php "./administrator/com_joomgallery/vendor/bin/phpcbf" \
    -v --standard=ruleset.xml ./ \
    > "${actualPath}/03.phpcbf.log"

echo

# =====================================================
# 04 call "php-cs-fixer" again

echo "----------------------------------------------"
echo "04 call \"php-cs-fixer\""
echo "   log file 04.php-cs-fixer.log"
echo "   may take some time"
echo

php "./administrator/com_joomgallery/vendor/bin/php-cs-fixer" \
    --verbose --config=./.php-cs-fixer.dist.php fix ./ \
    > "${actualPath}/04.php-cs-fixer.log"

echo

# -----------------------------------------------------
# Move back

popd > /dev/null
echo
echo "Done and moved back to path: $(pwd)"
echo
exit 0
