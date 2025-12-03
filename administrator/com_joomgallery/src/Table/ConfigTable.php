<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Table\Asset\AssetTableTrait;
use \Joomla\CMS\Access\Rules;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Registry\Registry;

/**
 * Config table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigTable extends Table
{
  use JoomTableTrait;
  use AssetTableTrait;

  /**
   * Constructor
   *
   * @param   JDatabase  &$db               A database connector object
   * @param   bool       $component_exists  True if the component object class exists
   */
  public function __construct(DatabaseDriver $db, bool $component_exists = true)
  {
    $this->component_exists = $component_exists;
    $this->typeAlias = _JOOM_OPTION.'.config';

    parent::__construct(_JOOM_TABLE_CONFIGS, 'id', $db);

    $this->setColumnAlias('published', 'published');
  }

  /**
   * Overloaded bind function to pre-process the params.
   *
   * @param   array  $array   Named array
   * @param   mixed  $ignore  Optional array or list of parameters to ignore
   *
   * @return  boolean  True on success.
   *
   * @see     Table:bind
   * @since   4.0.0
   * 
   * @throws  \InvalidArgumentException
   */
  public function bind($array, $ignore = '')
  {
    $date = Factory::getDate();
    $task = Factory::getApplication()->input->get('task', '', 'cmd');

  // Support for title field: title
  if(\array_key_exists('title', $array))
  {
    $array['title'] = trim($array['title']);

    if(empty($array['title']))
    {
    $array['title'] = 'Unknown';
    }
  }

    if($array['id'] == 0 && (!key_exists('created_by', $array) || empty($array['created_by'])))
    {
      $array['created_by'] = Factory::getApplication()->getIdentity()->id;
    }

    if($array['id'] == 0 && empty($array['modified_by']))
    {
      $array['modified_by'] = Factory::getApplication()->getIdentity()->id;
    }

    if($task == 'apply' || strpos($task, 'save') !== false)
    {
      $array['modified_by'] = Factory::getApplication()->getIdentity()->id;
    }

  // Support for multiple field: jg_replaceshowwarning
  $this->multipleFieldSupport($array, 'jg_replaceshowwarning');

    // Support for multiple field: jg_uploadorder
  $this->multipleFieldSupport($array, 'jg_uploadorder');

    // Support for multiple field: jg_delete_original
    $this->multipleFieldSupport($array, 'jg_delete_original');

    // Support for multiple field: jg_imgprocessor
    $this->multipleFieldSupport($array, 'jg_imgprocessor');

    // Support for multiple field: jg_msg_upload_type
    $this->multipleFieldSupport($array, 'jg_msg_upload_type');

    // Support for multiple field: jg_msg_upload_recipients
    $this->multipleFieldSupport($array, 'jg_msg_upload_recipients');

    // Support for multiple field: jg_msg_download_type
    $this->multipleFieldSupport($array, 'jg_msg_download_type');

    // Support for multiple field: jg_msg_download_recipients
    $this->multipleFieldSupport($array, 'jg_msg_download_recipients');

    // Support for multiple field: jg_msg_comment_type
    $this->multipleFieldSupport($array, 'jg_msg_comment_type');

    // Support for multiple field: jg_msg_comment_recipients
    $this->multipleFieldSupport($array, 'jg_msg_comment_recipients');

    // Support for multiple field: jg_msg_report_type
    $this->multipleFieldSupport($array, 'jg_msg_report_type');

    // Support for multiple field: jg_msg_report_recipients
    $this->multipleFieldSupport($array, 'jg_msg_report_recipients');

    // Support for multiple field: jg_msg_rejectimg_type
    $this->multipleFieldSupport($array, 'jg_msg_rejectimg_type');

    // Support for multiple field: group_id
    $this->multipleFieldSupport($array, 'group_id');

    // Support for multiple field: jg_uploaddefaultcat
    $this->multipleFieldSupport($array, 'jg_uploaddefaultcat');

    // Support for multiple field: jg_redirect_after_upload
    $this->multipleFieldSupport($array, 'jg_redirect_after_upload');

    // Support for multiple field: jg_downloadfile
    $this->multipleFieldSupport($array, 'jg_downloadfile');

  // Support for multiple field: jg_ratingcalctype
    $this->multipleFieldSupport($array, 'jg_ratingcalctype');

  // Support for number field: jg_maxusercat
  $this->numberFieldSupport($array, 'jg_maxusercat');

  // Support for number field: jg_maxuserimage
  $this->numberFieldSupport($array, 'jg_maxuserimage');

  // Support for number field: jg_maxuserimage_timespan
  $this->numberFieldSupport($array, 'jg_maxuserimage_timespan');

  // Support for number field: jg_maxfilesize
  $this->numberFieldSupport($array, 'jg_maxfilesize');

  // Support for number field: jg_maxuploadfields
  $this->numberFieldSupport($array, 'jg_maxuploadfields');

  // Support for number field: jg_maxvoting
  $this->numberFieldSupport($array, 'jg_maxvoting');

  // Support for multiple subform field: jg_replaceinfo
  $this->subformFieldSupport($array, 'jg_replaceinfo');

  // Support for multiple subform field: jg_staticprocessing
  $this->subformFieldSupport($array, 'jg_staticprocessing');

  // Support for multiple subform field: jg_dynamicprocessing
  $this->subformFieldSupport($array, 'jg_dynamicprocessing');

  // Support for multiple field: jg_record_hits_select
    $this->multipleFieldSupport($array, 'jg_record_hits_select');

  //
    if(isset($array['params']) && \is_array($array['params']))
    {
      $registry = new Registry;
      $registry->loadArray($array['params']);
      $array['params'] = (string) $registry;
    }

    if(isset($array['metadata']) && \is_array($array['metadata']))
    {
      $registry = new Registry;
      $registry->loadArray($array['metadata']);
      $array['metadata'] = (string) $registry;
    }

    // Bind the rules for ACL where supported.
    if(isset($array['rules']))
    {
    $rules = new Rules($array['rules']);
      $this->setRules($rules);
    }

    return parent::bind($array, $ignore);
  }

  /**
   * Overloaded check function
   *
   * @return bool
   */
  public function check()
  {
    // If there is an ordering column and this is a new row then get the next ordering value
    if(property_exists($this, 'ordering') && $this->id == 0)
    {
      $this->ordering = self::getNextOrder();
    }

    // Support for subform field jg_replaceinfo
    if(\is_array($this->jg_replaceinfo))
    {
      $this->jg_replaceinfo = json_encode($this->jg_replaceinfo, JSON_UNESCAPED_UNICODE);
    }

    if(\is_null($this->jg_replaceinfo))
    {
      $this->jg_replaceinfo = '{}';
    }

    // Support for subform field jg_staticprocessing
    if(\is_array($this->jg_staticprocessing))
    {
      $this->jg_staticprocessing = json_encode($this->jg_staticprocessing, JSON_UNESCAPED_UNICODE);
    }

    if(\is_null($this->jg_staticprocessing))
    {
      $this->jg_staticprocessing = '{}';
    }

    // Support for subform field jg_dynamicprocessing
    if(\is_array($this->jg_dynamicprocessing))
    {
      $this->jg_dynamicprocessing = json_encode($this->jg_dynamicprocessing, JSON_UNESCAPED_UNICODE);
    }

    if(\is_null($this->jg_dynamicprocessing))
    {
      $this->jg_dynamicprocessing = '{}';
    }

  // Support for media manager image select
  if(!empty($this->jg_wmfile) && strpos($this->jg_wmfile, '#') !== false)
  {
    $this->jg_wmfile = explode('#', $this->jg_wmfile)[0];
  }


    return parent::check();
  }
}
