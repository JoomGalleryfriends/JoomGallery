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

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

echo JHtml::_('bootstrap.startAccordion', 'categoryOptions', array('active' => 'collapse0'));
$i = 0;
foreach($this->fieldSets as $name => $fieldSet):
  if($name != ''):
    echo JHtml::_('bootstrap.addSlide', 'categoryOptions', JText::_($fieldSet->label), 'collapse'.$i++);
      if(isset($fieldSet->description) && trim($fieldSet->description)):
        echo '<p class="tip">'.$this->escape(JText::_($fieldSet->description)).'</p>';
      endif;
      foreach($this->form->getFieldset($name) as $field): ?>
          <div class="control-group">
            <div class="control-label">
              <?php echo $field->label; ?>
            </div>
            <div class="controls">
              <?php echo $field->input; ?>
            </div>
          </div>
<?php endforeach;
    echo JHtml::_('bootstrap.endSlide');
  endif;
endforeach;
echo JHtml::_('bootstrap.endAccordion');