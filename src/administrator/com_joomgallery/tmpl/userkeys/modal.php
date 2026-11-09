<?php
/**
 * @package    com_joomgallery
 * @copyright  2008 - 2026 JoomGallery::ProjectTeam
 * @license    GNU General Public License version 3 or later
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$this->document->getWebAssetManager()
  ->useScript('keepalive')
  ->useScript('form.validate')
  ->useStyle('com_joomgallery.admin');
?>
<div class="container-fluid py-3">
  <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=userkeys.save&tmpl=component'); ?>"
        method="post" id="userkeys-form" class="form-validate">
    <?php echo $this->form->renderFieldset('credentials'); ?>
    <div class="mt-3">
      <button type="submit" class="btn btn-primary">
        <?php echo Text::_('JSAVE'); ?>
      </button>
    </div>
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
</div>
