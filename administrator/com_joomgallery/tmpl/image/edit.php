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

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useStyle('com_joomgallery.admin');
HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();
$form      = $this->getForm();
$fieldSets = $form->getFieldsets();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<form
	action="<?php echo Route::_('index.php?option=com_joomgallery&layout='.$layout.$tmpl.'&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="image-form" class="form-validate"
  aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" >

  <div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-4">
      <?php echo $this->form->renderField('title'); ?>
    </div>
    <div class="col-12 col-md-4">
      <?php echo $this->form->renderField('alias'); ?>
    </div>
    <div class="col-12 col-md-4">
      <?php echo $this->form->renderField('image'); ?>
			<?php echo $this->form->renderField('filename'); ?>
    </div>
  </div>

  <div class="main-card">
	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Details', 'recall' => true, 'breakpoint' => 768)); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Details', Text::_('JDETAILS', true)); ?>	
  <div class="row">
		<div class="col-lg-9">
			<fieldset class="adminform">
        <?php echo $this->form->getLabel('description'); ?>
				<?php echo $this->form->getInput('description'); ?>
			</fieldset>
		</div>
    <div class="col-lg-3">
      <fieldset class="form-vertical">
        <legend class="visually-hidden"><?php echo Text::_('JGLOBAL_FIELDSET_GLOBAL'); ?></legend>
        <?php echo $this->form->renderField('published'); ?>
				<?php echo $this->form->renderField('catid'); ?>
        <?php echo $this->form->renderField('featured'); ?>
        <?php echo $this->form->renderField('hidden'); ?>
        <?php echo $this->form->renderField('access'); ?>
        <?php echo $this->form->renderField('tags'); ?>
				<?php echo $this->form->renderField('language'); ?>
      </fieldset>
    </div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

  <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Images', Text::_('COM_JOOMGALLERY_IMAGES', true)); ?>
	<div class="row">
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-images" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_PREVIEW'); ?></legend>
        <div class="text-center joom-image center">
          <div class="joom-loader"><img src="<?php echo Uri::root(true); ?>/media/system/images/ajax-loader.gif" alt="loading..."></div>
          <img src="<?php echo JoomHelper::getImg($this->item, 'thumbnail'); ?>" class="img-thumbnail" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
        </div>
        <div class="text-center">
          <div class="btn-group joom-imgtypes" role="group" aria-label="<?php echo Text::_('COM_JOOMGALLERY_SHOWIMAGE_LBL'); ?>">
            <?php foreach($this->imagetypes as $key => $imagetype) : ?>
              <a class="btn btn-outline-primary" style="cursor:pointer;" onclick="openModal('<?php echo $imagetype->typename; ?>')"><?php echo Text::sprintf('COM_JOOMGALLERY_SHOWIMAGE_IMGTYPE', \ucfirst($imagetype->typename)); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mt-5"><?php echo $this->form->renderField('filesystem'); ?></div>
			</fieldset>
    </div>
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-images-data" class="options-form">
        <legend><?php echo Text::_('INFO'); ?></legend>
        <div>
          <?php echo $this->form->renderField('author'); ?>
          <?php echo $this->form->renderField('date'); ?>
          <?php echo $this->form->renderField('hits'); ?>
          <?php echo $this->form->renderField('downloads'); ?>
          <?php echo $this->form->renderField('votes'); ?>
          <?php echo $this->form->renderField('rating'); ?>
        </div>          
      </fieldset>
    </div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

  <?php foreach ($fieldSets as $name => $fieldSet) :?>
    <?php if (strpos($name,'fields-') !== 0) continue; ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', $name, Text::_($fieldSet->label)); ?>
    <?php $this->fieldset = $name; ?>
    <?php echo LayoutHelper::render('joomla.edit.fieldset', $this); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
  <?php endforeach; ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
	<div class="row">
    <div class="col-12 col-lg-6">
			<fieldset id="fieldset-publishingdata" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
        <div>
          <?php echo $this->form->renderField('approved'); ?>
          <?php echo $this->form->renderField('created_time'); ?>
          <?php echo $this->form->renderField('created_by'); ?>
          <?php echo $this->form->renderField('modified_time'); ?>
          <?php echo $this->form->renderField('modified_by'); ?>
          <?php echo $this->form->renderField('id'); ?>
        </div>				
			</fieldset>
    </div>
    <div class="col-12 col-lg-6">
			<fieldset id="fieldset-metadata" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
        <div>
          <?php echo $this->form->renderField('metadesc'); ?>
				  <?php echo $this->form->renderField('metakey'); ?>
				  <?php echo $this->form->renderField('robots'); ?>
        </div>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'DisplayParams', Text::_('COM_JOOMGALLERY_PARAMETERS', true)); ?>
	<div class="row">
    <div class="col-12 <?php echo ($this->state->params->get('save_history', 1)) ? 'col-lg-6' : ''; ?>">
      <fieldset id="fieldset-images-params" class="options-form">
				<legend><?php echo Text::_('COM_JOOMGALLERY_PARAMETERS'); ?></legend>
        <div class="control-group">
          <div class="controls"><?php echo $this->form->getInput('params'); ?></div>
        </div>
			</fieldset>
    </div>
    <?php if ($this->state->params->get('save_history', 1)) : ?>
      <div class="col-12 col-lg-6">
        <fieldset id="fieldset-images-version" class="options-form">
          <legend><?php echo Text::_('JVERSION'); ?></legend>
          <?php echo $this->form->renderField('version_note'); ?>
        </fieldset>
		  </div>
    <?php endif; ?>
	</div>  
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

  <?php if($this->item->id) : ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Metadata', Text::_('COM_JOOMGALLERY_METADATA', true)); ?>
    <div class="row">
      <div class="col-12">
        <fieldset id="fieldset-images-metadata" class="options-form">
          <legend><?php echo Text::_('COM_JOOMGALLERY_METADATA'); ?></legend>
          <div class="control-group">
            <div class="controls"><?php echo $this->form->getInput('imgmetadata'); ?></div>
          </div>
        </fieldset>
      </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
  <?php endif; ?>
  <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value=""/>
  <input type="hidden" name="jform[uploader]" value="html" />
  <?php /* <input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
	<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
	<input type="hidden" name="jform[votes]" value="<?php echo $this->item->votes; ?>" />
	<input type="hidden" name="jform[useruploaded]" value="<?php echo $this->item->useruploaded; ?>" /> */ ?>
	<?php echo HTMLHelper::_('form.token'); ?>

</form>

<?php
// Image preview modal
$options = array('modal-dialog-scrollable' => true,
                  'title'  => 'Test Title',
                  'footer' => '<a id="replaceBtn" class="btn" href="'.Route::_('index.php?option=com_joomgallery&view=image&layout=replace&id='.(int) $this->item->id).'">'.Text::_('COM_JOOMGALLERY_REPLACE').'</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_('JCLOSE').'</button>',
                );

echo HTMLHelper::_('bootstrap.renderModal', 'image-modal-box', $options, '<div id="modal-body">Content set by ajax.</div>');
?>

<script>
  function openModal(typename)
	{
    let modal = document.getElementById('image-modal-box');

    let modalTitle = modal.querySelector('.modal-title');
    let modalBody  = modal.querySelector('.modal-body');
    let modalBtn   = document.getElementById('replaceBtn');

    <?php
      $imgURL   = '{';
      $title = '{';

      foreach($this->imagetypes as $key => $imagetype)
      {
        $imgURL   .= $imagetype->typename.':"'.JoomHelper::getImg($this->item, $imagetype->typename).'",';
        $title .= $imagetype->typename.':"'.Text::_('COM_JOOMGALLERY_'.strtoupper($imagetype->typename)).'",';
      }

      $imgURL .= '}';
      $title .= '}';
    ?>
    let imgURL   = <?php echo $imgURL; ?>;
    let title = <?php echo $title; ?>;

    modalTitle.innerHTML = title[typename];
    let body  = '<div class="joom-image center">'
    body      = body + '<div class="joom-loader"><img src="<?php echo Uri::root(true); ?>/media/system/images/ajax-loader.gif" alt="loading..."></div>';
    body      = body + '<img src="' + imgURL[typename] + '" alt="' + title[typename] + '">';
    body      = body + '</div>';
    modalBody.innerHTML  = body;

    modalBtn.href = modalBtn.href + '&type=' + typename;

    let bsmodal = new bootstrap.Modal(document.getElementById('image-modal-box'), {keyboard: false});
    bsmodal.show();
	};
</script>
