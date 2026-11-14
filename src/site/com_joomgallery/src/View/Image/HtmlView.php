<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\View\Image;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomgallery\Component\Joomgallery\Site\Model\ImageModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * View class for a detail view of Joomgallery.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
  /**
   * The media object
   *
   * @var  \stdClass
   */
  protected $item;

  /**
   * The page parameters
   *
   * @var    array
   *
   * @since  4.0.0
   */
  protected $params = [];

  protected $navigation = [];
  protected $backUrl    = '';
  protected $originView = 'category';
  protected $imageInfo;

  /**
   * Collections belonging to the current user.
   *
   * @var array
   * @since DEPLOY_VERSION
   */
  public $collections = [];

  /**
   * Default collection of the current user.
   *
   * @var object|null
   * @since DEPLOY_VERSION
   */
  public $defaultCollection = null;

  /**
   * Collection represented by the primary collection button.
   *
   * @var object|null
   * @since DEPLOY_VERSION
   */
  public $primaryCollection = null;

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
    /** @var ImageModel $model */
    $model = $this->getModel();

    $this->state  = $model->getState();
    $this->params = $model->getParams();

    $loaded = true;
    try
    {
      $this->item = $model->getItem();
    }
    catch (\Exception $e)
    {
      $loaded = false;
    }

    $temp = $model->getCategoryProtected();

    // Check if category is protected?
    if($loaded && $model->getCategoryProtected())
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_IMAGE_CAT_PROTECTED'), 'error');
      $this->app->redirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=category&id=' . $this->item->protectedParents[0]));
    }

    $temp = $model->getCategoryPublished();

    // Check published and approved state
    if(!$loaded || !$model->getCategoryPublished() || $this->item->published !== 1 || $this->item->approved !== 1)
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_UNAVAILABLE_VIEW'), 'error');

      return;
    }

    $temp = $model->getCategoryAccess();

    // Check access view level
    if(!$model->getCategoryAccess() || !\in_array($this->item->access, $this->getCurrentUser()->getAuthorisedViewLevels()))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error');

      return;
    }

    // Check for errors.
    if(\count($errors = $model->getErrors()))
    {
      throw new GenericDataException(implode("\n", $errors), 500);
    }

    // Load additional information
    $this->item->rating = JoomHelper::getRating($this->item->id);
    $this->prepareCollections();
    $this->prepareNavigation($model);

    try
    {
      $this->imageInfo = JoomHelper::getImgInfo($this->item, $this->params['configs']->get('jg_detail_view_type_image', 'detail', 'STRING'));
    }
    catch(\Throwable $e)
    {
      $this->imageInfo = new \stdClass();
    }

    $this->_prepareDocument();

    parent::display($tpl);
  }

  /**
   * Load the current user's selectable collections and image mapping state.
   *
   * @return void
   *
   * @since DEPLOY_VERSION
   */
  protected function prepareCollections(): void
  {
    $identity = $this->getCurrentUser();

    if($identity->guest)
    {
      return;
    }

    $model             = $this->component->getMVCFactory()->createModel('Collections', 'Administrator', ['ignore_request' => true]);
    $this->collections = $model->getSelectableByCmsUser((int) $identity->id, (int) $this->item->id);

    foreach($this->collections as $collection)
    {
      if((int) $collection->is_default === 1)
      {
        $this->defaultCollection = $collection;

        if((int) $collection->mapped === 1)
        {
          $this->primaryCollection = $collection;
        }
      }
    }

    if($this->primaryCollection === null)
    {
      $mapped = array_values(array_filter($this->collections, static fn($collection) => (int) $collection->mapped === 1));

      usort(
        $mapped,
        static fn($left, $right) => ((int) $right->image_count <=> (int) $left->image_count)
          ?: ((int) $left->id <=> (int) $right->id)
      );

      $this->primaryCollection = $mapped[0] ?? $this->defaultCollection;
    }
  }

  /** Resolve a safe return target and load siblings in the originating list. */
  protected function prepareNavigation(ImageModel $model): void
  {
    $encodedReturn = $this->app->getInput()->getString('return', '');
    $candidate     = $encodedReturn !== '' ? base64_decode($encodedReturn, true) : false;

    if(!$candidate)
    {
      $candidate = $this->app->input->server->getString('HTTP_REFERER', '');
    }
    $siteHost      = Uri::getInstance(Uri::root())->getHost();
    $candidateHost = $candidate ? Uri::getInstance($candidate)->getHost() : '';
    $isLocal       = $candidate && (!$candidateHost || strcasecmp($candidateHost, $siteHost) === 0);
    $isImageUrl    = $candidate && (strpos($candidate, 'view=image') !== false || preg_match('#/images/\d+(?:-|/|$)#', $candidate));

    if($isLocal && !$isImageUrl)
    {
      $this->backUrl    = $candidate;
      $candidatePath    = rtrim(Uri::getInstance($candidate)->getPath(), '/');
      $this->originView = strpos($candidate, 'view=gallery') !== false || preg_match('#/gallery$#', $candidatePath) ? 'gallery' : 'category';
    }
    else
    {
      $this->backUrl = Route::_('index.php?option=' . _JOOM_OPTION . '&view=category&id=' . (int) $this->item->catid);
    }
    $this->navigation = $model->getNavigation($this->originView);
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
    // We need to get it from the menu item itself
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
      $title            = $this->item->title ?? '';
      $baseTitle        = trim(Text::_('COM_JOOMGALLERY_META_TITLE_PREFIX') . ' ' . $title);
      $sitename         = $this->app->get('sitename');
      $siteNamePosition = (int) $this->app->get('sitename_pagetitles', 0);

      $title = $siteNamePosition === 1 ? $sitename . ' - ' . $baseTitle : ($siteNamePosition === 2 ? $baseTitle . ' - ' . $sitename : $baseTitle);
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
      $pathway        = $this->app->getPathway();
      $breadcrumbList = Text::_('COM_JOOMGALLERY_IMAGES');

      if(!\in_array($breadcrumbList, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbList, JoomHelper::getViewRoute('images'));
      }

      $breadcrumbTitle = $this->item->title;

      if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbTitle, '');
      }
    }
  }
}
