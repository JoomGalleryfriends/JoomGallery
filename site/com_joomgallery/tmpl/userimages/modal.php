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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.list')
   ->useStyle('com_joomgallery.site')
   ->useScript('com_joomgallery.list-view')
   ->useScript('multiselect');

$input        = Factory::getApplication()->input;
$field        = $input->getCmd('field');
$listOrder    = $this->state->get('list.ordering');
$listDirn     = $this->state->get('list.direction');
$imgRequired  = (int) $input->get('required', 0, 'int');
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=images&layout=modal&tmpl=component'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">

      <?php if (!$imgRequired) : ?>
        <div>
          <button type="button" class="btn btn-primary button-select" data-image-value="0" data-image-title="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_FIELDS_SELECT_IMAGE')); ?>" data-image-field="<?php echo $this->escape($field); ?>">
            <?php echo Text::_('COM_JOOMGALLERY_NO_IMAGE'); ?>
          </button>
        </div>
      <?php endif; ?>

			<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<div class="clearfix"></div>
        <div class="table-responsive">
          <table class="table table-striped" id="imageList">
            <caption class="visually-hidden">
              <?php echo Text::_('COM_JOOMGALLERY_IMAGES_TABLE_CAPTION'); ?>,
              <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
              <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
            </caption>
            <thead>
              <tr>
                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                  <?php echo Text::_('JSTATUS'); ?>
                </th>
                <th scope="col" class="w-1 text-center">
                  <?php // Spaceholder for thumbnail image ?>
                </th>
                <th scope="col" style="min-width:180px">
                  <?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('grid.sort',  'JCATEGORY', 'a.catid', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('grid.sort',  'JAUTHOR', 'a.author', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('grid.sort',  'JDATE', 'a.date', $listDirn, $listOrder); ?>
                </th>
                <?php if (Multilanguage::isEnabled()) : ?>
                  <th scope="col" class="w-10 d-none d-md-table-cell">
                    <?php echo HTMLHelper::_('grid.sort',  'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                  </th>
                <?php endif; ?>
                <th scope="col" class="w-3 d-none d-lg-table-cell">
                  <?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_FIELD_ID_LABEL', 'a.id', $listDirn, $listOrder); ?>
                </th>
              </tr>
            </thead>
            <tfoot>
            <tr>
              <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                <?php echo $this->pagination->getListFooter(); ?>
              </td>
            </tr>
            </tfoot>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
              <tr class="row<?php echo $i % 2; ?>">
                <td class="text-center d-none d-md-table-cell">
                  <div class="small badge-list">
                    <?php if ($item->featured === 1) : ?>
                      <span class="badge bg-secondary">
                        <?php echo Text::_('JFEATURED'); ?>
                      </span>
                    <?php endif; ?>

                    <?php if ($item->published === 1) : ?>
                      <span class="badge bg-secondary">
                        <?php echo Text::_('JPUBLISHED'); ?>
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary">
                        <?php echo Text::_('JUNPUBLISHED'); ?>
                      </span>
                    <?php endif; ?>

                    <?php if ($item->approved === 1) : ?>
                      <span class="badge bg-secondary">
                        <?php echo Text::_('COM_JOOMGALLERY_APPROVED'); ?>
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary">
                        <?php echo Text::_('COM_JOOMGALLERY_UNAPPROVED'); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <td class="small d-none d-md-table-cell">
                  <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($item, 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                </td>

                <th scope="row" class="has-context">
                  <div class="break-word">
                    <a class="pointer button-select" href="#" data-image-value="<?php echo (int) $item->id; ?>" data-image-title="<?php echo $this->escape($item->title); ?>" data-image-field="<?php echo $this->escape($field); ?>">
                      <?php echo $this->escape($item->title); ?>
                    </a>

                    <div class="small">
                      <?php echo Text::_('JCATEGORY') . ': '; ?>
                      <?php echo $this->escape($item->cattitle); ?>
                    </div>

                    <?php if ($item->hidden === 1) : ?>
                      <div class="small">
                        <span class="badge bg-secondary">
                          <?php echo Text::_('COM_JOOMGALLERY_HIDDEN'); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  </div>
                </th>

                <td class="small d-none d-md-table-cell">
                  <?php echo $this->escape($item->cattitle); ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <?php if ($item->author) : ?>
                    <?php echo $this->escape($item->author); ?>
                  <?php else : ?>
                    <?php echo Text::_('COM_JOOMGALLERY_NO_USER'); ?>
                  <?php endif; ?>
                </td>

                <td class="small d-none d-md-table-cell text-center">
                  <?php
                    $date = $item->date;
                    echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                  ?>
                </td>

                <?php if (Multilanguage::isEnabled()) : ?>
                  <td class="small d-none d-md-table-cell">
                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                  </td>
                <?php endif; ?>
                <td class="d-none d-lg-table-cell">
                  <?php echo (int) $item->id; ?>
                </td>

              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
				<input type="hidden" name="task" value=""/>
				<input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="filter_order" value=""/>
        <input type="hidden" name="filter_order_Dir" value=""/>
				<?php echo HTMLHelper::_('form.token'); ?>
			</div> 
		</div>
	</div>
</form>
