<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\Database\ParameterType;
use \Joomla\CMS\Form\Field\ListField;
use \Joomla\Database\DatabaseInterface;

/**
 * Category Edit field for JoomGallery
 *
 * @since  4.0.0
 */
class JgcategorydropdownField extends ListField
{
	/**
	 * A flexible category list that respects access controls
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	public $type = 'jgcategorydropdown';

	/**
	 * To allow creation of new categories.
	 *
	 * @var    integer   // ToDo: ??? bool ?
	 * @since  4.0.0
	 */
	protected $allowAdd;

	/**
	 * Optional prefix for new categories.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $customPrefix;

	/**
	 * Optional restrict to categories of logged user
	 *
	 * @var    bool
	 * @since  4.0.0
	 */
	protected $isCategoriesOfUser;

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $layout = 'joomla.form.field.categoryedit';

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string|null        $group    The field name group control value. This acts as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     FormField::setup()
	 * @since   4.0.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->allowAdd = isset($this->element['allowAdd']) ? (boolean) $this->element['allowAdd'] : false;
			$this->customPrefix = (string) $this->element['customPrefix'];

      $this->isCategoriesOfUser = isset($this->element['categoriesOfUser']) ? (boolean) $this->element['categoriesOfUser'] : false;
		}

		return $return;
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to get the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   4.0.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'allowAdd':
				return (bool) $this->$name;
			case 'customPrefix':
				return $this->$name;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to set the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function __set($name, $value)
	{
		$value = (string) $value;

		switch ($name)
		{
			case 'allowAdd':
				$value = (string) $value;
				$this->$name = ($value === 'true' || $value === $name || $value === '1');
				break;
			case 'customPrefix':
				$this->$name = (string) $value;
				break;
			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to get a list of categories that respects access controls and can be used for
	 * either category assignment or parent category assignment in edit screens.
	 * Use the parent element to indicate that the field will be used for assigning parent categories.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   4.0.0
	 */
	protected function getOptions()
	{
		$options = array();
		$published = $this->element['published'] ? \explode(',', (string) $this->element['published']) : array(0, 1);
		$name = (string) $this->element['name'];

		// Let's get the id for the current item, either category or content item.
		$jinput = Factory::getApplication()->input;

		// Load the category options for a given extension.

		// For categories the old category is the category id or 0 for new category.
		if ($this->element['parent'] || ($jinput->get('option') == _JOOM_OPTION && $jinput->get('view') == 'category'))
		{
			$oldCat = $jinput->get('id', 0);
			$oldParent = $this->form->getValue($name, 0);
			$extension = $this->element['extension'] ? (string) $this->element['extension'] : (string) $jinput->get('extension', _JOOM_OPTION);
		}
		else
			// For items the old category is the category they are in when opened or 0 if new.
		{
			$oldCat = $this->form->getValue($name, 0);
			$extension = $this->element['extension'] ? (string) $this->element['extension'] : (string) $jinput->get('option', _JOOM_OPTION);
		}

		// Account for case that a submitted form has a multi-value category id field (e.g. a filtering form), just use the first category
		$oldCat = \is_array($oldCat)
			? (int) \reset($oldCat)
			: (int) $oldCat;

		// Initialize needed classes
		$comp = Factory::getApplication()->bootComponent('com_joomgallery');
		$db   = Factory::getContainer()->get(DatabaseInterface::class);
		$user = Factory::getApplication()->getIdentity();

		// Get access service
		$comp->createAccess();
    $acl  = $comp->getAccess();

		$query = $db->getQuery(true)
			->select(
				[
					$db->quoteName('a.id', 'value'),
					$db->quoteName('a.title', 'text'),
					$db->quoteName('a.level'),
					$db->quoteName('a.published'),
          $db->quoteName('a.hidden'),
          $db->quoteName('a.in_hidden'),
					$db->quoteName('a.lft'),
					$db->quoteName('a.language'),
					$db->quoteName('a.created_by'),
				]
			)
			->from($db->quoteName(_JOOM_TABLE_CATEGORIES, 'a'));

		// Filter language
		if (!empty($this->element['language']))
		{
			if (strpos($this->element['language'], ',') !== false)
			{
				$language = \explode(',', $this->element['language']);
			}
			else
			{
				$language = $this->element['language'];
			}

			$query->whereIn($db->quoteName('a.language'), $language, ParameterType::STRING);
		}

		// Filter on the published state
		$state = ArrayHelper::toInteger($published);
		$query->whereIn($db->quoteName('a.published'), $state);

		// Filter categories on User Access Level
		// Filter by access level on categories.
		if (!$acl->checkACL('core.admin'))
		{
			$groups = $user->getAuthorisedViewLevels();
			$query->whereIn($db->quoteName('a.access'), $groups);
		}

		$query->order($db->quoteName('a.lft') . ' ASC');

		// If parent isn't explicitly stated, but we are in com_joomgallery assume we want parents
		if ($oldCat != 0 && ($this->element['parent'] == true || ($jinput->get('option') == _JOOM_OPTION && $jinput->get('view') == 'category')))
		{
			// Prevent parenting to children of this item.
			// To rearrange parents and children move the children up, not the parents down.
			$query->join(
				'LEFT',
				$db->quoteName(_JOOM_TABLE_CATEGORIES, 'p'),
				$db->quoteName('p.id') . ' = :oldcat'
			)
				->bind(':oldcat', $oldCat, ParameterType::INTEGER)
				->where('NOT(' . $db->quoteName('a.lft') . ' >= ' . $db->quoteName('p.lft')
					. ' AND ' . $db->quoteName('a.rgt') . ' <= ' . $db->quoteName('p.rgt') . ')'
				);
		}

    // Filter the root
    if(isset($this->element['show_root']) && (string) $this->element['show_root'] == 'false')
    {
      $query->where($db->quoteName('a.level') . ' > 0');
    }

    // Filter for user
    if($this->isCategoriesOfUser)
    {
      $query->where($db->quoteName('created_by') . ' = ' . (int) $user->id);
    }

    // Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			$this->component->addLog($e->getMessage(), 'error', 'jerror');
		}

		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			// Translate ROOT
			if ($this->element['parent'] == true || ($jinput->get('option') == _JOOM_OPTION && $jinput->get('view') == 'category'))
			{
				if ($options[$i]->level == 0)
				{
					$options[$i]->text = Text::_('JGLOBAL_ROOT_PARENT');
				}
			}

			if ($options[$i]->published == 1 && $options[$i]->hidden == 0 && $options[$i]->in_hidden == 0 || $options[$i]->level == 0)
			{
				$options[$i]->text = \str_repeat('- ', !$options[$i]->level ? 0 : $options[$i]->level - 1) . $options[$i]->text;
			}
			else
			{
				$options[$i]->text = \str_repeat('- ', !$options[$i]->level ? 0 : $options[$i]->level - 1) . '[' . $options[$i]->text . ']';
			}

			// Displays language code if not set to All
			if ($options[$i]->language !== '*')
			{
				$options[$i]->text = $options[$i]->text . ' (' . $options[$i]->language . ')';
			}
		}

		// For new items we want a list of categories you are allowed to create in.
		if ($oldCat == 0)
		{
			foreach ($options as $i => $option)
			{
				/*
				 * To take save or create in a category you need to have create rights for that category unless the item is already in that category.
				 * Unset the option if the user isn't authorised for it. In this field assets are always categories.
				 */
        $assetKey = $extension . '.category.' . $option->value;

				if ($option->level != 0 && !$acl->checkACL('core.create', $assetKey, 0, $option->value, true))
				{
					unset($options[$i]);
				}
			}
		}
		// If you have an existing category id things are more complex.
		else
		{
			/*
			 * If you are only allowed to edit in this category but not edit.state, you should not get any
			 * option to change the category parent for a category or the category for a content item,
			 * but you should be able to save in that category.
			 */
			foreach ($options as $i => $option)
			{
				$assetKey = $extension . '.category.' . $oldCat;

				if ($option->level != 0 && !isset($oldParent) && $option->value != $oldCat && !$acl->checkACL('core.edit.state', $assetKey))
				{
					unset($options[$i]);
					continue;
				}

				if ($option->level != 0	&& isset($oldParent) && $option->value != $oldParent && !$acl->checkACL('core.edit.state', $assetKey))
				{
					unset($options[$i]);
					continue;
				}

				/*
				 * However, if you can edit.state you can also move this to another category for which you have
				 * create permission, and you should also still be able to save in the current category.
				 */
				$assetKey = $extension . '.category.' . $option->value;

				if ($option->level != 0 && !isset($oldParent) && $option->value != $oldCat && !$acl->checkACL('core.create', $assetKey, 0, $option->value, true))
				{
					unset($options[$i]);
					continue;
				}

				if ($option->level != 0	&& isset($oldParent) && $option->value != $oldParent && !$acl->checkACL('core.create', $assetKey, 0, $option->value, true))
				{
					unset($options[$i]);
				}
			}
		}

		if ($oldCat != 0 && ($this->element['parent'] == true || ($jinput->get('option') == _JOOM_OPTION && $jinput->get('view') == 'category'))
			&& !isset($options[0])
			&& isset($this->element['show_root']))
		{
			$rowQuery = $db->getQuery(true)
				->select(
					[
						$db->quoteName('a.id', 'value'),
						$db->quoteName('a.title', 'text'),
						$db->quoteName('a.level'),
						$db->quoteName('a.parent_id'),
					]
				)
				->from($db->quoteName(_JOOM_TABLE_CATEGORIES, 'a'))
				->where($db->quoteName('a.id') . ' = :aid')
				->bind(':aid', $oldCat, ParameterType::INTEGER);
			$db->setQuery($rowQuery);
			$row = $db->loadObject();

			if ($row->parent_id == '1')
			{
				$parent = new \stdClass;
				$parent->text = Text::_('JGLOBAL_ROOT_PARENT');
				\array_unshift($options, $parent);
			}

			\array_unshift($options, HTMLHelper::_('select.option', '0', Text::_('JGLOBAL_ROOT')));
		}

		// Merge any additional options in the XML definition.
		return \array_merge(parent::getOptions(), $options);
	}

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   4.0.0
	 */
	protected function getInput()
	{
		$data = $this->getLayoutData();

		$data['options']        = $this->getOptions();
		$data['allowCustom']    = $this->allowAdd;
		$data['customPrefix']   = $this->customPrefix;
		$data['refreshPage']    = (boolean) $this->element['refresh-enabled'];
		$data['refreshCatId']   = (string) $this->element['refresh-cat-id'];
		$data['refreshSection'] = (string) $this->element['refresh-section'];

		$renderer = $this->getRenderer($this->layout);
		$renderer->setComponent('com_categories');
		$renderer->setClient(1);

		return $renderer->render($data);
	}
}
