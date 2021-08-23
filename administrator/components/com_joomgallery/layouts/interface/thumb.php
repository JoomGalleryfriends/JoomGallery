<?php
/****************************************************************************************\
**   JoomGallery 3                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2021  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.'); ?>

<?php if($displayData['args']['div']) : ?>
  <div class="<?php echo $displayData['args']['div']; ?>">
<?php endif; ?>

<?php if($displayData['args']['linked']) :
  // Check for link to category
  if(isset($displayData['_config']['catlink']) && $displayData['_config']['catlink'] == 1)
  {
    $link = JRoute::_('index.php?&view=category&catid='.$displayData['obj']->catid);
  }
  else
  {
    $link = JHTML::_('joomgallery.openimage', $displayData['_config']['openimage'], $displayData['obj'], $displayData['args']['type'], $displayData['group']);
    if($title = JHtml::_('joomgallery.getTitleforATag', $displayData['obj'], false))
    {
      $link .= '" title="'.$title;
    }
  }
?>

  <a href="<?php echo $link; ?>" class="jg_catelem_photo">
<?php endif; ?>

<?php $class = '';
      $extra = '';
?>

<?php if($displayData['args']['class'])
  {
    $class = ' '.$displayData['args']['class'];
  }
  if($displayData['args']['extra'])
  {
    $extra = ' '.$displayData['args']['extra'];
  }
?>

<img src="<?php echo $displayData['thumb_url']; ?>" class="jg_photo<?php echo $class; ?>" alt="<?php echo $displayData['obj']->imgtitle . $extra; ?>"/>

<?php if($displayData['args']['linked']) : ?>
  </a>
<?php endif; ?>

<?php if($displayData['args']['div']) : ?>
  </div>
<?php endif; ?>
