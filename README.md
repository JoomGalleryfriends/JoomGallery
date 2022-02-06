# JoomGallery v4.x (Development)

Development branch of the JoomGallery component for Joomla! 4.

**Project-Website:**
https://www.en.joomgalleryfriends.net/

**Support-Forum:**
https://www.forum.joomgalleryfriends.net

## Want to contribute?

JoomGallery is an OpenSource project and is developed by users for users. So if you are using JoomGallery feel free to contribute to the project...

## Codestyle guide
PHP: [Codestyle guide for PHP](docs/codestyleguide.md)

## Setup development environment

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
1. Checkout the dev-branch into a folder of your choice
2. Download the source code of the dev-branch as zip file and install it on Joomla
3. Remove the installed component folders within your Joomla installation
   - administrator/components/com_joomgallery
   - components/com_joomgallery
   - media/com_joomgallery
   - plugins/finder/joomgallerycategories
   - plugins/finder/joomgalleryimages
   - plugins/privacy/joomgalleryimages
   - plugins/privacy/webservices/joomgallery
4. Create symbolic links from those folders to the corresponding folders within the checked out copy of your component
5. Remove the installed component language files within your Joomla installation
   - administrator/language/en-GB/com_joomgallery.ini
   - administrator/language/en-GB/com_joomgallery.exif.ini
   - administrator/language/en-GB/com_joomgallery.iptc.ini
   - administrator/language/en-GB/com_joomgallery.sys.ini
   - language/en-GB/com_joomgallery.ini
6. Create symbolic links from those files to the corresponding files within the checked out copy of your component
7. The referenced copy of your component can be properly versioned using Git

**Symbolic link generator tool for windows:**
https://schinagl.priv.at/nt/hardlinkshellext/linkshellextension.html

## Task areas during development

| Taks area | Description |
| ----------- | ----------- |
| PHP Devloper | Programming the functionalities of the JoomGallery (Framework, MVC pattern, Services, Helpers, ...) |
| Frontend Designer | Creating the template files for the component views in the frontend (Template: Cassiopeia) |
| Backend Designer | Creating the template files for the component views in the backend (Template: Atum) |
| Language Manager | Setting up, structuring and managing the language files for frontend and backend. |
| Documentation | Writing instructions for the support section of the website. |
| Testing | Testing the new code before merging them into the main project. |
