<?php
/**
 ******************************************************************************************
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later                          **
 *****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use Joomgallery\Component\Joomgallery\Administrator\Model\CategoryModel as AdminCategoryModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Model to handle a user category form.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class UsercategoryModel extends AdminCategoryModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
//  protected $type = 'usercategory';
  protected $type = 'category';

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @return  void
   *
   * @throws  Exception
   * @since   4.0.0
   *
   */
  protected function populateState()
  {
    // Load state from the request userState on edit or from the passed variable on default
    $id = $this->app->input->getInt('id', null);
    if (!empty($id))
    {
      $this->app->setUserState('com_joomgallery.edit.category.id', $id);
    }
    else
    {
      // Old original: $id = (int) $this->app->getUserState('com_joomgallery.edit.category.id', null);

      // New category
      $id = 0;

      // Clear the profile id from the session.
      $this->app->setUserState('com_joomgallery.edit.category.id', null);
      $this->app->setUserState('com_joomgallery.edit.category.data', null);
    }

    if (is_null($id))
    {
      throw new \Exception('No ID provided to the model!', 500);
    }

    $return = $this->app->input->get('return', '', 'base64');
    $this->setState('return_page', base64_decode($return));

    $this->setState('category.id', $id);

    $this->loadComponentParams($id);
  }

  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  Object|boolean Object on success, false on failure.
   *
   * @since   4.0.0
   */
  public function getItem($id = null)
  {
    return parent::getItem($id);
  }

  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  Object|boolean Object on success, false on failure.
   *
   * @since   4.0.0
   */
  public function isUserRootCategory($id)
  {
    $isUserRootCategory = false;

    // try {

    $db = Factory::getContainer()->get(DatabaseInterface::class);

    // Check number of records in tables
    $query = $db->getQuery(true)
      ->select('id, parent_id')
      ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
      ->where($db->quoteName('id') . ' = ' . (int) $id);

    $db->setQuery($query);
    $item = $db->loadObject();

    if ((!empty($item->id)) && $item->parent_id == 1)
    {
      $isUserRootCategory = true;
    }

    return $isUserRootCategory;
  }

  /**
   * Method to get the profile form.
   *
   * The base form is loaded from XML
   *
   * @param   array    $data      An optional array of data for the form to interogate.
   * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  Form    A Form object on success, false on failure
   *
   * @since   4.0.0
   */
  public function getForm($data = array(), $loadData = true)
  {
    // Get the form.
    $form = $this->loadForm($this->typeAlias, 'usercategory', array('control' => 'jform', 'load_data' => $loadData));

    // Apply filter to exclude child categories
    $children = $form->getFieldAttribute('parent_id', 'children', 'true');
    $children = filter_var($children, FILTER_VALIDATE_BOOLEAN);
    if (!$children)
    {
      $form->setFieldAttribute('parent_id', 'exclude', $this->item->id);
    }

    // Apply filter for current category on thumbnail field
    $form->setFieldAttribute('thumbnail', 'categories', $this->item->id);

    if (empty($form))
    {
      return false;
    }

    return $form;
  }

  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  array  The default data is an empty array.
   *
   * @since   4.0.0
   */
  protected function loadFormData()
  {
    return parent::loadFormData();
  }

  /**
   * Get the return URL.
   *
   * @return  string  The return URL.
   *
   * @since   4.0.0
   */
  public function getReturnPage()
  {
    return \base64_encode($this->getState('return_page', ''));
  }
}
