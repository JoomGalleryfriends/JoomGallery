<?php
/****************************************************************************************\
**   JoomGallery 3                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2019  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * Renders a thumbnail preview form field
 *
 * @package JoomGallery
 * @since   2.0
 */
class JFormFieldThumbnailpreview extends JFormField
{
  /**
   * The form field type.
   *
   * @var     string
   * @since   2.0
   */
  protected $type = 'Thumbnailpreview';

  /**
   * Returns the HTML for a thumbnail preview form field.
   *
   * @return  object    The thumbnail preview form field.
   * @since   2.0
   */
  protected function getInput()
  {
    // Create attributes
    $class  = $this->element['class'] ? (string) $this->element['class'] : '';
    $class  = !empty($class) ? ' class="'.$class.'"' : '';

    return '<img '.$class.' src="'.$this->value.'" id="'.$this->id.'" name="'.$this->name.'" border="2" alt="'.JText::_('COM_JOOMGALLERY_COMMON_THUMBNAIL_PREVIEW').'">';
  }
}