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
use Joomla\CMS\Router\Route;

$request = $this->request;
$terms   = Text::_((string) $request['terms']);
?>
<div class="com-joomgallery-license container py-4">
  <div class="card mx-auto" style="max-width: 54rem;">
    <div class="card-body p-4">
      <h1 class="card-title h2"><?php echo Text::_('COM_JOOMGALLERY_LICENSE_TERMS_TITLE'); ?></h1>
      <p class="text-muted"><?php echo Text::_('COM_JOOMGALLERY_LICENSE_TERMS_INTRO'); ?></p>

      <div class="border rounded bg-light p-3 my-4">
        <?php echo nl2br(htmlspecialchars($terms, ENT_QUOTES, 'UTF-8')); ?>
      </div>

      <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=image.acceptLicense'); ?>" method="post">
        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" value="1" id="accept-terms" name="accept_terms" required>
          <label class="form-check-label" for="accept-terms">
            <?php echo Text::_('COM_JOOMGALLERY_LICENSE_TERMS_ACCEPT'); ?>
          </label>
        </div>

        <input type="hidden" name="resource" value="<?php echo htmlspecialchars((string) $request['resource'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $request['id']; ?>">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars((string) $request['type'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="purpose" value="<?php echo htmlspecialchars((string) $request['purpose'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="return" value="<?php echo htmlspecialchars((string) ($request['return'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo HTMLHelper::_('form.token'); ?>

        <button type="submit" class="btn btn-primary">
          <span class="icon-check" aria-hidden="true"></span>
          <?php echo Text::_('COM_JOOMGALLERY_LICENSE_TERMS_CONTINUE'); ?>
        </button>
      </form>
    </div>
  </div>
</div>
