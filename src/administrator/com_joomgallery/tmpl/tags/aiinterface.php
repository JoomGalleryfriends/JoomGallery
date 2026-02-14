<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin');

$filter_options = ['formSelector' => '#tagsForm', 'filterButton' => false, 'filtersHidden' => true];

?>
<div class="jg jg-tags-aiinterface">
  <form action="<?php echo Route::_('index.php?option=com_joomgallery&view=tags&layout=aiinterface&tmpl=component'); ?>" method="post"
      name="tagsForm" id="tagsForm">
    <div class="row">
      <div class="col-md-12">
        <div id="j-main-container" class="j-main-container">
          <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => $filter_options]); ?>
          <div>
            <p>Tagging: AI Interface Layout</p>
            <ul>
              <?php foreach($this->items as $i => $item) : ?>
                <li><?php echo $this->escape($item->title); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <input type="hidden" name="task" value=""/>
          <input type="hidden" name="form_submited" value="1"/>
          <?php echo HTMLHelper::_('form.token'); ?>
        </div>
      </div>
    </div>
  </form>
</div>
