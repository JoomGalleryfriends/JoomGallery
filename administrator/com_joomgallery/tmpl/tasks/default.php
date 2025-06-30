<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Button\PublishedButton;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin')
   ->useScript('multiselect');

$user      = $this->app->getIdentity();
$userId    = $user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $this->getAcl()->checkACL('editstate', 'com_joomgallery');
$saveOrder = ($listOrder == 'a.ordering' && strtolower($listDirn) == 'asc');

if($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=tasks.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
  HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=tasks'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
			  <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<div class="clearfix"></div>

        <h2>Instant Tasks</h2>
        <?php if (empty($this->items['instant'])) : ?>
          <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('No instant Tasks'); ?>
          </div>
        <?php else : ?>
          <?php foreach ($this->items['instant'] as $key => $item): ?>
            <div class="row align-items-start">
              <div class="col-md-12">
                <div class="card">
                  <h3 class="card-header"><?php echo Text::_('FILES_JOOMGALLERY_TASK_TITLE') . $item->task; ?></h3>
                  <div class="card-body">
                    <div class="badge-group mb-3">
                      <span class="badge bg-secondary"><?php echo Text::_('COM_JOOMGALLERY_PENDING'); ?>: <span id="badgeQueue-<?php echo $item->id; ?>"><?php echo count($item->queue); ?></span></span>
                      <span class="badge bg-success"><?php echo Text::_('COM_JOOMGALLERY_SUCCESSFUL'); ?>: <span id="badgeSuccessful-<?php echo $item->id; ?>"><?php echo count($item->successful); ?></span></span>
                      <span class="badge bg-danger"><?php echo Text::_('COM_JOOMGALLERY_FAILED'); ?>: <span id="badgeFailed-<?php echo $item->id; ?>"><?php echo count($item->failed); ?></span></span>
                    </div>
                    <div class="progress mb-2">
                      <div id="progress-<?php echo $item->id; ?>" class="progress-bar" style="width: <?php echo $item->progress; ?>%" role="progressbar" aria-valuenow="<?php echo $item->progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php if($item->progress > 0){echo $item->progress.'%';}; ?></div>
                    </div>
                    <a class="collapse-arrow mb-2" data-bs-toggle="collapse" href="#collapseLog-<?php echo $item->id; ?>" role="button" aria-expanded="false" aria-controls="collapseLog">
                      <i class="icon-angle-down"></i><span> <?php echo Text::_('COM_JOOMGALLERY_SHOWLOG'); ?></span>
                    </a>
                    <div class="collapse mt-2" id="collapseLog-<?php echo $item->id; ?>">
                      <div id="logOutput-<?php echo $item->id; ?>" class="card card-body border bg-light log-area">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <br><hr><br>
        <h2>Planned Tasks</h2>
        <?php if (empty($this->items['instant'])) : ?>
          <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('No planned Tasks'); ?>
          </div>
        <?php else : ?>
          <?php foreach ($this->items['planned'] as $key => $item): ?>
            <div class="row align-items-start">
              <div class="col-md-12">
                <div class="card">
                  <h3 class="card-header"><?php echo Text::_('FILES_JOOMGALLERY_TASK_TITLE') . $item->task; ?></h3>
                  <div class="card-body">
                    <div class="badge-group mb-3">
                      <span class="badge bg-secondary"><?php echo Text::_('COM_JOOMGALLERY_PENDING'); ?>: <span id="badgeQueue-<?php echo $item->id; ?>"><?php echo count($item->queue); ?></span></span>
                      <span class="badge bg-success"><?php echo Text::_('COM_JOOMGALLERY_SUCCESSFUL'); ?>: <span id="badgeSuccessful-<?php echo $item->id; ?>"><?php echo count($item->successful); ?></span></span>
                      <span class="badge bg-danger"><?php echo Text::_('COM_JOOMGALLERY_FAILED'); ?>: <span id="badgeFailed-<?php echo $item->id; ?>"><?php echo count($item->failed); ?></span></span>
                    </div>
                    <div class="progress mb-2">
                      <div id="progress-<?php echo $item->id; ?>" class="progress-bar" style="width: <?php echo $item->progress; ?>%" role="progressbar" aria-valuenow="<?php echo $item->progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php if($item->progress > 0){echo $item->progress.'%';}; ?></div>
                    </div>
                    <a class="collapse-arrow mb-2" data-bs-toggle="collapse" href="#collapseLog-<?php echo $item->id; ?>" role="button" aria-expanded="false" aria-controls="collapseLog">
                      <i class="icon-angle-down"></i><span> <?php echo Text::_('COM_JOOMGALLERY_SHOWLOG'); ?></span>
                    </a>
                    <div class="collapse mt-2" id="collapseLog-<?php echo $item->id; ?>">
                      <div id="logOutput-<?php echo $item->id; ?>" class="card card-body border bg-light log-area">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

				<input type="hidden" name="task" value=""/>
				<input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="form_submited" value="1"/>
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
