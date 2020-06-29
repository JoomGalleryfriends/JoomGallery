<?php defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');

$optionsImageTypes = array(JHtml::_('select.option', 1, JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTIONS_THUMBS_DETAILS')),
                           JHtml::_('select.option', 2, JText::_('COM_JOOMGALLERY_COMMON_ROTATE_OPTIONS_THUMBS_DETAILS_ORIGINALS'))
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
</div>
