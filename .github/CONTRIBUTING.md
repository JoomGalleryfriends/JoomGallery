# Contributing to JoomGallery

Thank you for considering a contribution to JoomGallery. This is a volunteer-driven open-source Joomla! extension. You can contribute in many ways like code reviews, bug fixes, documentation and testing. Everything helps the project.

--> [How to contribute](https://www.joomgalleryfriends.net/en/opensource.html)

## Before you start

1. Search [existing issues](https://github.com/JoomGalleryfriends/JoomGallery/issues) and [pull requests](https://github.com/JoomGalleryfriends/JoomGallery/pulls) for related work already in development.
2. For a significant changes, discuss the proposal in an issue before investing in implementation. Maintainer capacity is limited, so discussions and reviews may take time.
3. The [roadmap](https://www.forum.joomgalleryfriends.net/forum/index.php?thread/483-unverbindliche-roadmap/&postID=2880#post2880) shows what is planned for the next releases. Contributions towards this roadmap will be handled with priority.
4. Read the [contribution guide](../docs/Contribution.md), [PHP coding style guide](../docs/Codestyleguide.md), and [release strategy](../docs/Releasestrategy.md) if available in the branch you use.
5. Select the development base branch according to the project's current contribution instructions. **Do not assume that `master` is the correct target for every pull request.**

## Development prerequisites

- Git, PHP and Composer.
- A local Joomla! installation compatible with the JoomGallery version you are developing.
- A supported database and local web server; GD or ImageMagick if your work involves image processing.
- A code editor or IDE (for example, VS Code or PhpStorm).

### Useful references:
- [Joomla! developer manual](https://manual.joomla.org/)
- [Joomla! extension development community](https://joomlacommunity.cloud.mattermost.com/main/channels/extension-development-room)
- [Robbies video series on Joomla 4 component development](https://www.youtube.com/playlist?list=PLzio09PZm6TuXGnu-ptpVb90Szkawy9IV)
- [Nicholas book on Joomla 4 development](https://www.dionysopoulos.me/book.html)

## Clone and install dependencies

```sh
git clone https://github.com/JoomGalleryfriends/JoomGallery.git
cd JoomGallery
composer install
```

Repository layout:

| Path | Purpose |
| --- | --- |
| `src/` | Deployable extension source tree |
| `vendor/` | Repository-level development dependencies |
| `src/administrator/com_joomgallery/vendor/` | Runtime dependencies included with the component |
| `joomla/` | Gitignored local Joomla installation for tools such as PHPStan and Rector |

Install a Joomla! site in the gitignored `joomla/` directory to provide the Joomla classes expected by analysis tools. Configure its database and installation locally; **never commit local configuration, credentials or uploaded photos**.

## Run checks

Use the repository's Composer scripts to check your work without modifying files:

```sh
composer cs-check  # (php-cs-fixer, code style)
composer phpcs     # (phpcs, code style)
composer phpstan   # (phpstan, static code analyzer)
composer rector    # (rector, automatic code refactoring)
```

`composer rector` performs a dry run. Examine its suggestions carefully before executing Rector in write mode.

For local style fixes, use the repository tools (and review the resulting diff):

```sh
# fix indentation
php ./tools/fixindent.php -fix

# fix code style
./vendor/bin/php-cs-fixer fix
./vendor/bin/phpcbf -p --standard=ruleset.xml src
./vendor/bin/php-cs-fixer fix
```

Run the checks that apply to your changes before proposing a pull request. If a command cannot run in your environment, mention that in the pull-request description.

## Test your changes in Joomla!

For active development, you can install a development build into a local Joomla! site, remove its deployed component folders and replace them with symbolic links to matching folders in your checkout. **Do this only in a disposable development installation**, never a live site; deleting deployed folders on a production installation can cause data loss or downtime.

Common folders include:

```text
administrator/components/com_joomgallery
components/com_joomgallery
media/com_joomgallery
plugins/console/joomconsole
plugins/finder/joomgallery
plugins/privacy/joomgallery
plugins/system/joomgallery
plugins/system/joomowner
plugins/task/joomgallery
```

Check the package and paths for the branch you are testing, since extensions and install locations may change. On Windows, configure symlink permissions or use an appropriate link-management tool. Ensure your Joomla test site has backups or disposable test data.

## Submit a pull request

1. Fork the [maintained repository](https://github.com/JoomGalleryfriends/JoomGallery) to your GitHub account.
2. Create a descriptive branch from the current contribution base branch.
3. Make a focused change; keep unrelated refactors separate.
4. Add or update tests and documentation when appropriate; test relevant Joomla workflows manually.
5. Run quality checks and inspect the diff for secrets, debug output, unrelated files and accidental formatting changes.
6. Push your branch and open a pull request to the appropriate branch in `JoomGalleryfriends/JoomGallery`.
7. Explain **what changed**, **why**, **how it was tested**, and any compatibility or migration implications. Reference relevant issues.
8. Respond to review comments when possible. Maintainers may request revisions or defer changes due to volunteer capacity.

Please do not add third-party code or assets unless redistribution is compatible with the project's licensing and attribution requirements.

## Test somebody else's pull request

Testing is valuable even if you do not write code. A PR may provide a downloadable build artifact through GitHub Actions:

1. Open the pull request and inspect its changes and test instructions.
2. Open the successful GitHub Actions run of the PR and download the PR build ZIP. GitHub login is required.
3. Install the package into a **test Joomla! site only**; do not deploy unreviewed PR artifacts on production systems.
4. Exercise the changed functionality and relevant regressions.
5. Comment on the PR with the Joomla/PHP/database versions, exact reproduction steps, expected and actual results, and logs or screenshots with credentials and personal data removed.

### Useful bug-report template

```text
Summary:
Steps to reproduce:
1.
2.
Expected result:
Actual result:
Environment: Joomla / JoomGallery / PHP / database / image processor
Additional details (sanitized):
```

## Additional ways to help

- [Translate via Crowdin](https://joomla.crowdin.com/joomgallery).
- Improve user documentation, examples and accessibility.
- Reproduce and triage non-security bug reports.
- Answer questions in the [support forum](https://www.forum.joomgalleryfriends.net/).

Project-maintainer history is summarized in the [README](../README.md). For official current code and PRs, always use the `JoomGallery friends` GitHub organization.
