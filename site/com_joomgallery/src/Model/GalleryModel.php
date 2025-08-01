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

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\CMS\Language\Multilanguage;

/**
 * Model for the gallery view.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class GalleryModel extends JoomItemModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'gallery';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws \Exception
	 */
	protected function populateState()
	{
		$this->loadComponentParams();
	}

	/**
	 * Method to get an object.
	 *
	 * @param   integer $id The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @throws \Exception
	 */
	public function getItem($id = null)
	{
		if($this->item === null)
		{
			$this->item = new \stdClass();
      $this->item->id = 1;
		}

    // Get Gallery description
    $params = $this->getParams();
    $this->item->description = $params['configs']->get('jg_gallery_view_description','', 'STRING');

		return $this->item;
	}

  /**
	 * Method to check in an item.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function checkin($id = null)
	{
    return true;
  }

  /**
	 * Method to check out an item for editing.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function checkout($id = null)
	{
    return true;
  }

  /**
	 * Method to get the images to be viewed in the gallery view.
	 *
	 * @return  array|false    Array of images on success, false on failure.
	 *
	 * @throws Exception
	 */
  public function getImages()
  {
    if($this->item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load images list model
    $listModel = $this->component->getMVCFactory()->createModel('images', 'site');
    $listModel->getState();

    // Select fields to load
    $fields = array('id', 'alias', 'catid', 'title', 'description', 'filename', 'filesystem', 'author', 'date', 'hits', 'votes', 'votesum');
    $fields = $this->addColumnPrefix('a', $fields);

    // Apply preselected filters and fields selection for images
    $this->setImagesModelState($listModel, $fields);

    // Get images
    $items = $listModel->getItems();

    if(!empty($listModel->getError()))
    {
      $this->setError($listModel->getError());
    }

    return $items;
  }

  /**
   * Method to get a \JPagination object for the images in this category.
   *
   * @return  Pagination  A Pagination object for the images in this category.
   */
  public function getImagesPagination()
  {
    if($this->item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load images list model
    $listModel = $this->component->getMVCFactory()->createModel('images', 'site');
    $listModel->getState();

    // Apply preselected filters and fields selection for images
    $this->setImagesModelState($listModel);

    // Get pagination
    $pagination = $listModel->getPagination();

    // Set additional query parameter to pagination
    $pagination->setAdditionalUrlParam('contenttype', 'image');

    return $pagination;
  }

  /**
   * Function to set the image list model state for the pre defined filter and fields selection
   * 
   * @param   ListModel   $listModel    Images list model
   * @param   array       $fields       List of field names to be loaded (default: array())
   *
   * @return  void
   */
  protected function setImagesModelState(ListModel &$listModel, array $fields = array())
  {
    // Get current user
    $user   = $this->app->getIdentity();
    $params = $this->getParams();

    // Apply selection
    if(\count($fields) > 0)
    {
      $listModel->setState('list.select', $fields);
    }

    // Apply filters
    $listModel->setState('filter.access', $user->getAuthorisedViewLevels());
    $listModel->setState('filter.published', 1);
    $listModel->setState('filter.showunapproved', 0);
    $listModel->setState('filter.showhidden', 0);

    if(Multilanguage::isEnabled())
    {
      $listModel->setState('filter.language', $this->item->language);
    }

    $imgform_list = array();
    $imgform_limitstart = 0;
    if($this->app->input->get('contenttype', '') == 'image')
    {
      // Get query variables sent by the images form
      $imgform_list = $this->app->input->get('list', array());
      $imgform_limitstart = $this->app->getInput()->get('limitstart', 0, 'int');
    }

    // Load the number of images defined in the configuration
    $listModel->setState('list.limit', $params['configs']->get('jg_gallery_view_numb_images', 12, 'int'));

    // Apply number of images to be loaded from list in the view
    if(isset($imgform_list['limit']))
    {
      $listModel->setState('list.limit', $imgform_list['limit']);
    }

    // Disable behavior of remembering pagination position
    // if it is not explicitely given in the request
    $listModel->setState('list.start', $imgform_limitstart);

    // Apply ordering
    $listModel->setState('list.ordering', '');
    $listModel->setState('list.fullordering', $params['configs']->get('jg_gallery_view_ordering', 'a.hits DESC'));
  }

  /**
	 * Method to add a prefix to a list of field names
	 *
	 * @param   string  $prefix   The prefix to apply
   * @param   array   $fields   List of fields
	 *
	 * @return  array   List of fields with applied prefix
	 */
  protected function addColumnPrefix(string $prefix, array $fields): array
  {
    foreach($fields as $key => $field)
    {
      $field = (string) $field;

      if(\strpos($field, $prefix.'.') === false)
      {
        $fields[$key] = $prefix . '.' . $field;
      }
    }

    return $fields;
  }
}
