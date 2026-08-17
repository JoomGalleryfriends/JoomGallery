#!/usr/bin/env bash

# Fix JoomGallery code style, then run the same checks as the PR workflow.
# Run from the repository root: bash tools/fixCodeStyle.sh

set -o pipefail

log_dir="tools"
export PHP_CS_FIXER_IGNORE_ENV=true

fail() {
  echo
  echo "ERROR: $1"
  echo "Review the corresponding log in ${log_dir}/."
  exit 1
}

run_logged() {
  local title="$1"
  local log_file="$2"
  shift 2

  echo
  echo "------------------------------------------------------------"
  echo "$title"
  echo "Command: $*"
  echo "Log: $log_file"
  echo "------------------------------------------------------------"

  "$@" 2>&1 | tee "$log_file"
  return "${PIPESTATUS[0]}"
}

echo "JoomGallery code-style repair and PR verification"

if [ ! -f "composer.json" ] || [ ! -f "joomgallery.xml" ] || [ ! -d "src" ]; then
  fail "Run this script from the JoomGallery repository root."
fi

command -v php >/dev/null 2>&1 || fail "PHP is not available on PATH."
command -v composer >/dev/null 2>&1 || fail "Composer is not available on PATH."

php --version | head -n 1

run_logged "Install locked dependencies" "${log_dir}/00.composer-install.log" \
  composer install --prefer-dist --no-ansi --no-interaction --no-progress \
  || fail "Composer install failed."

run_logged "1/3 Apply PHP-CS-Fixer rules" "${log_dir}/01.php-cs-fixer.log" \
  php ./vendor/bin/php-cs-fixer fix -vvv --diff \
  || fail "PHP-CS-Fixer could not complete."

run_logged "2/3 Normalize indentation" "${log_dir}/02.fixindent.log" \
  php ./tools/fixindent.php fix details \
  || fail "The indentation helper could not complete."

# PHPCBF returns 1 when it fixed violations and 2 when violations remain.
run_logged "3/3 Apply PHPCS fixes with PHPCBF" "${log_dir}/03.phpcbf.log" \
  php ./vendor/bin/phpcbf --extensions=php -p -v --standard=ruleset.xml src
phpcbf_status=$?
if [ "$phpcbf_status" -gt 1 ]; then
  fail "PHPCBF left unfixable errors."
fi

# PHPCBF can alter whitespace covered by PHP-CS-Fixer, so converge once more.
run_logged "Re-apply PHP-CS-Fixer after PHPCBF" "${log_dir}/04.php-cs-fixer.log" \
  php ./vendor/bin/php-cs-fixer fix -vvv --diff \
  || fail "The final PHP-CS-Fixer pass failed."

echo
echo "Running the exact code-style checks used by .github/workflows/pr-build.yml"

run_logged "CI check: PHP-CS-Fixer" "${log_dir}/05.ci-php-cs-fixer.log" \
  php ./vendor/bin/php-cs-fixer fix -vvv --dry-run --diff \
  || fail "PHP-CS-Fixer CI check still fails."

run_logged "CI check: PHPCS" "${log_dir}/06.ci-phpcs.log" \
  php ./vendor/bin/phpcs --extensions=php -p --standard=ruleset.xml \
    --runtime-set ignore_warnings_on_exit 1 src \
  || fail "PHPCS reports violations that PHPCBF could not fix automatically."

if [ -f "joomla/includes/framework.php" ]; then
  run_logged "CI check: PHPStan" "${log_dir}/07.ci-phpstan.log" \
    php ./vendor/bin/phpstan analyse \
    || fail "PHPStan reports errors; these require manual code changes."
else
  echo
  echo "Skipping PHPStan exactly as CI does: joomla/includes/framework.php is absent."
fi

run_logged "CI check: Rector dry-run" "${log_dir}/08.ci-rector.log" \
  php ./vendor/bin/rector process --dry-run \
  || fail "Rector proposes changes. Review the log and apply them deliberately."

echo
echo "SUCCESS: local checks match the PR pipeline."
echo "Logs are available in ${log_dir}/*.log."
