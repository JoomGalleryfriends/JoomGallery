<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

?>

<div class="row-fluid">
  <div class="offset2 span8 well">
    <div class="alert alert-info">
      <?php echo Text::_('COM_JOOMGALLERY_COMMON_REFRESHER_PLEASE_WAIT'); ?>
      <?php if($displayData['name']): ?>
        <br />
        <?php echo Text::sprintf('COM_JOOMGALLERY_COMMON_REFRESHER_CURRENT_TASK', '<span style="color:green;">'.$displayData['name'].'</span>'); ?>
      <?php endif; ?>
    </div>

    <p class="center"><img src="<?php echo Uri::root(); ?>media/system/images/modal/spinner.gif" alt="Spinner" width="16" height="16" /></p>

    <?php if($displayData['showprogress']): ?>
      <div class="progress progress-striped active" title="<?php echo Text::sprintf('COM_JOOMGALLERY_COMMON_REFRESHER_PROGRESSBAR', $displayData['maxtime']); ?>">
        <div class="bar"></div>
      </div>
      <div class="small muted center"><?php echo Text::sprintf('COM_JOOMGALLERY_COMMON_REFRESHER_PROGRESSBAR', $displayData['maxtime']); ?></div>
    <?php endif; ?>
  </div>
</div>
