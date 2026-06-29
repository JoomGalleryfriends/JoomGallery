<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
   ->useScript('form.validate')
   ->useScript('com_joomgallery.ftp-import')
   ->useStyle('com_joomgallery.admin');
HTMLHelper::_('bootstrap.tooltip');

Text::script('JGLOBAL_VALIDATION_FORM_FAILED');
Text::script('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS');
Text::script('COM_JOOMGALLERY_COMMON_ALERT_YOU_MUST_SELECT_CATEGORY');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_DIRECTORY');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_NO_FILES');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_NO_FILES_SELECTED');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_PROCESSING');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_DONE');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_FAILED');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_LOADING');
Text::script('COM_JOOMGALLERY_FTP_IMPORT_LOAD_FAILED');

$wa->addInlineScript(
    "
    document.addEventListener('DOMContentLoaded', function () {
      const title = document.getElementById('jform_title');
      const numbering = document.getElementById('jform_nmb_start');

      if (!title || !numbering) {
        return;
      }

      const field = numbering.closest('.control-group, .mb-3, .form-group') || numbering.parentElement;
      const toggleNumbering = function () {
        const hasTitle = title.value.trim().length > 0;

        numbering.disabled = !hasTitle;

        if (field) {
          field.hidden = !hasTitle;
        }
      };

      title.addEventListener('input', toggleNumbering);
      toggleNumbering();
    });
    "
);
$this->form->setFieldAttribute('title', 'required', 'false');
?>

<div class="jg jg-upload jg-ftp-import">
  <form
    action="<?php echo Route::_('index.php?option=com_joomgallery&controller=image'); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="needs-validation"
    novalidate aria-label="<?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT', true); ?>">

    <div class="row align-items-start">
      <div class="col-md-7 mb">
        <div class="card" id="ftp-import-area"
             data-list-url="<?php echo Route::_('index.php?option=com_joomgallery&task=image.ftplist&format=json'); ?>"
             data-save-url="<?php echo Route::_('index.php?option=com_joomgallery&controller=image'); ?>">
          <div class="card-header">
            <h2><?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT'); ?></h2>
          </div>
          <div class="card-body">
            <p class="small text-muted mb-3" id="ftp-import-path"></p>
            <div class="mb-3">
              <button type="button" class="btn btn-secondary btn-sm" id="ftp-import-refresh">
                <?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_REFRESH'); ?>
              </button>
              <button type="button" class="btn btn-secondary btn-sm" id="ftp-import-select-all">
                <?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_SELECT_ALL'); ?>
              </button>
              <button type="button" class="btn btn-secondary btn-sm" id="ftp-import-select-none">
                <?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_SELECT_NONE'); ?>
              </button>
            </div>
            <div class="mb-3">
              <label for="ftp-import-action" class="form-label">
                <?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_SOURCE_ACTION'); ?>
              </label>
              <select id="ftp-import-action" class="form-select">
                <option value="keep"><?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_KEEP'); ?></option>
                <option value="delete"><?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_DELETE'); ?></option>
                <option value="move"><?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_MOVE'); ?></option>
              </select>
            </div>
            <div class="mb-3">
              <label for="ftp-import-limit" class="form-label">
                <?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_BATCH_SIZE'); ?>
              </label>
              <select id="ftp-import-limit" class="form-select">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="150">150</option>
              </select>
            </div>
            <div class="mb-3">
              <button type="button" class="btn btn-primary" id="ftp-import-start" disabled>
                <?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_START'); ?>
              </button>
            </div>
            <div class="progress mb-3" role="progressbar" aria-label="<?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_PROGRESS', true); ?>">
              <div class="progress-bar" id="ftp-import-progress" style="width: 0%">0%</div>
            </div>
            <div id="ftp-import-status" class="small mb-3"></div>
            <div class="table-responsive">
              <table class="table table-striped table-sm">
                <thead>
                  <tr>
                    <th scope="col"><span class="visually-hidden"><?php echo Text::_('JSELECT'); ?></span></th>
                    <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                    <th scope="col"><?php echo Text::_('COM_JOOMGALLERY_FILESIZE'); ?></th>
                    <th scope="col"><?php echo Text::_('JDATE'); ?></th>
                    <th scope="col"><?php echo Text::_('JSTATUS'); ?></th>
                  </tr>
                </thead>
                <tbody id="ftp-import-files">
                  <tr>
                    <td colspan="5"><?php echo Text::_('COM_JOOMGALLERY_FTP_IMPORT_LOADING'); ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
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
                'small'       => true,
              ];
              $renderer    = new FileLayout('joomgallery.tip');
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
          <input type="text" id="jform_id" class="hidden form-control readonly" name="jform[id]" value="" readonly>
        </div>
      </div>
    </div>

    <input type="hidden" name="task" value="image.ajaxsave">
    <input type="hidden" id="jform_uploader" name="jform[uploader]" value="ftp">
    <input type="hidden" name="jform[multiple]" value="1">
    <input type="hidden" id="jform_ftp_file" name="jform[ftp_file]" value="">
    <input type="hidden" id="jform_ftp_action" name="jform[ftp_action]" value="keep">
    <?php if($this->config->get('jg_useorigfilename')): ?>
      <input type="hidden" name="jform[title]" value="title">
    <?php endif; ?>
    <input type="hidden" name="id" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <div id="popup-area"></div>
</div>
