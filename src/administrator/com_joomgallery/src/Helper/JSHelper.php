<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

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

    // Parse a specific language file and filter by prefix
    $file = JPATH_BASE . "/components/com_joomgallery/language/{$lang->getTag()}/com_joomgallery.ini";

    $strings = [];

    if(is_file($file))
    {
      $parsed = LanguageHelper::parseIniFile($file);

      foreach($parsed as $key => $value)
      {
        if(str_starts_with($key, $startsWith))
        {
          $strings[$key] = Text::_($key);
        }
      }
    }

    // Pass all matching translated strings to JavaScript
    Factory::getApplication()->getDocument()->addScriptOptions($script . '.lang', $strings);
  }
}
