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

<ul>

<?php //Title ?>
<?php if($displayData['interface']->getConfig('showtitle') || $displayData['interface']->getConfig('showpicasnew')) : ?>
  <li>
    <?php if($displayData['interface']->getConfig('showtitle')) : ?>
      <b><?php echo $displayData['obj']->imgtitle; ?></b>
    <?php endif; ?>

    <?php if($displayData['interface']->getConfig('showpicasnew')) : ?>
      <?php echo JoomHelper::checkNew($displayData['obj']->imgdate, $displayData['_jg_config']->get('jg_daysnew')); ?>
    <?php endif; ?>
  </li>
<?php endif; ?>

<?php //Author ?>
<?php if($displayData['interface']->getConfig('showauthor')) : ?>
  <?php
    if($displayData['obj']->imgauthor)
    {
      $authorowner = $displayData['obj']->imgauthor;
    }
    else
    {
      $authorowner = JHTML::_('joomgallery.displayname', $displayData['obj']->owner);
    }
  ?>
  <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_AUTHOR_VAR', $authorowner); ?></li>
<?php endif; ?>

<?php //Category ?>
<?php if($displayData['interface']->getConfig('showcategory')) : ?>
  <li>
    <?php if($displayData['interface']->getConfig('showcatlink')) : ?>
      <?php
        $catlink = '<a href="'.JRoute::_('index.php?view=category&catid='.$displayData['obj']->catid).'">'.$displayData['obj']->cattitle.'</a>';
        echo JText::sprintf('COM_JOOMGALLERY_COMMON_CATEGORY_VAR',$catlink);
      ?>
    <?php else : ?>
      <?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_CATEGORY_VAR',$displayData['obj']->cattitle); ?>
    <?php endif; ?>
  </li>
<?php endif; ?>

<?php //Hits ?>
<?php if ($displayData['interface']->getConfig('showhits')) : ?>
  <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_HITS_VAR', $displayData['obj']->hits);?></li>
<?php endif; ?>

<?php //Downloads ?>
<?php if ($displayData['interface']->getConfig('showdownloads')) : ?>
  <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_DOWNLOADS_VAR', $displayData['obj']->downloads);?></li>
<?php endif; ?>

<?php //Rating ?>
<?php if ($displayData['interface']->getConfig('showrate')) : ?>
  <li><?php echo JHTML::_('joomgallery.rating', $displayData['obj'], false, 'jg_starrating_cat');?></li>
<?php endif; ?>

<?php //Date ?>
<?php if ($displayData['interface']->getConfig('showimgdate')) : ?>
  <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_UPLOAD_DATE', JHTML::_('date', $displayData['obj']->imgdate, JText::_($displayData['interface']->getConfig('dateformat'))));?></li>
<?php endif; ?>

<?php //Nmb. comments ?>
<?php if ($displayData['interface']->getConfig('shownumcomments')) : ?>
  <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_COMMENTS_VAR', $displayData['obj']->cmtcount);?></li>
<?php endif; ?>

<?php //Description ?>
<?php if ($displayData['interface']->getConfig('showdescription') && $displayData['obj']->imgtext) : ?>
  <?php if ($displayData['interface']->getConfig('showdescriptionintrotext') == 1) : ?>
    <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_DESCRIPTION_VAR', JoomHelper::getIntrotext($displayData['obj']->imgtext));?></li>
  <?php else : ?>
    <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_DESCRIPTION_VAR', JoomHelper::getFulltext($displayData['obj']->imgtext));?></li>
  <?php endif; ?>
<?php endif; ?>

<?php //Comment Date ?>
<?php if ($displayData['interface']->getConfig('showcmtdate') == 1 && !is_null($displayData['obj']->cmtdate)) : ?>
  <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_COMMENTS_LASTDATE', JHTML::_('date', $displayData['obj']->cmtdate, JText::_($displayData['interface']->getConfig('dateformat'))));?></li>
<?php endif; ?>

<?php //Comment Text ?>
<?php if ($displayData['interface']->getConfig('showcmttext') == 1 && !is_null($displayData['obj']->cmtdate)) : ?>
  <?php //Comment username
    if ($displayData['obj']->cmtuserid != 0)
    {
      $cmtname = JHTML::_('joomgallery.displayname', $displayData['obj']->cmtuserid);
    }
    else
    {
      $cmtname = $displayData['obj']->cmtname;
    }
  ?>

  <?php //Comment text ?>
  <li><?php echo JText::sprintf('COM_JOOMGALLERY_COMMON_COMMENT_WITH_AUTHOR', $cmtname, $displayData['obj']->cmttext); ?></li>
<?php endif; ?>

<?php //onJoomAfterDisplayThumb
  $results  = $displayData['_mainframe']->triggerEvent('onJoomAfterDisplayThumb', array($displayData['obj']->id));
  echo trim(implode('', $results));
?>

</ul>
