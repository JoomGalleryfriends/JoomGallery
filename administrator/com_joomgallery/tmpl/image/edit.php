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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
?>

<form
	action="<?php echo Route::_('index.php?option=com_joomgallery&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="image-form" class="form-validate form-horizontal">

	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Details')); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Details', Text::_('JDETAILS', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('JDETAILS'); ?></legend>
				<?php echo $this->form->renderField('imgtitle'); ?>
				<?php echo $this->form->renderField('alias'); ?>
				<?php echo $this->form->renderField('catid'); ?>
				<?php echo $this->form->renderField('published'); ?>
				<?php echo $this->form->renderField('imgauthor'); ?>
				<?php echo $this->form->renderField('language'); ?>
				<?php echo $this->form->renderField('imgtext'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
				<?php echo $this->form->renderField('access'); ?>
				<?php echo $this->form->renderField('hidden'); ?>
				<?php echo $this->form->renderField('featured'); ?>
				<?php echo $this->form->renderField('created_time'); ?>
				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('modified_time'); ?>
				<?php echo $this->form->renderField('modified_by'); ?>
				<?php echo $this->form->renderField('id'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
				<?php echo $this->form->renderField('metadesc'); ?>
				<?php echo $this->form->renderField('metakey'); ?>
				<?php echo $this->form->renderField('robots'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Images', Text::_('COM_JOOMGALLERY_COMMON_IMAGES', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_COMMON_IMAGES'); ?></legend>
        <?php echo $this->form->renderField('image'); ?>
				<?php echo $this->form->renderField('filename'); ?>
        <div class="control-group">
          <div class="control-label">
            <label><?php echo Text::_('COM_JOOMGALLERY_COMMON_IMAGETYPE'); ?></label>
          </div>
          <div class="controls">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th scope="col" style="width:30%"></th>
                    <?php foreach($this->imagetypes as $key => $imagetype) : ?>
                      <th scope="col"><?php echo Text::_('COM_JOOMGALLERY_MAIMAN_TYPE_'.strtoupper($imagetype->type_alias)); ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <img style="max-width:150px;" src="<?php echo JoomHelper::getImg($this->item, 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_MAIMAN_TYPE_'.strtoupper($imagetype->typename)); ?>">
                    </td>
                    <?php foreach($this->imagetypes as $key => $imagetype) : ?>
                      <td data-column="<?php echo Text::_('COM_JOOMGALLERY_MAIMAN_TYPE_'.strtoupper($imagetype->type_alias)); ?>">
                        <a style="cursor:pointer;" onclick="openModal('<?php echo $imagetype->typename; ?>')"><?php echo Text::_('COM_JOOMGALLERY_COMMON_SHOWIMAGE'); ?></a>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA'); ?></legend>
				<?php echo $this->form->renderField('imgdate'); ?>
				<?php echo $this->form->renderField('imgmetadata'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'DisplayParams', Text::_('COM_JOOMGALLERY_COMMON_PARAMETERS', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_COMMON_PARAMETERS'); ?></legend>
				<?php echo $this->form->renderField('params'); ?>
				<?php if ($this->state->params->get('save_history', 1)) : ?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
					</div>
				<?php endif; ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
	<?php /*<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />*/ ?>
	<input type="hidden" name="jform[hits]" value="<?php echo $this->item->hits; ?>" />
	<input type="hidden" name="jform[downloads]" value="<?php echo $this->item->downloads; ?>" />
	<input type="hidden" name="jform[imgvotes]" value="<?php echo $this->item->imgvotes; ?>" />
	<input type="hidden" name="jform[imgvotesum]" value="<?php echo $this->item->imgvotesum; ?>" />
	<input type="hidden" name="jform[approved]" value="<?php echo $this->item->approved; ?>" />
	<input type="hidden" name="jform[useruploaded]" value="<?php echo $this->item->useruploaded; ?>" />

	<?php if (Factory::getUser()->authorise('core.admin','joomgallery')) : ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
		<?php echo $this->form->getInput('rules'); ?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>

</form>

<?php
$options = array('modal-dialog-scrollable' => true,
                  'title'  => 'Test Title',
                  'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_('JCLOSE').'</button>',
                );

echo HTMLHelper::_('bootstrap.renderModal', 'image-modal-box', $options, '<div id="modal-body">Content set by ajax.</div>');
?>

<script>
  function openModal(typename)
	{
    let modal = document.getElementById('image-modal-box');

    let modalTitle = modal.querySelector('.modal-title');
    let modalBody  = modal.querySelector('.modal-body');

    <?php
      $imgURL   = '{';
      $imgTitle = '{';

      foreach($this->imagetypes as $key => $imagetype)
      {
        $imgURL   .= $imagetype->typename.':"'.JoomHelper::getImg($this->item, $imagetype->typename).'",';
        $imgTitle .= $imagetype->typename.':"'.Text::_('COM_JOOMGALLERY_MAIMAN_TYPE_'.strtoupper($imagetype->typename)).'",';
      }

      $imgURL .= '}';
      $imgTitle .= '}';
    ?>
    let imgURL   = <?php echo $imgURL; ?>;
    let imgTitle = <?php echo $imgTitle; ?>;

    modalTitle.innerHTML = imgTitle[typename];
    modalBody.innerHTML = '<img style="max-width:100%" src="' + imgURL[typename] + '" alt="' + imgTitle[typename] + '">';

    let bsmodal = new bootstrap.Modal(document.getElementById('image-modal-box'), {keyboard: false});
    bsmodal.show();


		console.log(typename);
	};
</script>
