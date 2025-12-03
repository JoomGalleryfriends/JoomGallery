<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\View;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\JsonView;
use Joomla\CMS\Response\JsonResponse;

/**
 * Parent JSON View Class for JoomGallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomGalleryJsonView extends JsonView
{
  /**
   * The model state
   *
   * @var  object
   */
  protected $state;

  /**
   * Joomla\CMS\Application\AdministratorApplication
   *
   * @access  protected
   * @var     object
   */
  protected $app;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  protected $component;

  /**
   * JoomGallery access service
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface
   */
  protected $acl = null;

  /**
   * JUser object, holds the current user data
   *
   * @access  protected
   * @var     object
   */
  protected $user;

  /**
   * Message that should be served on the json request
   *
   * @access  protected
   * @var     string
   */
  protected $message = '';

  /**
   * Request success flag
   *
   * @access  protected
   * @var     bool
   */
  protected $error = false;

  /**
   * Constructor
   *
   * @access  protected
   * @return  void
   * @since   1.5.5
   */
  function __construct($config = [])
  {
    parent::__construct($config);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = $this->app->getIdentity();

    if(strpos($this->component->version, 'dev'))
    {
      // We are dealing with a development version (alpha or beta)
      $this->message = Text::_('COM_JOOMGALLERY_NOTE_DEVELOPMENT_VERSION');
    }
  }

  /**
   * Method to get the access service class.
   *
   * @return  AccessInterface   Object on success, false on failure.
   * @since   4.0.0
   */
  public function getAcl(): AccessInterface
  {
    // Create access service
    if(\is_null($this->acl))
    {
      $this->component->createAccess();
      $this->acl = $this->component->getAccess();
    }

    return $this->acl;
  }

  /**
   * Check if state is set
   *
   * @param   mixed  $state  State
   *
   * @return bool
   */
  public function getState($state)
  {
    return isset($this->state->{$state}) ? $this->state->{$state} : false;
  }

  /**
   * Outputs the content as json string
   *
   * @param   mixed  $res  The output
   *
   * @return void
   */
  protected function output($res)
  {
    // Prevent the api url from being indexed
    $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow');

    // JInput object
    $input = $this->app->getInput();

    // Serializing the output
    $result = json_encode($res);

    // Pushing output to the document
    $this->getDocument()->setBuffer($result);

    // Output json response
    echo new JsonResponse($result, $this->message, $this->error, $input->get('ignoreMessages', true, 'bool'));
  }
}
