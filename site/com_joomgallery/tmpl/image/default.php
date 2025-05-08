<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
******************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// image params
$image_type        = $this->params['configs']->get('jg_detail_view_type_image', 'detail', 'STRING');
$show_title        = $this->params['configs']->get('jg_detail_view_show_title', 0, 'INT');
$show_category     = $this->params['configs']->get('jg_detail_view_show_category', 0, 'INT');
$show_description  = $this->params['configs']->get('jg_detail_view_show_description', 0, 'INT');
$show_imgdate      = $this->params['configs']->get('jg_detail_view_show_imgdate', 0, 'INT');
$show_imgauthor    = $this->params['configs']->get('jg_detail_view_show_imgauthor', 0, 'INT');
$show_created_by   = $this->params['configs']->get('jg_detail_view_show_created_by', 0, 'INT');
$show_votes        = $this->params['configs']->get('jg_detail_view_show_votes', 0, 'INT');
$show_rating       = $this->params['configs']->get('jg_detail_view_show_rating', 0, 'INT');
$show_hits         = $this->params['configs']->get('jg_detail_view_show_hits', 0, 'INT');
$show_downloads    = $this->params['configs']->get('jg_detail_view_show_downloads', 0, 'INT');
$show_tags         = $this->params['configs']->get('jg_detail_view_show_tags', 0, 'INT');
$show_metadata     = $this->params['configs']->get('jg_detail_view_show_metadata', 0, 'INT');

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');

// Access check
$canEdit    = $this->getAcl()->checkACL('edit', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);
$canDelete  = $this->getAcl()->checkACL('delete', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);
$canCheckin = $this->getAcl()->checkACL('editstate', 'com_joomgallery.image', $this->item->id, $this->item->catid, true) || $this->item->checked_out == $this->getUser()->id;
$returnURL  = base64_encode(JoomHelper::getViewRoute('image', $this->item->id, $this->item->catid, $this->item->language, $this->getLayout()));

// Tags
$tagLayout = new FileLayout('joomgallery.content.tags');
$tags = $tagLayout->render($this->item->tags);

// Metadata
$metadataLayout = new FileLayout('joomgallery.content.metadata');
$metadata = $metadataLayout->render($this->item->imgmetadata);

// Custom Fields
$fields = FieldsHelper::getFields('com_joomgallery.image', $this->item);
?>

<?php if ($show_title) : ?>
  <h2><?php echo $this->item->title; ?></h2>
<?php endif; ?>

<a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->catid); ?>">
  <i class="jg-icon-arrow-left-alt"></i><span><?php echo Text::_('COM_JOOMGALLERY_IMAGE_BACK_TO_CATEGORY') . ' ' . $this->item->cattitle; ?></span>
</a>

</br />
</br />

<?php if($canEdit || $canCheckin || $canDelete): ?>
  <div class="mb-3">
    <?php if ($canCheckin && $this->item->checked_out > 0): ?>
      <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.checkin&id='.$this->item->id.'&return='.$returnURL.'&'.Session::getFormToken().'=1'); ?>">
        <?php echo Text::_("JLIB_HTML_CHECKIN"); ?>
      </a>
    <?php endif; ?>

    <?php if ($canEdit): ?>
      <a class="btn btn-outline-primary<?php echo ($this->item->checked_out > 0) ? ' disabled' : ''; ?>" href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.edit&id='.$this->item->id.'&return='.$returnURL); ?>" <?php echo ($this->item->checked_out > 0) ? 'disabled' : ''; ?>>
        <i class="jg-icon-edit"></i><span><?php echo Text::_("JGLOBAL_EDIT"); ?></span>
      </a>
    <?php endif; ?>

    <?php if ($canDelete) : ?>
      <a class="btn btn-danger<?php echo ($this->item->checked_out > 0) ? ' disabled' : ''; ?>" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal" <?php echo ($this->item->checked_out > 0) ? 'disabled' : ''; ?>>
        <i class="jg-icon-delete"></i><span><?php echo Text::_("JACTION_DELETE"); ?></span>
      </a>

      <?php echo HTMLHelper::_(
                                'bootstrap.renderModal',
                                'deleteModal',
                                array(
                                    'title'  => Text::_('JACTION_DELETE'),
                                    'height' => '50%',
                                    'width'  => '20%',

                                    'modalWidth'  => '50',
                                    'bodyHeight'  => '100',
                                    'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_joomgallery&task=image.remove&id='.$this->item->id.'&return='.$returnURL.'&'.Session::getFormToken().'=1', false, 2) .'" class="btn btn-danger">' . Text::_('COM_JOOMGALLERY_COMMON_DELETE_IMAGE_TIPCAPTION') .'</a>'
                                ),
                                Text::_('COM_JOOMGALLERY_COMMON_ALERT_SURE_DELETE_SELECTED_ITEM')
                              );
      ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php // Image ?>
<figure class="figure joom-image text-center center">
  <div id="jg-loader"></div>
  <img src="<?php echo JoomHelper::getImg($this->item, $image_type); ?>" class="figure-img img-fluid rounded" alt="<?php echo $this->item->title; ?>" style="width:auto;" itemprop="image" loading="lazy">
  <?php if ($show_description) : ?>
    <figcaption class="figure-caption"><?php echo $this->item->description; ?></figcaption>
  <?php endif; ?>
</figure>

<?php // Image info and fields ?>
<div class="item_fields">
  <h3><?php echo Text::_('COM_JOOMGALLERY_IMAGE_INFO'); ?></h3>
    <table class="table">
      <tr>
        <?php if ($show_category) : ?>
          <tr>
            <th><?php echo Text::_('JCATEGORY'); ?></th>
            <td>
              <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->catid); ?>">
                <?php echo $this->escape($this->item->cattitle); ?>
              </a>
            </td>
          </tr>
        <?php endif; ?>
        <?php if ($show_imgdate) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_DATE'); ?></th>
            <td><?php echo HTMLHelper::_('date', $this->item->date, Text::_('DATE_FORMAT_LC4')); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_imgauthor) : ?>
          <tr>
            <th><?php echo Text::_('JAUTHOR'); ?></th>
            <td><?php echo $this->escape($this->item->author); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_created_by) : ?>
        <?php $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->item->created_by); ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_OWNER'); ?></th>
            <td><?php echo $this->escape($user->name); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_votes) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_VOTES'); ?></th>
            <td><?php echo $this->escape($this->item->votes); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_rating) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_IMAGE_RATING'); ?></th>
            <td><?php echo $this->escape($this->item->rating); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_hits) : ?>
          <tr>
            <th><?php echo Text::_('JGLOBAL_HITS'); ?></th>
            <td><?php echo (int) $this->item->hits; ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_downloads) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_DOWNLOADS'); ?></th>
            <td><?php echo (int) $this->item->downloads; ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_tags) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_TAGS'); ?></th>
            <td><?php echo $tags; ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_metadata) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?></th>
            <td><?php echo $metadata; ?></td>
          </tr>
        <?php endif; ?>
        <?php if (count($fields) > 0) : ?>
          <tr>
            <th><strong><?php echo Text::_('JGLOBAL_FIELDS'); ?></strong></th>
            <td></td>
          </tr>
          <?php foreach ($fields as $key => $field) : ?>
            <?php if($this->component->getAccess()->checkViewLevel($field->access) && $field->params->get('display') > 0) : ?>
              <tr class="<?php echo $field->params->get('render_class'); ?>">
                <th class="<?php echo $field->params->get('label_render_class'); ?>"><?php if($field->params->get('showlabel', true)) echo $this->escape($field->title); ?></th>
                <td class="<?php echo $field->params->get('value_render_class'); ?>"><?php echo $this->escape($field->value); ?></td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

<script>
window.onload = function() {
  const el = document.querySelector('#jg-loader');
  el.classList.add('hidden'); 
};
</script>
