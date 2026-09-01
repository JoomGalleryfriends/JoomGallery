#!/usr/bin/env bash

# Check JoomGallery with the same PHP-CS-Fixer command used by the PR workflow.
# Run from the repository root: bash tools/checkCodeBy_cs_fixer.sh

set -o pipefail

log_file="tools/check.php-cs-fixer.log"

if [ ! -f "composer.json" ] || [ ! -f ".php-cs-fixer.dist.php" ] || [ ! -d "src" ]; then
  echo "ERROR: Run this script from the JoomGallery repository root."
  exit 1
fi

command -v php >/dev/null 2>&1 || {
  echo "ERROR: PHP is not available on PATH."
  exit 1
}

if [ ! -f "vendor/bin/php-cs-fixer" ]; then
  echo "ERROR: vendor/bin/php-cs-fixer is missing. Run 'composer install' first."
  exit 1
fi

echo "Checking PHP code with the PR pipeline's PHP-CS-Fixer command"
echo "Log: ${log_file}"
echo

PHP_CS_FIXER_IGNORE_ENV=true php ./vendor/bin/php-cs-fixer \
  fix -vvv --dry-run --diff 2>&1 | tee "$log_file"
status="${PIPESTATUS[0]}"

echo
if [ "$status" -eq 0 ]; then
  echo "SUCCESS: PHP-CS-Fixer passes the PR pipeline check."
else
  echo "FAILED: PHP-CS-Fixer would change files."
  echo "Run 'bash tools/fixCodeStyle.sh' to apply the fixes."
  echo "The proposed differences are listed in ${log_file}."
fi

exit "$status"

