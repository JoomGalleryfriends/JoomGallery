<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;

/**
 * JoomGallery Helper for JavaScript related methods
 *
 * @static
 * @package JoomGallery
 * @since   4.4.0
 */
class JSHelper
{
  /**
   * Passing multiple Language Constants to JavaScript
   *
   * Example:
   * PHP: JSHelper::registerText('com_joomgallery.script', 'COM_JOOMGALLERY_JS_SCRIPT_');
   * JS:  const lang = Joomla.getOptions('com_joomgallery.script.lang', {});
   *      console.log(lang.COM_JOOMGALLERY_JS_SCRIPT_TEST);
   *
   * @param   string   $script       Asset name of the script (example: com_joomgallery.script)
   * @param   string   $startsWith   Beginning of the Language Constants to be passed.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public static function registerText(string $script, string $startsWith): void
  {
    $lang = Factory::getApplication()->getLanguage();

    // Make sure your extension language file is loaded first.
    // Adjust extension/client/basePath as needed.
    $lang->load('com_joomgallery', JPATH_BASE);

    $strings       = [];
    $languagePath  = JPATH_BASE . '/components/com_joomgallery/language';
    $languageFiles = [
      $languagePath . '/en-GB/com_joomgallery.ini',
    ];

    // Overlay the active translation on the complete English fallback set.
    if($lang->getTag() !== 'en-GB')
    {
      $languageFiles[] = $languagePath . "/{$lang->getTag()}/com_joomgallery.ini";
    }

    foreach($languageFiles as $file)
    {
      if(!is_file($file))
      {
        continue;
      }

      foreach(LanguageHelper::parseIniFile($file) as $key => $value)
      {
        if(str_starts_with($key, $startsWith))
        {
          $strings[$key] = $value;
        }
      }
    }

    // Pass all matching translated strings to JavaScript
    Factory::getApplication()->getDocument()->addScriptOptions($script . '.lang', $strings);
  }
}
