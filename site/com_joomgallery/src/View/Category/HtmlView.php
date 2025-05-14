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
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a category view of Joomgallery.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	/**
	 * The category object
	 *
	 * @var  \stdClass
	 */
	protected $item;

  /**
	 * The active menu item object
	 *
	 * @var  \Joomla\CMS\Menu\MenuItem
	 */
	protected $menu;

	/**
	 * The page parameters
	 *
	 * @var    array
	 *
	 * @since  4.0.0
	 */
	protected $params = array();

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function display($tpl = null)
	{
		/** @var CategoryModel $model */
    $model = $this->getModel();

    $this->state  = $model->getState();
		$this->params = $model->getParams();
    $this->menu   = $this->app->getMenu()->getActive();

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
		if(!\in_array($this->item->access, $this->getCurrentUser()->getAuthorisedViewLevels()))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error');
			return;
    }

		// Load only if category is currently not protected
		if(!$this->item->pw_protected)
		{
			// Load parent category
			$this->item->parent = $model->getParent();

			// Load subcategories
			$this->item->children = new \stdClass();
			$this->item->children->items         = $model->getChildren();
			$this->item->children->pagination    = $model->getChildrenPagination();

			// Load images
			$this->item->images = new \stdClass();
			$this->item->images->items         = $model->getImages();
			$this->item->images->pagination    = $model->getImagesPagination();
			$this->item->images->filterForm    = $model->getImagesFilterForm();
			$this->item->images->activeFilters = $model->getImagesActiveFilters();
		}

    // Check for errors.
		if(count($errors = $model->getErrors()))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function _prepareDocument()
	{
		$title = null;

		// Because the application sets a default page title,
		// We need to get it from the menu item itself
		if($this->menu)
		{
			$this->params['menu']->def('page_heading', $this->params['menu']->get('page_title', $this->menu->title));
		}
		else
		{
			$this->params['menu']->def('page_heading', Text::_('JoomGallery'));
		}

		$title = $this->params['menu']->get('page_title', '');

		if(empty($title))
		{
			$title = $this->app->get('sitename');
		}
		elseif($this->app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);
		}
		elseif($this->app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
		}

		$this->document->setTitle($title);

		if($this->params['menu']->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params['menu']->get('menu-meta_description'));
		}

		if($this->params['menu']->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params['menu']->get('menu-meta_keywords'));
		}

		if($this->params['menu']->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params['menu']->get('robots'));
		}

    // Get ID of the category from active menu item
    if($this->menu && $this->menu->component == _JOOM_OPTION && isset($this->menu->query['view']) && in_array($this->menu->query['view'], ['categories', 'category']))
    {
      $id = $this->menu->query['id'];
    }
    else
    {
      $id = 1;
    }

		if(!$this->isMenuCurrentView($this->menu))
		{
			// Add Breadcrumbs
			if($this->item->id > 1)
			{
				$path = [['title' => $this->item->title, 'link' => '']];
			}
			else
			{
				$path = [];
			}
			
			if(!$this->item->pw_protected)
			{
				$category = $this->item->parent;
			
				while($category && $category->id !== 1 && $category->id != $id)
				{
					$path[]   = ['title' => $category->title, 'link' => JoomHelper::getViewRoute('category', $category->id, 0, null, null, $category->language)];
					$category = $this->getModel()->getParent($category->parent_id);
				}

				$path = \array_reverse($path);

				foreach($path as $item)
				{
					$this->app->getPathway()->addItem($item['title'], $item['link']);
				}
			}
		}
	}
}
