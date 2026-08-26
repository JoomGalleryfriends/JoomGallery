#!/usr/bin/env bash

# Check JoomGallery with the same PHPCS command used by the PR workflow.
# Run from the repository root: bash tools/checkCodeBy_cs_cbf.sh

set -o pipefail

log_file="tools/check.phpcs.log"

if [ ! -f "composer.json" ] || [ ! -f "ruleset.xml" ] || [ ! -d "src" ]; then
  echo "ERROR: Run this script from the JoomGallery repository root."
  exit 1
fi

command -v php >/dev/null 2>&1 || {
  echo "ERROR: PHP is not available on PATH."
  exit 1
}

if [ ! -f "vendor/bin/phpcs" ]; then
  echo "ERROR: vendor/bin/phpcs is missing. Run 'composer install' first."
  exit 1
fi

echo "Checking PHP code with the PR pipeline's PHPCS command"
echo "Log: ${log_file}"
echo

php ./vendor/bin/phpcs \
  --extensions=php \
  -p \
  --standard=ruleset.xml \
  --runtime-set ignore_warnings_on_exit 1 \
  src 2>&1 | tee "$log_file"
status="${PIPESTATUS[0]}"

echo
if [ "$status" -eq 0 ]; then
  echo "SUCCESS: PHPCS passes the PR pipeline check."
else
  echo "FAILED: PHPCS found coding-standard errors."
  echo "Run 'bash tools/fixCodeStyle.sh' to fix what can be fixed automatically."
  echo "Remaining violations are listed in ${log_file}."
fi

exit "$status"

