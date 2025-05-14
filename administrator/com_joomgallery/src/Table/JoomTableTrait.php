<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Access\Rules;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Filter\OutputFilter;

/**
* Trait for Table methods
*
* @since  4.0.0
*/
trait JoomTableTrait
{
  /**
   * Form element to the table
   *
   * @var Form
  */
  public $form = false;

  /**
   * True if we are in an environement where the component class exists
   *
   * @var bool
  */
  protected $component_exists = true;

  /**
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    $this->load($pk);
    $result = parent::delete($pk);

    return $result;
  }

  /**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if(\property_exists($this, 'ordering') && $this->id == 0 && \is_null($this->ordering))
		{
			$this->ordering = self::getNextOrder();
		}

		// Check if alias is unique
    if( \property_exists($this, 'alias') &&
        (\property_exists($this, '_checkAliasUniqueness') ? $this->_checkAliasUniqueness : true)
      )
    {
      if(!$this->isUnique('alias'))
      {
        $count = 2;
        $currentAlias =  $this->alias;

        while(!$this->isUnique('alias'))
        {
          $this->alias = $currentAlias . '-' . $count++;
        }
      }
    }

    // Support for field description
    if(\property_exists($this, 'description'))
    {
      if(empty($this->description))
      {
        $this->description = $this->loadDefaultField('description');
      }
    }

		// Support for subform field params
    if(\property_exists($this, 'params'))
    {
      if(empty($this->params))
      {
        $this->params = $this->loadDefaultField('params');
      }
      elseif(\is_array($this->params))
      {
        $this->params = \json_encode($this->params, JSON_UNESCAPED_UNICODE);
      }
    }

    // Support for field metadesc
    if(\property_exists($this, 'metadesc'))
    {
      if(empty($this->metadesc))
      {
        $this->metadesc = $this->loadDefaultField('metadesc');
      }
    }

    // Support for field metakey
    if(\property_exists($this, 'metakey'))
    {
      if(empty($this->metakey))
      {
        $this->metakey = $this->loadDefaultField('metakey');
      }
    }

		return parent::check();
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
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task', '', 'cmd');

    // Support for title field: title
    if(\array_key_exists('title', $array))
    {
      $array['title'] = \trim($array['title']);
      if(empty($array['title']))
      {
        $array['title'] = 'Unknown';
      }
    }

    // Support for alias field: alias
    if(\array_key_exists('alias', $array))
    {
      if(empty($array['alias']))
      {
        if(empty($array['title']))
        {
          $array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
        }
        else
        {
          if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
          {
            $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['title']));
          }
          else
          {
            $array['alias'] = OutputFilter::stringURLSafe(trim($array['title']));
          }
        }
      }
      else
      {
        if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
        {
          $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['alias']));
        }
        else
        {
          $array['alias'] = OutputFilter::stringURLSafe(trim($array['alias']));
        }
      }
    }

		if(\array_key_exists('params', $array) && isset($array['params']) && \is_array($array['params']))
		{
			$registry = new Registry($array['params']);
			$array['params'] = (string) $registry;
		}

		if(\array_key_exists('metadata', $array) && isset($array['metadata']) && \is_array($array['metadata']))
		{
			$registry = new Registry($array['metadata']);
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
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   4.0.0
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
	}

  /**
	 * Get an array of all record fields and their current values
	 *
   * @param   array   $exclude   Array with properties to be excluded (default: [])
   * 
	 * @return  array  Fields with values
	 *
	 * @since   4.0.0
	 */
  public function getFieldsValues($exclude = array())
  {
    // Convert to \stdClass before adding other data
		$properties = $this->getProperties(1);
		$item       = ArrayHelper::toObject($properties);

		if(\property_exists($item, 'params'))
		{
			$registry = new Registry($item->params);
			$item->params = $registry->toArray();
		}

		if(isset($item->params))
		{
		  $item->params = \json_encode($item->params);
		}

    // Delete excluded properties
    if(\count($exclude) > 0)
    {
      foreach($exclude as $property)
      {
        unset($item->{$property});
      }
    }

		return $item;
  }

  /**
   * Method to get the last ordering value for a group of rows defined by an SQL WHERE clause.
   * This is useful for placing a new item first in a group of items in the table.
   *
   * @param   string    $where  query WHERE clause for selecting MAX(ordering).
   * 
   * @return  integer   The ordring number
   * 
   * @since   4.0.0
   * @throws  \UnexpectedValueException
   */
  public function getPreviousOrder($where = '')
  {
    // Check if there is an ordering field set
    if(!$this->hasField('ordering'))
    {
      throw new \UnexpectedValueException(sprintf('%s does not support ordering.', \get_class($this)));
    }

    // Get the largest ordering value for a given where clause.
    $query = $this->_db->getQuery(true)
      ->select('MIN(' . $this->_db->quoteName($this->getColumnAlias('ordering')) . ')')
      ->from($this->_db->quoteName($this->_tbl));

    if($where)
    {
      $query->where($where);
    }

    $this->_db->setQuery($query);
    $max = (int) $this->_db->loadResult();

    return $max - 1;
  }

  /**
   * Get the component class
   *
   * @return  object
   */
  protected function getComponent(): object
  {
    if($this->component_exists)
		{
      $obj = Factory::getApplication()->bootComponent('com_joomgallery');
		}
		else
		{
		  $obj = $this->addMessageTrait();
		}

    return $obj;
  }

  /**
	 * Check if a field is unique
	 *
	 * @param   string   $field         Name of the field
   * @param   integer  $parent        Parent id (default=null)
   * @param   string   $parentfield   Field name of parent id (default='parent_id')
	 *
	 * @return  bool    True if unique
	 */
	protected function isUnique($field, $parent=null, $parentfield='parent_id')
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName($field))
			->from($db->quoteName($this->_tbl))
			->where($db->quoteName($field) . ' = ' . $db->quote($this->$field))
			->where($db->quoteName('id') . ' <> ' . (int) $this->{$this->_tbl_key});

    if($parent > 0)
    {
      $query->where($db->quoteName($parentfield) . ' = ' . $db->quote($parent));
    }

		$db->setQuery($query);
		$db->execute();

		return ($db->getNumRows() == 0) ? true : false;
	}

  /**
   * Support for multiple field
   *
   * @param   array   $data       Form data
   * @param   string  $fieldName  Name of the field
   *
   * @return  void
   */
  protected function multipleFieldSupport(&$data, $fieldName)
  {
    if(isset($data[$fieldName]))
		{
			if(\is_array($data[$fieldName]))
			{
				$data[$fieldName] = \implode(',',$data[$fieldName]);
			}
			elseif(\strpos($data[$fieldName], ',') != false)
			{
				$data[$fieldName] = \explode(',',$data[$fieldName]);
			}
			elseif(\strlen($data[$fieldName]) == 0)
			{
				$data[$fieldName] = '';
			}
		}
		else
		{
			$data[$fieldName] = '';
		}
  }

  /**
   * Support for number field
   *
   * @param   array   $data       Form data
   * @param   string  $fieldName  Name of the field
   *
   * @return  void
   */
  protected function numberFieldSupport(&$data, $fieldName)
  {
    if($data[$fieldName] === '')
		{
			$data[$fieldName] = null;
			$this->{$fieldName} = null;
		}
  }

  /**
   * Support for number field
   *
   * @param   array   $data       Form data
   * @param   string  $fieldName  Name of the field
   *
   * @return  void
   */
  protected function subformFieldSupport(&$data, $fieldName)
  {
    if((!empty($data[$fieldName]) && (\is_array($data[$fieldName]))))
    {
      \array_push($this->_jsonEncode, $fieldName);
    }
  }

  /**
	 * Method to load the default value of a field in a xml form
	 *
   * @param   string  $field  The name of the field to get the default value from.
	 * @param   string  $form   The filename of the xml form.
	 *
	 * @return  string
	 */
  protected function loadDefaultField($field, $form=null)
  {
    // Get form name
    if(!$form)
    {
      $typeArr = \explode('.', $this->typeAlias);
      $form    = $typeArr[1];
    }

    // Load form
    $this->loadForm($form);

    // Get field type
    $field_type = $this->form->getField($field)->getAttribute('type', 'text');

    // Load default from xml field
    if($field_type == 'subform')
    {
      // Load subform
      $subform = $this->form->getField($field)->loadSubForm();

      // Load array with defaults
      $defaults = array();
      foreach($subform->getFieldset('general') as $key => $field)
      {
        $defaults[$field->getAttribute('name')] = $field->getAttribute('default', '');
      }

      return \json_encode($defaults, JSON_FORCE_OBJECT);
    }
    else
    {
      return $this->form->getField($field)->getAttribute('default', '');
    }
  }

  /**
	 * Method to load a Form object coupled to an xml form
	 *
	 * @param   string  $form   The filename of the xml form.
	 *
	 * @return  void
	 */
  protected function loadForm($form)
  {
    // Get xml file path
    if(\file_exists(_JOOM_PATH_ADMIN . '/forms/'.$form.'.xml'))
    {
      $xml_file  = _JOOM_PATH_ADMIN . '/forms/'.$form.'.xml';
      $form_name = $form;
    }
    elseif(\file_exists(_JOOM_PATH_ADMIN . '/forms/'.$form))
    {
      $xml_file  = _JOOM_PATH_ADMIN . '/forms/'.$form;
      $form_name = \str_replace('.xml', '', $form);
    }

    // Load form
    $this->form = new Form($form_name);
    $this->form->loadFile($xml_file);
  }

  /**
	 * Method to add a fake JoomGallery component class in order to use the Message functions
	 *
	 * @return  object
	 */
  protected function addMessageTrait(): object
  {
    $jgobjectClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Extension\\JoomgalleryComponent';

    if(!\class_exists($jgobjectClass))
    {
      // We expect to be in a pre installed environement. Use a custom way of including the MessageTrait.
      $msgtraitClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Extension\\MessageTrait';
      $msgtrait_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Extension'.DIRECTORY_SEPARATOR.'MessageTrait.php';

      // Manually include the MessageTrait file if it's not already available
      if(!\trait_exists($msgtraitClass))
      {
        require_once $msgtrait_path;
      }

      // Create an anonymous class that uses the MessageTrait
      return new class extends \stdClass {
        use \Joomgallery\Component\Joomgallery\Administrator\Extension\MessageTrait;
      };
    }
    else
    {
      // We expect to be in a post installed environment. Use the default way.
      return Factory::getApplication()->bootComponent('com_joomgallery');
    }
  }
}
