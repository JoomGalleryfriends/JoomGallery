# Contributing to JoomGallery

Just like the [Joomla! CMS™](https://github.com/joomla/joomla-cms), this gallery component, JoomGallery, is an Open Source project and exists because of its community. Which means that everything in the project is developed and maintained by users of the JoomGallery.
Therefore we ask you to use your skills and knowledge to contribute to the project. Here on GitHub you can help coding and developing the extension.

Following the JoomGalleries coding documentation:

- [Release-Strategy](docs/Releasestrategy.md)
- [Codestyle guide for PHP](docs/Codestyleguide.md)
- [Contribution guide](/docs/Contribution.md)

### Setup development environment
https://docs.joomla.org/Setting_up_your_workstation_for_Joomla_development

The deployable extension tree lives in `src/`. Development dependencies are installed in the repository-level `vendor/`, while runtime dependencies shipped with the component are installed in `src/administrator/com_joomgallery/vendor/`.

After cloning, run:

```sh
composer install
```

Put a Joomla installation in the gitignored `joomla/` directory for PHPStan and Rector type discovery. The available checks (no file writes) are:

```sh
composer cs-check
composer phpcs
composer phpstan
composer rector
```

`composer rector` is a dry run. Review its output before invoking `vendor/bin/rector process` without `--dry-run`.

To apply code-style fixes, you can do by

```sh
./vendor/bin/php-cs-fixer fix
./vendor/bin/phpcbf -p --standard=ruleset.xml src
./vendor/bin/php-cs-fixer fix
```

**Webserver recommendation:**
- https://wampserver.aviatechno.net/ (Windows only)
- https://www.apachefriends.org/index.html (Windows, Linux and macOS)

**IDE/Editor recommendation:**
- https://www.jetbrains.com/phpstorm/ (Windows, Linux and macOS)
- https://code.visualstudio.com/ (Windows, Linux and macOS)

**Git-Client recommendation:**
- https://desktop.github.com/ (Windows and macOS)

**Recommendet approach for proper versioning with Git:**
1. Checkout the repo into a folder of your choice
2. Download the source code of the dev-branch as zip file and install it on Joomla
3. Remove the installed component folders within your Joomla installation
   - administrator/components/com_joomgallery
   - components/com_joomgallery
   - media/com_joomgallery
   - plugins/finder/joomgallerycategories
   - plugins/finder/joomgalleryimages
   - plugins/privacy/joomgalleryimages
   - plugins/webservices/joomgallery
4. Create symbolic links from those folders to the corresponding folders within the checked out copy of the repository
5. The referenced copy of your component can be properly versioned using Git

**Symbolic link generator tool for windows:**
https://schinagl.priv.at/nt/hardlinkshellext/linkshellextension.html

<hr>

## Testing

Testing is an integrate part of the development process and can be done even without coding skills. Just look at the tab `Pull requests` to see which code changes need testing. If you want to test a code change, this is how to do it:

<video class="ratio ratio-16x9" control="controls"><source src="https://www.joomgalleryfriends.net/images/videos/install-a-pr.mp4" type="video/mp4">Your browser does not support the video tag</video>

1. Open the Pull request (PR) you want to test
2. Scroll down the comments feed of the PR all the way to the bottom
3. Open the accordion **All checks have passed**
4. Open the link **PR Build Artifact / build-pr-zip**
5. Change on the left navigation to **Summary**
6. Click the link in the **Artifacts** section which has the name "JoomGallery-PRname"
7. A zip file of the PR gets created and downloaded
8. Install the zip file in your Joomla!
9. Perform tests where the PR changes anything
10. If you find a bug or unexpected behaviour, post a comment in the pull request with the following content:

### Issue reporting

#### Steps to reproduce the issue
List the steps to perform in order to reproduce the issue you found
#### Expected result
What would you have expected should have happen?
#### Actual result
What did really happen?
#### System information
- PHP-Version
- Database type and version
- (ImageMagick version)
#### Additional comments
Anything else that you think is important for the developer to fix the issue
