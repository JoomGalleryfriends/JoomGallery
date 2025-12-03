<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addRegistryFile('media/com_scheduler/joomla.asset.json');
$wa->useStyle('com_scheduler.admin-view-task-css');
$wa->useScript('com_scheduler.admin-view-select-task-search');

// Load scheduler language file
$this->app->getLanguage()->load('com_scheduler', JPATH_ADMINISTRATOR);

// Create items variable
$this->items = $this->tasks;

// In case no tasks are found
if(empty($this->tasks))
{
  $no_items  = '<div class="alert alert-info">';
  $no_items .=   '<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden">' . Text::_('INFO') . '</span>';
  $no_items .=   ' ' . Text::_('No Tasks found');
  $no_items .= '</div>';

  echo $no_items;

  return;
}

// Load scheduler template
$path = JPATH_ADMINISTRATOR . '/components/com_scheduler/tmpl/select/default.php';
$templateContent = file_get_contents($path);

$templateContent = str_replace(
    'index.php?option=com_scheduler&task=task.add&type=',
    'index.php?option=com_joomgallery&task=task.add&type=',
    $templateContent
);

eval('?>' . $templateContent);
