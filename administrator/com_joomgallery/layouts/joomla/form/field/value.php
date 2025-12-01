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

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $class           Classes for the input.
 * @var   boolean  $hidden          Is this field hidden in the form?
 * @var   string   $id              DOM id of the field.
 * @var   string   $value           Value attribute of the field.
 */

 $class  = empty($class) ? '' : $class;
 $hidden = empty($hidden) ? '' : $hidden;
 $id     = empty($id) ? '' : $id;
 $value  = empty($value) ? '' : $value;
?>

  <span id="<?php echo $id; ?>" class="<?php echo $class; ?> <?php if($hidden){echo 'hidden';}; ?>">
    <?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>
  </span>
