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

<?php
  $numcols = $displayData['interface']->getConfig('columns');

  if(!$numcols)
  {
    $numcols = $displayData['interface']->getConfig('default_columns');
    if(!$numcols)
    {
      $numcols = 2;
    }
  }

  $elem_width =  floor(99 / $numcols);

  $rowcount   = 1;
  $itemcount  = 0;
?>

<div class="jg_row jg_row1">

<?php foreach($displayData['rows'] as $row) : ?>

  <?php //start new row ?>
  <?php if(($itemcount % $numcols == 0) && ($itemcount != 0)) : ?>
    </div>

    <div class="jg_row jg_row<?php echo $rowcount % 2 + 1; ?>">

    <?php $rowcount++; ?>
  <?php endif; ?>

  <?php //thumb ?>
  <div class="jg_element_cat" style="width:<?php echo $elem_width; ?>%">
    <?php
      $type = 'img';

      if(   (!is_numeric($displayData['interface']->getConfig('openimage')) || $displayData['interface']->getConfig('openimage') > 0)
        &&  ($displayData['interface']->getJConfig('jg_lightboxbigpic') || $displayData['interface']->getConfig('type') == 'img' || $displayData['interface']->getConfig('type') == 'orig')
        &&  file_exists($displayData['_ambit']->getImg('orig_path', $row))
        )
      {
        $type = 'orig';
      }
    ?>

    <?php //image ?>
    <?php if($displayData['interface']->getConfig('type') == 'img' || $displayData['interface']->getConfig('type') == 'orig') : ?>
      <?php echo $displayData['interface']->displayDetail($row, true, null, 'jg_imgalign_catimgs', null, $type); ?>
    <?php else : ?>
      <?php echo $displayData['interface']->displayThumb($row, true, null, 'jg_imgalign_catimgs', null, $type); ?>
    <?php endif; ?>

    <?php //description ?>
    <?php if(!$displayData['interface']->getConfig('disable_infos')) : ?>
      <div class ="jg_catelem_txt"><?php echo $displayData['interface']->displayDesc($row); ?></div>
    <?php endif; ?>
  </div>

    <?php $itemcount++; ?>
<?php endforeach; ?>

</div>
