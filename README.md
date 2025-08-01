# JoomGallery

<img src="https://www.joomgalleryfriends.net/images/modulbilder/logo-joomgalleryfriends.svg" width="700" title="JoomGalleryfriends Logo">

This is the repository of the JoomGallery component for Joomla! 4 and newer.

**Project-Website:**
https://www.joomgalleryfriends.net/

**Support-Forum:**
https://www.forum.joomgalleryfriends.net

**Non-binding roadmap (DE):**
https://www.forum.joomgalleryfriends.net/forum/index.php?thread/483-unverbindliche-roadmap/&postID=2880#post2880

**Project Presentation (DE):**
https://docs.google.com/presentation/d/1kXGfGRrHswU0M3yh0zUvOwW1fYqB07cksHzNxzcEyxI

## Translation

<a href="https://joomla.crowdin.com/joomgallery" rel="nofollow"><img style="width:140;height:40px" src="https://badges.crowdin.net/badge/dark/crowdin-on-light@2x.png" alt="Translated with Crowdin" /></a>

[![Crowdin](https://badges.crowdin.net/e/a74b79c0af70ca6f1c1e610704f1c44c/localized.svg)](https://joomla.crowdin.com/joomgallery)

## Want to donate?

JoomGallery is an OpenSource project and is developed by users for users. So if you are using JoomGallery feel free to contribute to the project...

[![](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2TBYDQ88VH4PW)

## Literature

The JoomGallery component is developed following the current Joomla! 4+ Paradigm. Following you find some online resources explaining them.

### Joomla 4 extension development docs
- [Robbies video series on Joomla 4 component development](https://www.youtube.com/playlist?list=PLzio09PZm6TuXGnu-ptpVb90Szkawy9IV)
- [Official Joomla! Programmers Documentation](https://manual.joomla.org/)
- [Official Joomla! 4 component development docs](https://docs.joomla.org/J4.x:Developing_an_MVC_Component/Introduction)
- [Nicholas book on Joomla 4 development](https://www.dionysopoulos.me/book.html)
- [Mattermost channel for Joomla developers](https://joomlacommunity.cloud.mattermost.com/main/channels/extension-development-room)

<hr>

## Contribute code

Just like the CMS Joomla!, the gallery component JoomGallery is an Open Source project and exists because of its community. Which means that everything in the project is developed and maintained by users of the JoomGallery.
Therefore we ask you to use your skills and knowledge to contribute to the project. Here on GitHub you can help coding and developing the extension.

Following the JoomGalleries coding documentation:

- [Release-Strategy](docs/Releasestrategy.md)
- [Codestyle guide for PHP](docs/Codestyleguide.md)
- [Contribution guide](/docs/Contribution.md)

### Setup development environment
https://docs.joomla.org/Setting_up_your_workstation_for_Joomla_development

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
4. Create symbolic links from those folders to the corresponding folders within the checked out copy of your component
5. The referenced copy of your component can be properly versioned using Git

**Symbolic link generator tool for windows:**
https://schinagl.priv.at/nt/hardlinkshellext/linkshellextension.html

<hr>

## Testing

Testing is an integrate part of the development process and can be done even without coding skills. Just look at the tab `Pull requests` to see which code changes need testing. If you want to test a code change, this is how to do it:

1. Open the Pull request you want to test
2. Change to the branch where the code of the PR is coming from
3. Button "Code" -> "Download ZIP"
4. Install the zip file in your Joomla!
5. Perform tests where the Pull request changes anything
6. If you find a bug or unexpected behaviour, post a comment in the pull request with the following content:

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
