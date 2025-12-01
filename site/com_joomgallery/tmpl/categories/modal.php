<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/
 
// No direct access 
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Multilanguage;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.list')
   ->useStyle('com_joomgallery.site')
   ->useScript('com_joomgallery.list-view')
   ->useScript('multiselect');

$input       = Factory::getApplication()->input; 
$field       = $input->getCmd('field');
$listOrder   = $this->state->get('list.ordering');
$listDirn    = $this->state->get('list.direction');
$catRequired = (int) $input->get('required', 0, 'int');
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=categories&layout=modal&tmpl=component'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
        <?php if (!$catRequired) : ?>
          <div>
            <button type="button" class="btn btn-primary button-select" data-category-value="1" data-category-title="<?php echo $this->escape('Root'); ?>" data-category-field="<?php echo $this->escape($field); ?>">
              <?php echo Text::_('COM_JOOMGALLERY_NO_CATEGORY'); ?>
            </button>
          </div>
        <?php endif; ?>
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<div class="clearfix"></div>
        <div class="table-responsive">
          <table class="table table-striped" id="categoryList">
            <caption class="visually-hidden">
							<?php echo Text::_('COM_JOOMGALLERY_CATEGORY_TABLE_CAPTION'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
            <thead>
              <tr>
                <th scope="col" class="w-1 text-center">
                  <?php echo Text::_('JSTATUS'); ?>
                </th>               
                <th scope="col" style="min-width:100px">
                  <?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_PARENT_CATEGORY', 'a.parent_id', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo Text::_('COM_JOOMGALLERY_IMAGES'); ?>
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
                    <?php if ($item->published === 1) : ?>
                      <span class="badge bg-secondary">
                        <?php echo Text::_('JPUBLISHED'); ?>
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary">
                        <?php echo Text::_('JUNPUBLISHED'); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <th scope="row" class="has-context">
                    <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                    <a class="pointer button-select" href="#" data-category-value="<?php echo (int) $item->id; ?>" data-category-title="<?php echo $this->escape($item->title); ?>" data-category-field="<?php echo $this->escape($field); ?>">
                      <?php echo $this->escape($item->title); ?>
                    </a>

                    <?php if ($item->hidden === 1) : ?>
                      <div class="small">
                        <span class="badge bg-secondary">
                          <?php echo Text::_('COM_JOOMGALLERY_HIDDEN'); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                </th>

                <td class="d-none d-md-table-cell">
                  <?php echo ($item->parent_title == 'Root') ? '' : $item->parent_title; ?>
                </td>

                <td class="d-none d-md-table-cell">
                  <span class="badge bg-info"><?php echo (int) $item->img_count; ?></span>
                </td>

                <?php if (Multilanguage::isEnabled()) : ?>
                  <td class="small d-none d-md-table-cell">
                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                  </td>
                <?php endif; ?>
                <td>
                  <?php echo $item->id; ?>
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
