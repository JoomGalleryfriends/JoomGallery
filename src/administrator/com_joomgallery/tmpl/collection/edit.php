<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

\defined('_JEXEC') || die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$this->document->getWebAssetManager()->useScript('keepalive')->useScript('form.validate')->useScript('core')->useStyle('com_joomgallery.admin');
?>
<form action="<?php echo Route::_('index.php?option=com_joomgallery'); ?>" method="post"
      name="adminForm" id="adminForm" class="form-validate"
      aria-label="<?php echo Text::_('COM_JOOMGALLERY_COLLECTION_NEW'); ?>">
  <div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('title'); ?>
    </div>
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('alias'); ?>
    </div>
  </div>

  <div class="main-card">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'collectionTab', ['active' => 'Details', 'recall' => true, 'breakpoint' => 768]); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'collectionTab', 'Details', Text::_('JDETAILS', true)); ?>
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
          <?php echo $this->form->renderField('userid'); ?>
          <?php echo $this->form->renderField('published'); ?>
          <?php echo $this->form->renderField('access'); ?>
        </fieldset>
      </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
  </div>
  <input type="hidden" name="task" value="collection.save">
  <?php echo HTMLHelper::_('form.token'); ?>
</form>
