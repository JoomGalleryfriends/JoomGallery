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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
  ->useScript('form.validate')
  ->useScript('bootstrap.collapse')
  ->useScript('com_joomgallery.form-edit')
  ->useStyle('com_joomgallery.site');
HTMLHelper::_('bootstrap.tooltip', '.hasTip');

// Load admin language file
// Load admin lang may be useful as edit is a copy of backend items
$lang = Factory::getApplication()->getLanguage();
$lang->load('joomla', JPATH_ADMINISTRATOR);
$lang->load(_JOOM_OPTION . '.exif', JPATH_ADMINISTRATOR . '/components/' . _JOOM_OPTION);
$lang->load(_JOOM_OPTION . '.iptc', JPATH_ADMINISTRATOR . '/components/' . _JOOM_OPTION);

if($this->item->catid)
{
  // ID given -> edit
  $canEdit = $this->getAcl()->checkACL('edit', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);
}
else
{
  // ID = null -> add
  $canEdit = true;
}
$canAdmin = $this->getAcl()->checkACL('admin', 'com_joomgallery');

$config    = $this->params['configs'];
$menuParam = $this->params['menu'];

$isShowTitle = $menuParam->get('showTitle') ?? true;

$app       = Factory::getApplication();
$form      = $this->getForm();
$fieldSets = $form->getFieldsets();

$paramComponent = $this->params['component'];

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

?>

<div class="jg image-edit front-end-edit item-page">
  <?php if(!$canEdit) : ?>
    <?php Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error'); ?>
  <?php else : ?>
    <!--          action="--><?php //echo Route::_('index.php?option=com_joomgallery&controller=userimage&id='.$this->item->id); ?><!--" -->
    <form id="adminForm"
          action="<?php echo Route::_('index.php?option=com_joomgallery&view=userimage&layout=editImg&id=' . $this->item->id); ?>"
          method="post" name="adminForm" class="form-validate form-horizontal" enctype="multipart/form-data"
          aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>"
    >
      <?php if($isShowTitle): ?>
        <h3><?php echo Text::_('COM_JOOMGALLERY_USER_IMAGE_EDIT'); ?></h3>
        <hr>
      <?php endif; ?>

      <div class="form-group">

        <div class="mb-4">
          <button class="btn btn-primary" type="button" data-submit-task="userimage.save">
            <span class="fas fa-save" aria-hidden="true"></span> <?php echo Text::_('JAPPLY'); ?>
          </button>
          <button class="btn btn-primary" type="button" data-submit-task="userimage.saveAndClose">
            <span class="fas fa-save" aria-hidden="true"></span> <?php echo Text::_('JSAVEANDCLOSE'); ?>
          </button>
          <button class="btn btn-danger" type="button" data-submit-task="userimage.cancel">
            <span class="fas fa-times" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?>
          </button>
        </div>

      </div>

      <!--      <fieldset>-->
      <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'Details']); ?>

      <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Details', Text::_('COM_JOOMGALLERY_DETAILS', true)); ?>

      <?php echo $this->form->renderField('title'); ?>
      <?php echo $this->form->renderField('alias'); ?>
      <?php echo $this->form->renderField('published'); ?>
      <?php echo $this->form->renderField('catid'); ?>
      <?php echo $this->form->renderField('featured'); ?>
      <?php echo $this->form->renderField('hidden'); ?>
      <?php echo $this->form->renderField('access'); ?>
      <?php echo $this->form->renderField('tags'); ?>
      <?php echo $this->form->renderField('language'); ?>
      <?php echo $this->form->renderField('description'); ?>

      <?php echo HTMLHelper::_('uitab.endTab'); ?>

      <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Images', Text::_('COM_JOOMGALLERY_IMAGES', true)); ?>

      <div class="row">
        <div class="col-12 col-lg-6">
          <fieldset id="fieldset-images" class="options-form">
            <legend><?php echo Text::_('JGLOBAL_PREVIEW'); ?></legend>
            <div class="text-center joom-image center">
              <div class="joom-loader"><img src="<?php echo Uri::root(true); ?>/media/system/images/ajax-loader.gif"
                                            alt="loading..."></div>
              <img src="<?php echo JoomHelper::getImg($this->item, 'thumbnail'); ?>" class="img-thumbnail"
                   alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
            </div>
            <div class="text-center">
              <div class="btn-group joom-imgtypes" role="group"
                   aria-label="<?php echo Text::_('COM_JOOMGALLERY_SHOWIMAGE_LBL'); ?>">
                <?php if(false): ?>
                  <?php foreach($this->imagetypes as $key => $imagetype) : ?>
                    <a class="btn btn-outline-primary" style="cursor:pointer;"
                       onclick="openModal('<?php echo $imagetype->typename; ?>')"><?php echo Text::sprintf('COM_JOOMGALLERY_SHOWIMAGE_IMGTYPE', ucfirst($imagetype->typename)); ?></a>
                  <?php endforeach; ?>
                <?php endif ?>
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

      <?php foreach($fieldSets as $name => $fieldSet) : ?>
        <?php if(strpos($name, 'fields-') !== 0) continue; ?>
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
        <div class="col-12 <?php echo ($paramComponent->get('save_history', 1)) ? 'col-lg-6' : ''; ?>">
          <fieldset id="fieldset-images-params" class="options-form">
            <legend><?php echo Text::_('COM_JOOMGALLERY_PARAMETERS'); ?></legend>
            <div class="control-group">
              <div class="controls"><?php echo $this->form->getInput('params'); ?></div>
            </div>
          </fieldset>
        </div>
        <?php if($paramComponent->get('save_history', 1)) : ?>
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

        <div class="row form-control">
          <div class="col-12">
            <fieldset id="fieldset-images-metadata" class="options-form">
              <legend><?php echo Text::_('COM_JOOMGALLERY_METADATA'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php echo $this->form->renderField('imgmetadata'); ?></div>
              </div>
              <!--              <div class="control-group">-->
              <!--                <div class="controls">-->
              <?php //echo $this->form->renderField('comment'); ?><!--</div>-->
              <!--              </div>-->
              <!--              <div class="card">-->
              <!--                <div class="card-body">-->
              <!--                  <h5 class="card-title">IPTC</h5>-->
              <!--                  <div class="control-group">-->
              <!--                    <div class="controls">-->
              <?php //echo $this->form->renderField('iptc'); ?><!--</div>-->
              <!--                  </div>-->
              <!--                </div>-->
              <!--              </div>-->
              <!--              <div class="card">-->
              <!--                <div class="card-body">-->
              <!--                  <h5 class="card-title">EXIF</h5>-->
              <!--                  <div class="control-group">-->
              <!--                    <div class="controls">-->
              <?php //echo $this->form->renderField('exif'); ?><!--</div>-->
              <!--                  </div>-->
              <!--                </div>-->
              <!--              </div>-->
            </fieldset>
          </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>
      <?php endif; ?>

      <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

      <input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering ?? ''; ?>"/>
      <input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out ?? ''; ?>"/>
      <input type="hidden" name="jform[hits]" value="<?php echo $this->item->hits ?? ''; ?>"/>
      <input type="hidden" name="jform[downloads]" value="<?php echo $this->item->downloads ?? ''; ?>"/>
      <input type="hidden" name="jform[votes]" value="<?php echo $this->item->votes ?? ''; ?>"/>
      <input type="hidden" name="jform[votesum]" value="<?php echo $this->item->votesum ?? ''; ?>"/>
      <input type="hidden" name="jform[approved]" value="<?php echo $this->item->approved ?? ''; ?>"/>
      <input type="hidden" name="jform[useruploaded]" value="<?php echo $this->item->useruploaded ?? ''; ?>"/>

      <input type="hidden" name="type" id="itemType" value="userimage"/>
      <input type="hidden" name="return" value="<?php echo $this->return_page; ?>"/>
      <input type="hidden" name="task" value=""/>
      <input type="hidden" id="mediaManagerPath" name="mediapath" value=""/>
      <input type="hidden" name="jform[uploader]" value="html"/>
      <?php /* <input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
    <input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
    <input type="hidden" name="jform[votes]" value="<?php echo $this->item->votes; ?>" />
    <input type="hidden" name="jform[useruploaded]" value="<?php echo $this->item->useruploaded; ?>" /> */ ?>
      <?php echo HTMLHelper::_('form.token'); ?>
      <!--      </fieldset>-->


    </form>
  <?php endif; ?>
</div>

<?php
$mediaManagerBtn = '<joomla-toolbar-button><button class="btn disabled" disabled>' . Text::_('COM_JOOMGALLERY_IMAGE_EDIT') . '</button></joomla-toolbar-button>';

if(\in_array(strtolower(pathinfo($this->item->filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
{
  $mediaManagerBtn = '<joomla-toolbar-button id="toolbar-openmedia" task="image.openmedia"><button class="btn btn-secondary hasTip"  disabled="disabled" readonly                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 
     title="' . Text::_('COM_JOOMGALLERY_IMAGE_EDIT_TIP') . '"><s>' . Text::_('COM_JOOMGALLERY_IMAGE_EDIT') . '</s> </button></joomla-toolbar-button>';
}

// Image preview modal
$options = [
  'modal-dialog-scrollable' => true,
  'title'                   => 'Test Title',
  'footer'                  => $mediaManagerBtn
    . '<a id="replaceBtn" class="btn btn-outline-dark hasTip" title="' . Text::_('COM_JOOMGALLERY_IMAGE_REPLACE_TIP')
    . '" href="' . Route::_('index.php?option=com_joomgallery&view=userimage&layout=replace&id=' . (int) $this->item->id) . '">'
    . Text::_('COM_JOOMGALLERY_REPLACE')
    . '</a>'
    . '<button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">'
    . Text::_('JCLOSE')
    . '</button>',
];

echo HTMLHelper::_('bootstrap.renderModal', 'image-modal-box', $options, '<div id="modal-body">Content set by ajax.</div>');
?>

<script>
  function openModal(typename) {
    let modal = document.getElementById('image-modal-box');

    let modalTitle = modal.querySelector('.modal-title');
    let modalBody = modal.querySelector('.modal-body');
    let replaceBtn = document.getElementById('replaceBtn');
    let mediaInput = document.getElementById('mediaManagerPath');

    <?php
    $imgURL   = '{';
    $title    = '{';
    $mediaURL = '{';

    foreach($this->imagetypes as $key => $imagetype)
    {
      $imgURL .= $imagetype->typename . ':"' . JoomHelper::getImg($this->item, $imagetype->typename) . '",';
      $title  .= $imagetype->typename . ':"' . Text::_('COM_JOOMGALLERY_' . strtoupper($imagetype->typename)) . '",';

      $img_path = str_replace('\\', '/', JoomHelper::getImg($this->item, $imagetype->typename, false, false));

      if($this->item->filesystem == 'local-images')
      {
        // Adjust for local file adapter
        $img_path = str_replace('/images/', '/', $img_path);
      }

      $mediaURL .= $imagetype->typename . ':"index.php?option=com_joomgallery&path=' . $this->item->filesystem . ':' . $img_path . '",';
    }

    $imgURL   .= '}';
    $title    .= '}';
    $mediaURL .= '}';
    ?>
    let imgURL = <?php echo $imgURL; ?>;
    let title = <?php echo $title; ?>;
    let mediaURL = <?php echo $mediaURL; ?>;

    modalTitle.innerHTML = title[typename];
    let body = '<div class="joom-image center">'
    body = body + '<div class="joom-loader"><img src="<?php echo Uri::root(true); ?>/media/system/images/ajax-loader.gif" alt="loading..."></div>';
    body = body + '<img src="' + imgURL[typename] + '" alt="' + title[typename] + '">';
    body = body + '</div>';
    modalBody.innerHTML = body;

    replaceBtn.href = replaceBtn.href + '&type=' + typename;
    mediaInput.value = mediaURL[typename];

    let bsmodal = new bootstrap.Modal(document.getElementById('image-modal-box'), {keyboard: false});
    bsmodal.show();
  };
</script>
