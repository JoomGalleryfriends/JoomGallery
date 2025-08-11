<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $description     The description which could contain a tip.
 * @var   string   $id              The id of the description.
 * @var   string   $class           Class for the collapse tip div.
 * @var   bool     $small           True, if the collapse should be rendered in a small tag.
 */

  $tip = null;
  if(!empty($description) && strpos($description, '{tip}') !== false)
  {
    $desc_arr    = explode('{tip}',$description);
    $description = $desc_arr[0];
    $tip         = $desc_arr[1];
  }

  if(!isset($class))
  {
    $class = null;
  }

  $tag = 'div';
  if(!empty($small) && $small === true)
  {
    $tag = 'small';
  }

  if (empty($class)){
    $class = '';
  }
?>

<?php echo $description; ?>
<?php if(!empty($tip)) : ?>
  <a data-bs-toggle="collapse" href="#collapseTip_<?php echo $id; ?>" role="button" aria-expanded="false" aria-controls="collapseTip_<?php echo $id; ?>">
    <?php echo Text::_('COM_JOOMGALLERY_FIELDS_TIP_MORE'); ?>
  </a>
<?php endif; ?>

<?php if(!empty($tip)) : ?>
  <br />
  <<?php echo $tag; ?> id="collapseTip_<?php echo $id; ?>" class="collapse <?php echo $class; ?>">
    <?php echo $tip; ?>
  </<?php echo $tag; ?>>
<?php endif; ?>
