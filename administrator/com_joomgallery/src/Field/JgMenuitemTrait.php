<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\Layout\FileLayout;
use \Joomla\CMS\Application\ApplicationHelper;

/**
* Trait to make Field available in Menuitem params
*
* @since  4.0.0
*/
trait JgMenuitemTrait
{
  /**
   * Allow to override renderer include paths in child fields
   *
   * @return  array
   *
   * @since   3.5
   */
  protected function getLayoutPaths()
  {
    $renderer = new FileLayout('default');

    // Get the template
    $app       = Factory::getApplication();
    $component = ApplicationHelper::getComponentName();

    // Reset includePaths
    $paths = array();

    if($component !== 'com_joomgallery')
    {
      if($app->isClient('site') || $app->isClient('administrator'))
      {
        // Try to get a default template
        $template = $app->getTemplate(true);
      }
      else
      {
        // Template not found
        $template = false;
      }

      // (1) Component template overrides path
      if($template)
      {
        $paths[] = JPATH_THEMES . '/' . $template->template . '/html/layouts/com_joomgallery';

        if(!empty($template->parent))
        {
          // (1.a) Component template overrides path for an inherited template using the parent
          $paths[] = JPATH_THEMES . '/' . $template->parent . '/html/layouts/com_joomgallery';
        }
      }

      // (2) Component path
      if($app->isClient('site'))
      {
        $paths[] = JPATH_SITE . '/components/com_joomgallery/layouts';
      }
      else
      {
        $paths[] = JPATH_ADMINISTRATOR . '/components/com_joomgallery/layouts';
      }
    }

    $paths = \array_merge($paths, $renderer->getDefaultIncludePaths());

    return $paths;
  }
}
