<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Category;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Site\View\JoomGalleryJsonView;

/**
 * Json view class for a category view of Joomgallery.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class JsonView extends JoomGalleryJsonView
{
  /**
	 * The category object
	 *
	 * @var  \stdClass
	 */
	protected $item;

  /**
	 * Display the json view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
    /** @var CategoryModel $model */
    $model = $this->getModel();

    $this->state  = $model->getState();

    $loaded = true;
		try {
			$this->item = $model->getItem();
		}
		catch (\Exception $e)
		{
			$loaded = false;
		}

    // Check published state
		if($loaded && $this->item->published !== 1) 
		{
			$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_UNAVAILABLE_VIEW'), 'error');
			return;
		}

    // Check access view level
		if(!\in_array($this->item->access, $this->user->getAuthorisedViewLevels()))
    {
      $this->output(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'));
      return;
    }

    // Load parent category
    $this->item->parent = $model->getParent();

    // Load subcategories
    $this->item->children = new \stdClass();
    $this->item->children->items = $model->getChildren();

    // Load images
    $this->item->images = new \stdClass();
    $this->item->images->items = $model->getImages();

    // Check for errors.
		if(\count($errors = $model->getErrors()))
		{
      $this->error = true;
      $this->output($errors);

      return;
    }

    $this->output($this->item);
  }
}
