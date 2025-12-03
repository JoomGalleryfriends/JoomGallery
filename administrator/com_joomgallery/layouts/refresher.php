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
use Joomla\CMS\Uri\Uri;

?>

<div class="container">
  <div class="row card justify-content-center">
    <div class="col card-body">
      <div class="head">
        <img src="<?php echo Uri::root(false); ?>media/com_joomgallery/images/watermark.png" alt="JoomGallery logo">
      </div>
      <div class="alert alert-info">
        <?php echo Text::_('COM_JOOMGALLERY_SERVICE_PLEASE_WAIT_EXEC'); ?>
        <?php if($displayData['name']): ?>
          <br />
          <?php echo Text::sprintf('COM_JOOMGALLERY_SERVICE_CURRENT_TASK', '<span class="task-name">' . $displayData['name'] . '</span>'); ?>
        <?php endif; ?>
      </div>

      <div class="text-center joom-loader"><img src="<?php echo Uri::root(false); ?>media/system/images/ajax-loader.gif" alt="loading..."></div>

      <?php if($displayData['showprogress']): ?>
        <?php $value = floor((($displayData['total'] - $displayData['remaining']) / $displayData['total']) * 100); ?>
        <div class="progress">
          <div class="progress-bar progress-bar-striped progress-bar-animated" 
               title="<?php echo Text::sprintf('COM_JOOMGALLERY_SERVICE_PROGRESSBAR', $displayData['maxtime']); ?>"
               role="progressbar"
               aria-valuenow="<?php echo $value; ?>"
               aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $value; ?>%">
          </div>
        </div>
        <div class="small text-center text-muted"><?php echo Text::sprintf('COM_JOOMGALLERY_SERVICE_PROGRESSBAR', $displayData['maxtime']); ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>
