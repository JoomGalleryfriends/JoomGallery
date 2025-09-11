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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;

// Uppy config
$uppy_version = 'v3.5.0'; // Uppy version to use

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate')
//   ->useStyle('com_joomgallery.admin')
   ->useStyle('com_joomgallery.site')
;

HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

$categoryTitle = $this->categoryTitle;
$replaceType =$app->input->get('type');
?>

<div class="jg jg-img-replace">
  <form
    action="<?php echo Route::_('index.php?option=com_joomgallery&layout='.$layout.$tmpl); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="image-form" class="form-validate form-horizontal"
    aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD', true); ?>" >

    <div class="row align-items-start">

    <\div>

    <div class="row align-items-start">
      <div class="col-xxl-auto col-md-6 mb">
        <div class="card">
          <div class="card-header">
            <h2><?php echo Text::_('COM_JOOMGALLERY_REPLACE'); ?></h2>
          </div>
          <div class="card-body">
<!--            --><?php //echo $this->form->renderField('replacetype'); ?>
            <?php echo $this->form->renderField('replacetype_disabled','',$replaceType); ?>
            <?php echo $this->form->renderField('replaceprocess'); ?>
            <?php echo $this->form->renderField('image'); ?>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card">
          <div class="card-header">
            <h2><?php echo Text::_('COM_JOOMGALLERY_IMAGE'); ?></h2>
          </div>
          <div class="card-body">
            <?php echo $this->form->renderField('title'); ?>
            <?php echo $this->form->renderField('alias'); ?>
            <?php echo $this->form->renderField('id'); ?>
<!--            --><?php //echo $this->form->renderField('catid'); ?>
            <?php echo $this->form->renderField('catid_disabled','',$categoryTitle);
            ?>
          </div>
        </div>
      </div>
    </div>

    <input type="hidden" name="id" value="<?php echo $this->item->id; ?>"/>
    <input type="hidden" name="layout" value="replace"/>
    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
</div>
