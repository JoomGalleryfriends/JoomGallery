<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View;

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
   * @var     AccessInterface
   */
  protected $acl = null;

  /**
   * User object, holds the current user data
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

    $this->app       = Factory::getApplication();
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = $this->app->getIdentity();

    if( stripos($this->component->version, 'dev') ||
        stripos($this->component->version, 'alpha') ||
        stripos($this->component->version, 'beta') ||
        stripos($this->component->version, 'rc')
     )
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
   * Recursively converts all JSON strings in an object/array into PHP objects.
   * Suitable for preparing a stdClass before calling json_encode().
   *
   * @param  mixed  $data  The data to be processed
   *
   * @return mixed
   */
  protected function prepareForJson(mixed $data): mixed
  {
    // Process objects
    if(\is_object($data))
    {
      foreach($data as $key => $value)
      {
        $data->$key = $this->prepareForJson($value);
      }

      return $data;
    }

    // Process arrays
    if(\is_array($data))
    {
      foreach($data as $key => $value)
      {
        $data[$key] = $this->prepareForJson($value);
      }

      return $data;
    }

    // Process strings
    if(\is_string($data))
    {
      $trimmed = trim($data);

      // Skip empty strings
      if($trimmed === '')
      {
        return $data;
      }

      // Only attempt to decode values that look like JSON
      if( ($trimmed[0] === '{' && str_ends_with($trimmed, '}')) || ($trimmed[0] === '[' && str_ends_with($trimmed, ']')))
      {
        try
        {
          $decoded = json_decode($trimmed, false, 512, JSON_THROW_ON_ERROR);

          // Continue recursively because the decoded object may itself
          // contain JSON strings.
          return $this->prepareForJson($decoded);
        }
        catch(\JsonException)
        {
          // Not valid JSON -> leave untouched.
        }
      }
    }

    return $data;
  }

  protected function isJson(mixed $string)
  {
    if(\is_string($string))
    {
      json_decode($string);

      return json_last_error() === JSON_ERROR_NONE;
    }

    return false;
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

    // Input object
    $input = $this->app->getInput();

    // Serializing the output
    if($this->isJson($res))
    {
      // Content must not be serialized
      $res = json_decode($res);
    }

    // Pushing output to the document
    $this->getDocument()->setBuffer($res);

    // Output json response
    echo new JsonResponse($res, $this->message, $this->error, $input->get('ignoreMessages', true, 'bool'));
  }
}
