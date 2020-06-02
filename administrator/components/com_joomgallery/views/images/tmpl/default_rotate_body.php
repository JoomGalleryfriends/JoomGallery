<?php defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');

$optionsImageTypes = array(JHtml::_('select.option', 1, JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTIONS_THUMBS_DETAILS')),
                           JHtml::_('select.option', 2, JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTIONS_ORIGINALS'))
                          );

$optionsRotationAngle = array(JHtml::_('select.option', 90, JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTION_90')),
                              JHtml::_('select.option', 180, JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTION_180')),
                              JHtml::_('select.option', 270, JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTION_270'))
                             );

?>
<div class="container-fluid">
  <div class="row-fluid">
    <div class="control-group span3">
      <div class="controls">
        <?php echo JHtml::_('select.genericlist',
                            $optionsImageTypes,
                            'rotateimagetypes',
                            null,
                            'value',
                            'text',
                            $selected = 1,
                            $idtag = 'rotateimagetypes'
                           ); ?>
      </div>
    </div>
    <div class="control-group span3">
      <div class="controls">
        <?php echo JHtml::_('select.genericlist',
                            $optionsRotationAngle,
                            'rotateimageangle',
                             null,
                             'value',
                             'text',
                             $selected = 90,
                             $idtag = 'rotateimageangle'
                           ); ?>
      </div>
    </div>
  </div>
  <h4><?php echo JText::_('JGLOBAL_DESCRIPTION'); ?>:</h4>
  <p id="desc1" class=""><?php echo JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTIONS_THUMBS_DETAILS_DESC'); ?></p>
  <p id="desc2" class="hidden"><?php echo JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTIONS_ORIGINALS_DESC'); ?></p>
</div>

<script>
  jQuery(document).ready(function(){
    var rotateimagetypes = jQuery('#rotateimagetypes_chzn .chzn-single span');
    var desc1 = document.getElementById('desc1');
    var desc2 = document.getElementById('desc2');

    rotateimagetypes.on('DOMSubtreeModified',function(){
      if(rotateimagetypes.text() == 'Thumbs and details')
      {
        desc1.classList.remove('hidden');
        desc2.classList.add('hidden');
      }
      else
      {
        desc1.classList.add('hidden');
        desc2.classList.remove('hidden');
      }
    });
  });
</script>
