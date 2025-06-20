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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useScript('com_joomgallery.uppy-uploader')
   ->useScript('bootstrap.modal')
   ->useStyle('com_joomgallery.uppy')
   ->useStyle('com_joomgallery.admin');
HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

// Add language strings to JavaScript
Text::script('JCLOSE');
Text::script('JAUTHOR');
Text::script('JGLOBAL_TITLE');
Text::script('JGLOBAL_DESCRIPTION');
Text::script('JGLOBAL_VALIDATION_FORM_FAILED');
Text::script('COM_JOOMGALLERY_UPLOADING');
Text::script('COM_JOOMGALLERY_SAVING');
Text::script('COM_JOOMGALLERY_WAITING');
Text::script('COM_JOOMGALLERY_DEBUG_INFORMATION'); 
Text::script('COM_JOOMGALLERY_FILE_TITLE_HINT');
Text::script('COM_JOOMGALLERY_FILE_DESCRIPTION_HINT');
Text::script('COM_JOOMGALLERY_FILE_AUTHOR_HINT');
Text::script('COM_JOOMGALLERY_SUCCESS_UPPY_UPLOAD');
Text::script('COM_JOOMGALLERY_ERROR_UPPY_UPLOAD');
Text::script('COM_JOOMGALLERY_ERROR_UPPY_FORM');
Text::script('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD');
Text::script('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS');

$wa->addInlineScript('window.uppyVars = JSON.parse(\''. json_encode($this->js_vars) . '\');', ['position' => 'before'], [], ['com_joomgallery.uppy-uploader']);
?>

<div class="jg jg-upload">
  <form
    action="<?php echo Route::_('index.php?option=com_joomgallery&controller=image'); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="needs-validation"
    novalidate aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD', true); ?>" >

    <div class="row align-items-start">
      <div class="col-md-6 mb"> 
        <div class="card">
          <div class="card-header">
            <h2><?php echo Text::_('COM_JOOMGALLERY_IMAGE_SELECTION'); ?></h2>
          </div>
          <div id="drag-drop-area">
            <div class="card-body"><?php echo Text::_('COM_JOOMGALLERY_INFO_UPLOAD_FORM_NOT_LOADED'); ?></div>
          </div>
          <hr />
          <div class="card-body">
            <?php echo $this->form->renderField('debug'); ?>
          </div>
          <div>
            <?php DisplaySystemSettings($this->uploadLimit, $this->postMaxSize, $this->memoryLimit, $this->configSize, $this->maxSize); ?>
          </div>
        </div>
      </div>
      <div class="col card">
        <div class="card-header">
          <h2><?php echo Text::_('JOPTIONS'); ?></h2>
        </div>
        <div class="card-body">
          <p>
            <?php
              $displayData = [
                  'description' => Text::_('COM_JOOMGALLERY_GENERIC_UPLOAD_DATA'),
                  'id'          => 'adminForm-desc',
                  'small'       => true
              ];
              $renderer = new FileLayout('joomgallery.tip');
            ?>
            <?php echo $renderer->render($displayData); ?>
          </p>
          <?php echo $this->form->renderField('catid'); ?>
          <?php if(!$this->config->get('jg_useorigfilename')): ?>
            <?php echo $this->form->renderField('title'); ?>
            <?php if($this->config->get('jg_filenamenumber')): ?>
              <?php echo $this->form->renderField('nmb_start'); ?>
            <?php endif; ?>
          <?php endif; ?>
          <?php echo $this->form->renderField('author'); ?>
          <?php echo $this->form->renderField('published'); ?>
          <?php echo $this->form->renderField('access'); ?>
          <?php echo $this->form->renderField('language'); ?>
          <fieldset class="adminform">
            <?php echo $this->form->getLabel('description'); ?>
            <?php echo $this->form->getInput('description'); ?>
          </fieldset>
          <input type="text" id="jform_id" class="hidden form-control readonly" name="jform[id]" value="" readonly/>
        </div>
      </div>
    </div>

    <input type="hidden" name="task" value="image.ajaxsave"/>
    <input type="hidden" name="jform[uploader]" value="tus" />
    <input type="hidden" name="jform[multiple]" value="1" />
    <?php if($this->config->get('jg_useorigfilename')): ?>
      <input type="hidden" name="jform[title]" value="title" />
    <?php endif; ?>
    <input type="hidden" name="id" value="0" />
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <div id="popup-area"></div>
</div>

<?php
/**
 * Display system settings as collapsed
 *
 * Parameter: limits in megabytes, created in viewhtml.php
 * @param   int  $UploadLimit  php setting 'upload_max_filesize'
 * @param   int  $PostMaxSize  php setting 'post_max_size'
 * @param   int  $MemoryLimit  php setting 'memory_limit'
 * @param   int  $configSize   upload limit by joomgallery configuraion
 * @param   int  $maxSize      Min of above
 *
 * @since 4.1.0
 */
function DisplaySystemSettings($UploadLimit, $PostMaxSize, $MemoryLimit, $configSize, $maxSize)
{
  $title  = Text::sprintf('COM_JOOMGALLERY_POST_MAX_SIZE_IS', $maxSize);
  $id     = 127000;
  $itemId = 127001;
  ?>

  <div class="card">
    <div class="accordion" id="<?php echo $id; ?>">
      <div class="accordion-item">
        <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                  data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
            <?php echo Text::_($title); ?>
          </button>
        </h2>
        <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
             aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
          <div class="accordion-body">
            <table class="table table-striped">
              <thead>
                <tr>&nbsp;</tr>
                <tr>&nbsp;</tr>
              </thead>
              <tbody>
                <tr>
                  <td class="d-md-table-cell">
                    <?php echo Text::sprintf('COM_JOOMGALLERY_UPLOAD_UPLOAD_LIMIT_IS', $UploadLimit); ?>
                  </td>
                  <td class="d-md-table-cell">
                    <strong><?php echo $UploadLimit; ?></strong>&nbsp;MB ('upload_max_filesize')"
                  </td>
                </tr>
                <tr>
                  <td class="d-md-table-cell">
                    <?php echo Text::sprintf('COM_JOOMGALLERY_UPLOAD_POST_MAX_SIZE_IS', $PostMaxSize); ?>
                  </td>
                  <td class="d-md-table-cell">
                    <strong><?php echo $PostMaxSize; ?></strong>&nbsp;MB ('post_max_size')
                  </td>
                </tr>
                <tr>
                  <td class="d-md-table-cell">
                    <?php echo Text::sprintf('COM_JOOMGALLERY_UPLOAD_POST_MEMORY_LIMIT_IS', $MemoryLimit); ?>
                  </td>
                  <td class="d-md-table-cell">
                    <strong><?php echo $MemoryLimit; ?></strong>&nbsp;MB ('memory_limit')
                  </td>
                </tr>
                <tr>
                  <td class="d-md-table-cell">
                    <?php echo Text::sprintf('COM_JOOMGALLERY_UPLOAD_CONFIG_LIMIT_IS', $configSize); ?>
                  </td>
                  <td class="d-md-table-cell">
                    <strong><?php echo $configSize; ?></strong>&nbsp;MB
                  </td>
                </tr>

              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php return;
}
?>
