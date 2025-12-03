<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access.
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Model\CategoryModel as AdminCategoryModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\CurrentUserInterface;

/**
 * Model to handle a category form.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryformModel extends AdminCategoryModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'category';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     *
     * @since   4.0.0
     *
     * @throws  \Exception
     */
    protected function populateState()
    {
        // Load state from the request userState on edit or from the passed variable on default
        $id = $this->app->input->getInt('id', null);

        if($id)
        {
            $this->app->setUserState('com_joomgallery.edit.category.id', $id);
        }
        else
        {
            $id = (int) $this->app->getUserState('com_joomgallery.edit.category.id', null);
        }

        if(\is_null($id))
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
     * @param   integer  $id  The id of the primary key.
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
     * Method to get the profile form.
     *
     * The base form is loaded from XML
     *
     * @param   array   $data     An optional array of data for the form to interogate.
     * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|CurrentUserInterface|false    A Form object on success, false on failure
     *
     * @since   4.0.0
     */
    public function getForm($data = [], $loadData = true): Form|CurrentUserInterface|false
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'categoryform', ['control'   => 'jform',  'load_data' => $loadData]);

    if(empty($form))
    {
      return false;
    }

    // Apply filter to exclude child categories
    $children = $form->getFieldAttribute('parent_id', 'children', 'true');
    $children = filter_var($children, FILTER_VALIDATE_BOOLEAN);

    if(!$children)
    {
      $form->setFieldAttribute('parent_id', 'exclude', $this->item->id);
    }

        // Apply filter for current category on thumbnail field
    $form->setFieldAttribute('thumbnail', 'categories', $this->item->id);

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
  public function getReturnPage(): string
  {
    return base64_encode($this->getState('return_page', ''));
  }
}
