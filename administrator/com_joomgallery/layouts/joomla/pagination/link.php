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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$item       = $displayData['data'];
$display    = $item->text;
$app        = Factory::getApplication();
$span_class = '';

switch((string) $item->text)
{
  // Check for "Start" item
  case Text::_('JLIB_HTML_START'):
    $icon = $app->getLanguage()->isRtl() ? 'icon-angle-double-right' : 'icon-angle-double-left';
    $aria = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
      break;

  // Check for "Prev" item
  case $item->text === Text::_('JPREV'):
    $item->text = Text::_('JPREVIOUS');
    $icon       = $app->getLanguage()->isRtl() ? 'icon-angle-right' : 'icon-angle-left';
    $aria       = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
    $span_class = ' previous';
      break;

  // Check for "Next" item
  case Text::_('JNEXT'):
    $icon       = $app->getLanguage()->isRtl() ? 'icon-angle-left' : 'icon-angle-right';
    $aria       = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
    $span_class = ' next';
      break;

  // Check for "End" item
  case Text::_('JLIB_HTML_END'):
    $icon = $app->getLanguage()->isRtl() ? 'icon-angle-double-left' : 'icon-angle-double-right';
    $aria = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
      break;

  default:
    $icon = null;
    $aria = Text::sprintf('JLIB_HTML_GOTO_PAGE', strtolower($item->text));
      break;
}

if($icon !== null)
{
  $display = '<span class="' . $icon . '" aria-hidden="true"></span>';
}

if($displayData['active'])
{
  if($item->base > 0)
  {
    $limit = 'limitstart.value=' . $item->base;
  }
  else
  {
    $limit = 'limitstart.value=0';
  }

  $class = 'active';

  if($app->isClient('administrator'))
  {
    $link = 'href="#" onclick="document.adminForm.' . $item->prefix . $limit . '; Joomla.submitform();return false;"';
  }
  elseif($app->isClient('site'))
  {
    $link = 'href="' . $item->link . '"';
  }
}
else
{
  $class = (property_exists($item, 'active') && $item->active) ? 'active' : 'disabled';
}

?>
<?php if($displayData['active']) : ?>
  <li class="page-item">
    <a aria-label="<?php echo $aria; ?>" <?php echo $link; ?> class="page-link<?php echo $span_class; ?>">
      <?php echo $display; ?>
    </a>
  </li>
<?php elseif(isset($item->active) && $item->active) : ?>
  <?php $aria = Text::sprintf('JLIB_HTML_PAGE_CURRENT', strtolower($item->text)); ?>
  <li class="<?php echo $class; ?> page-item">
    <a aria-current="true" aria-label="<?php echo $aria; ?>" href="#" class="page-link<?php echo $span_class; ?>"><?php echo $display; ?></a>
  </li>
<?php else : ?>
  <li class="<?php echo $class; ?> page-item">
    <span class="page-link<?php echo $span_class; ?>" aria-hidden="true"><?php echo $display; ?></span>
  </li>
<?php endif; ?>
