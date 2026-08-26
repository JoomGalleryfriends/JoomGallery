# Code-style tools

These scripts format and check the PHP code in the folder `src/`. Their final checks use the same commands as the GitHub PullRequest pipeline. So they can be used to make PR checks succeed.

All commands in this document must be run from the repository root. Do not change into the `tools` or `src` directory first.

## Requirements

- PHP 8.2 or later must be available on `PATH` for the repository-level development tools.
- Composer must be available on `PATH` when running the complete repair script.
- Install the locked development dependencies before running an individual check:

  ```sh
  composer install
  ```

Composer installs development tools in the root `vendor/` directory and JoomGallery dependencies in `src/administrator/com_joomgallery/vendor/`.

## Repair code style and verify the PR checks

Use the complete repair script before committing or opening a pull request.

Windows Command Prompt or PowerShell:

```bat
tools\fixCodeStyle.bat
```

Linux, macOS, Git Bash, or WSL:

```sh
bash tools/fixCodeStyle.sh
```

The script performs these steps:

1. Verifies that it is running from the repository root.
2. Runs `composer install` using the committed lockfiles.
3. Applies the configured PHP-CS-Fixer rules.
4. Normalizes indentation to two spaces with `fixindent.php`.
5. Applies fixable PHPCS violations with PHPCBF.
6. Runs PHP-CS-Fixer again because PHPCBF can change whitespace.
7. Runs the exact PHP-CS-Fixer dry-run used by the PR pipeline.
8. Runs the exact PHPCS check used by the PR pipeline.
9. Runs PHPStan when `joomla/includes/framework.php` exists, matching the CI condition.
10. Runs Rector in dry-run mode.

The repair script exits with status `0` only when all applicable PR checks pass. PHPCS or Rector findings that cannot safely be fixed automatically are reported for manual correction.

### Repair and verification logs

The complete script writes output to:

- `tools/00.composer-install.log`
- `tools/01.php-cs-fixer.log`
- `tools/02.fixindent.log`
- `tools/03.phpcbf.log`
- `tools/04.php-cs-fixer.log`
- `tools/05.ci-php-cs-fixer.log`
- `tools/06.ci-phpcs.log`
- `tools/07.ci-phpstan.log`, when PHPStan runs
- `tools/08.ci-rector.log`

Output is also displayed in the terminal. The log files are ignored by Git.

## Run check-only scripts

The check scripts do not modify source files and do not install dependencies. Run `composer install` first.

### PHP-CS-Fixer check

Windows:

```bat
tools\checkCodeBy_cs_fixer.bat
```

Linux/macOS:

```sh
bash tools/checkCodeBy_cs_fixer.sh
```

This runs the pipeline-equivalent command:

```sh
vendor/bin/php-cs-fixer fix -vvv --dry-run --diff
```

Results are written to `tools/check.php-cs-fixer.log`. A nonzero exit status means PHP-CS-Fixer would modify one or more files.

### PHPCS check

The `cs_cbf` filename is retained for compatibility, but this script performs the non-mutating PHPCS check. PHPCBF is used only by the complete repair script.

Windows:

```bat
tools\checkCodeBy_cs_cbf.bat
```

Linux/macOS:

```sh
bash tools/checkCodeBy_cs_cbf.sh
```

This runs the pipeline-equivalent command:

```sh
vendor/bin/phpcs --extensions=php -p --standard=ruleset.xml \
  --runtime-set ignore_warnings_on_exit 1 src
```

Results are written to `tools/check.phpcs.log`. A nonzero exit status means PHPCS found errors. Warnings are displayed but do not fail the check, matching CI.

## Run the indentation helpers directly

The indentation helpers use the customized `colinodell/indentation` dependency from the root `vendor/` directory. They inspect PHP files below `src/administrator`, `src/site`, and `src/plugins`; component vendors, `includes`, `node_modules`, and cache directories are excluded.

### Entire source tree

Analyze without changing files:

```sh
php tools/fixindent.php
```

Analyze with per-file details:

```sh
php tools/fixindent.php analyze details
```

Apply indentation fixes:

```sh
php tools/fixindent.php fix
```

Apply fixes with per-file details:

```sh
php tools/fixindent.php fix details
```

In analysis mode, exit status `1` means files need changes. In fix mode, exit status `1` means at least one file could not be updated.

### One PHP file

Pass a path relative to `src`:

```sh
php tools/fixindent_file.php plugins/console/joomconsole/src/Extension/JoomgalleryConsole.php
```

An explicit `src/` prefix is also accepted:

```sh
php tools/fixindent_file.php src/plugins/console/joomconsole/src/Extension/JoomgalleryConsole.php
```

Add `fix` to modify the file and `details` for detailed output:

```sh
php tools/fixindent_file.php plugins/console/joomconsole/src/Extension/JoomgalleryConsole.php fix details
```

For safety, `fixindent_file.php` rejects missing files, non-PHP files, and paths outside `src`.

## Recommended workflow

1. Make and test the code changes.
2. Run the complete `fixCodeStyle` script for the operating system.
3. Review all source changes; formatting tools modify files in place.
4. Correct any PHPCS, PHPStan, or Rector findings that require manual work.
5. Run the complete script again until it reports success.
6. Commit the source changes, not the generated log files or `vendor/` directories.

If a script reports that it was started from the wrong directory, return to the repository root and run it again.
