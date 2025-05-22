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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Button\PublishedButton;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin')
   ->useScript('com_joomgallery.catBtns')
   ->useScript('table.columns')
   ->useScript('multiselect');

$user      = $this->app->getIdentity();
$userId    = $user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $this->getAcl()->checkACL('editstate', 'com_joomgallery');
$saveOrder = ($listOrder == 'a.lft' && strtolower($listDirn) == 'asc');

if($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=categories.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=categories'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
        <?php if (empty($this->items)) : ?>
          <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
          </div>
        <?php else : ?>
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
                <td class="w-1 text-center">
                  <?php echo HTMLHelper::_('grid.checkall'); ?>
                </td>
                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                </th>
                <th scope="col" class="w-1 text-center">
                  <?php echo Text::_('COM_JOOMGALLERY_IMAGE') ?>
                </th>
                <th scope="col" class="w-1 text-center">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" style="min-width:100px">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_SHOW_PARENT_CATEGORY_LABEL', 'a.parent_id', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo Text::_('COM_JOOMGALLERY_IMAGES'); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_OWNER', 'a.created_by', $listDirn, $listOrder); ?>
                </th>
                <?php if (Multilanguage::isEnabled()) : ?>
                  <th scope="col" class="w-10 d-none d-md-table-cell">
                    <?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                  </th>
                <?php endif; ?>
                <th scope="col" class="w-3 d-none d-lg-table-cell"> 
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_FIELD_ID_LABEL', 'a.id', $listDirn, $listOrder); ?>
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
            <tbody <?php if ($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
              <?php foreach ($this->items as $i => $item) :
                $ordering   = ($listOrder == 'a.ordering');
                $canEdit    = $this->getAcl()->checkACL('edit', _JOOM_OPTION.'.category.'.$item->id);
                $canChange  = $this->getAcl()->checkACL('editstate', _JOOM_OPTION.'.category.'.$item->id);
                $canCheckin = $user->authorise('core.admin', 'com_checkin') || $item->checked_out == $userId || is_null($item->checked_out);

                // Get the parents of item for sorting
								if ($item->level > 1)
								{
									$parentsStr = '';
									$_currentParentId = $item->parent_id;
									$parentsStr = ' ' . $_currentParentId;
									for ($i2 = 0; $i2 < $item->level; $i2++)
									{
										foreach ($this->ordering as $k => $v)
										{
											$v = implode('-', $v);
											$v = '-' . $v . '-';
											if (strpos($v, '-' . $_currentParentId . '-') !== false)
											{
												$parentsStr .= ' ' . $k;
												$_currentParentId = $k;
												break;
											}
										}
									}
								}
								else
								{
									$parentsStr = '';
								}
              ?>
              <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->parent_id; ?>"
                data-item-id="<?php echo $item->id ?>" data-parents="<?php echo $parentsStr ?>"
                data-level="<?php echo $item->level ?>">

                <td class="text-center">
                  <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                </td>

                <td class="text-center d-none d-md-table-cell">
                  <?php
                  $iconClass = '';
                  if (!$canChange)
                  {
                    $iconClass = ' inactive';
                  }
                  elseif (!$saveOrder)
                  {
                    $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                  }
                  ?>
                  <span class="sortable-handler<?php echo $iconClass ?>">
                    <span class="icon-ellipsis-v"></span>
                  </span>
                  <?php if ($canChange && $saveOrder) : ?>
                    <input type="text" name="order[]" size="5" value="<?php echo $item->lft; ?>" class="hidden">
                  <?php endif; ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <?php if(!empty($item->thumbnail)) : ?>
                    <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($item->thumbnail, 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                  <?php endif; ?>
                </td>

                <td class="category-status text-center">
                  <?php 
                    $options = [
                      'task_prefix' => 'categories.',
                      'disabled' => !$canChange,
                      'id' => 'state-' . $item->id
                    ];

                    echo (new PublishedButton)->render((int) $item->published, $i, $options); 
                  ?>
                </td>

                <th scope="row" class="has-context">
                    <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                    <?php if (isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
                      <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'categories.', $canCheckin); ?>
                    <?php endif; ?>
                    <?php if ($canEdit) : ?>
                      <a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id='.(int) $item->id); ?>">
                        <?php echo $this->escape($item->title); ?>
                      </a>
                    <?php else : ?>
                      <?php echo $this->escape($item->title); ?>
                    <?php endif; ?>
                    <div class="small">
                        <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                        <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                    </div>
                    <?php if ($item->hidden === 1) : ?>
                      <div class="small">
                        <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
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
                <?php if($item->img_count > 0) : ?>
                  <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&view=images&filter[category]='.$item->id); ?>">
                    <span class="badge bg-info"><?php echo (int) $item->img_count; ?></span>
                  </a>
                <?php else : ?>
                  <span class="badge bg-info">0</span>
                <?php endif; ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <?php echo $item->access; ?>
                </td>
                <td class="small d-none d-md-table-cell">
                  <?php if ($item->created_by) : ?>
                    <a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->created_by_id); ?>">
                      <?php echo $this->escape($item->created_by); ?>
                    </a>
                  <?php else : ?>
                    <?php echo Text::_('JNONE'); ?>
                  <?php endif; ?>
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
        <?php endif; ?>
				<input type="hidden" name="task" value=""/>
				<input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="form_submited" value="1"/>
        <input type="hidden" id="del_force" name="del_force" value="0"/>
				<input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>

<?php if($this->deleteBtnJS) : ?>
  <script>
    <?php echo $this->deleteBtnJS; ?>
  </script>
<?php endif; ?>
