<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\User\CurrentUserInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Model\CategoryModel as AdminCategoryModel;

/**
 * Model to handle a user category form.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class UsercategoryModel extends AdminCategoryModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   *
   * @since   4.2.0
   */
//  protected $type = 'usercategory';
  protected $type = 'category';

  /**
   * True if a password is set
   *
   * @access  protected
   * @var     bool
   */
//  protected $is_password = true;

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @return  void
   *
   * @throws  \Exception
   * @since   4.2.0
   *
   */
  protected function populateState(): void
  {
    // Load state from the request userState on edit or from the passed variable on default
    $id = $this->app->input->getInt('id', null);
    if(!empty($id))
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

    if(is_null($id))
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
   * @param   integer   $id  The id of the primary key.
   *
   * @return  Object|bool Object on success, false on failure.
   *
   * @since   4.2.0
   */
  public function getItem($id = null): object|bool
  {
    return parent::getItem($id);
  }

  /**
   * Method to get a single record.
   *
   * @param   integer   $id  The id of the primary key.
   *
   * @return  bool when root category is matching id
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function isUserRootCategory(int $id): bool
  {
    $isUserRootCategory = false;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('id, parent_id')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('id').' = '.(int) $id);

      $db->setQuery($query);
      $item = $db->loadObject();

      if((!empty($item->id)) && $item->parent_id == 1)
      {
        $isUserRootCategory = true;
      }
    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('isUserRootCategory-Error: '.$e->getMessage(), 'error');

      return false;
    }

    return $isUserRootCategory;
  }

  /**
   * Method to get the profile form.
   *
   * The base form is loaded from XML
   *
   * @param   array   $data      An optional array of data for the form to interogate.
   * @param   bool    $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  Form|CurrentUserInterface|false    A Form object on success, false on failure
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function getForm($data = array(), $loadData = true): Form|CurrentUserInterface|false
  {
    // Get the form.
    $form = $this->loadForm($this->typeAlias, 'usercategory', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form))
    {
      return false;
    }

    return $form;
  }

  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  \Joomla\CMS\Object\CMSObject|\stdClass|array  The default data is an empty array.
   *
   * @since   4.2.0
   */
  protected function loadFormData(): \Joomla\CMS\Object\CMSObject|\stdClass|array
  {
    return parent::loadFormData();
  }

  /**
   * Get the return URL.
   *
   * @return  string  The return URL.
   *
   * @since   4.2.0
   */
  public function getReturnPage(): string
  {
    return \base64_encode($this->getState('return_page', ''));
  }

  /**
   * Get the most recent error message.
   * Eiter generated in model or written to component
   *
   * @param   integer   $i  Option error index.
   * @param   boolean   $toString  Indicates if Exception objects should return their error message.
   *
   * @return  string   Error message
   *
   * @since   1.7.0
   *
   * @deprecated  3.1.4 will be removed in 6.0
   *              Will be removed without replacement
   *              Catch thrown Exceptions instead of getError
   */
  public function getError($i = null, $toString = true)
  {
    // 2025.10.04: user images checkin does not find error message in model !manuel!
    $error = parent::getError($i, $toString);

    // error saved by $this->component->setError(...)
    if (empty($error)){
      $error = $this->component->getError(true);
    }

    return $error;
  }

}
