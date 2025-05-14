<?php

/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Imageform;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a list of Joomgallery.
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
	 * The form object
	 *
	 * @var  \Joomla\CMS\Form\Form;
	 */
	protected $form;

	/**
	 * The page parameters
	 *
	 * @var    array
	 *
	 * @since  4.0.0
	 */
	protected $params = array();

	/**
   * The page to return to after the article is submitted
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $return_page = '';

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function display($tpl = null)
	{
		$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_NOT_YET_AVAILABLE'), 'warning');

		if(!$this->app->input->get('preview', 0))
		{
			return;
		}

		/** @var ImagefromModel $model */
    $model = $this->getModel();

    $this->state  = $model->getState();
		$this->params = $model->getParams();
		$this->item   = $model->getItem();
		$this->form   = $model->getForm();

    // Get return page
    $this->return_page = $model->getReturnPage();

		// Check for errors.
		if(count($errors = $model->getErrors()))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

    // Check access view level
		if(!\in_array($this->item->access, $this->getCurrentUser()->getAuthorisedViewLevels()))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error');
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
		$menus = $this->app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if($menu)
		{
			$this->params['menu']->def('page_heading', $this->params['menu']->get('page_title', $menu->title));
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

		if(!$this->isMenuCurrentView($menu))
		{
			// Add Breadcrumbs
			$pathway = $this->app->getPathway();
			$breadcrumbList = Text::_('COM_JOOMGALLERY_IMAGES');

			if(!\in_array($breadcrumbList, $pathway->getPathwayNames()))
			{
				$pathway->addItem($breadcrumbList, JoomHelper::getViewRoute('images'));
			}

			$breadcrumbTitle = isset($this->item->id) ? Text::_('JGLOBAL_EDIT') : Text::_('JGLOBAL_FIELD_ADD');

			if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
			{
				$pathway->addItem($breadcrumbTitle, '');
			}
		}
	}
}
