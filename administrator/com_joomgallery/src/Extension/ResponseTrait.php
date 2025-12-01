<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Psr\Http\Message\ResponseInterface;

/**
 * Trait implementing tools to create responses
 *
 * @since  4.0.0
 */
trait ResponseTrait
{
  /**
   * Joomla! CMS Application class
   * 
   * @var Joomla\CMS\Application\CMSApplication
   */
  protected $app;

  /**
   * Map of standard HTTP status code/reason phrases
   *
   * @var array
   */
  private $phrases = [
    // INFORMATIONAL CODES
    100 => 'Continue',
    101 => 'Switching Protocols',
    102 => 'Processing',
    103 => 'Early Hints',
    // SUCCESS CODES
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    207 => 'Multi-Status',
    208 => 'Already Reported',
    226 => 'IM Used',
    // REDIRECTION CODES
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => 'Switch Proxy', // Deprecated to 306 => '(Unused)'
    307 => 'Temporary Redirect',
    308 => 'Permanent Redirect',
    // CLIENT ERROR
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Payload Too Large',
    414 => 'URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',
    421 => 'Misdirected Request',
    422 => 'Unprocessable Entity',
    423 => 'Locked',
    424 => 'Failed Dependency',
    425 => 'Too Early',
    426 => 'Upgrade Required',
    428 => 'Precondition Required',
    429 => 'Too Many Requests',
    431 => 'Request Header Fields Too Large',
    444 => 'Connection Closed Without Response',
    451 => 'Unavailable For Legal Reasons',
    // SERVER ERROR
    499 => 'Client Closed Request',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    506 => 'Variant Also Negotiates',
    507 => 'Insufficient Storage',
    508 => 'Loop Detected',
    510 => 'Not Extended',
    511 => 'Network Authentication Required',
    599 => 'Network Connect Timeout Error',
  ];

  /**
   * Get the PSR-7 Response Object. 
   *
   * @return  ResponseInterface The response object
   */
  private function getResponse(): ResponseInterface
  {
    return $this->app->response;
  }

  private function setStatusCode(int $code)
  {
    $this->app->setHeader('Status', (string) $code);

    require JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/tusspecs.php';

    // Set content based on TUS specifications (https://tus.io/protocols/resumable-upload.html)
    if(isset($tus_specs_array['Codes'][$code]))
    {
      $this->setContent($tus_specs_array['Codes'][$code]);
    }
    elseif(isset($this->phrases[$code]))
    {
      // Set content based on RFC 9110 specification (https://httpwg.org/specs/rfc9110.html#overview.of.status.codes)
      $this->setContent($this->phrases[$code]);
    }
  }

  /**
   * Set body content.  If body content already defined, this will replace it.
   *
   * @param   string  $content  The content to set as the response body.
   *
   * @return  $this
   */
  private function setContent($content, $replace = true)
  {
    if($replace)
    {
      $this->app->setBody($content);
    }
    else
    {
      $this->app->appendBody($content);
    }
  }

  /**
   * Method to get the array of response headers to be sent when the response is sent to the client.
   *
   * @return  array
   */
  private function getHeaders()
  {
    return $this->app->getHeaders();
  }

  /**
   * Method to set a response header.
   *
   * If the replace flag is set then all headers with the given name will be replaced by the new one.
   * The headers are stored in an internal array to be sent when the site is sent to the browser.
   *
   * @param   string   $name     The name of the header to set.
   * @param   string   $value    The value of the header to set.
   * @param   boolean  $replace  True to replace any headers with the same name.
   *
   * @return  $this
   *
   * @since   4.0.0
   */
  private function addHeaderLine(string $name, string $value, bool $replace = true)
  {
    return $this->app->setHeader($name, $value, $replace);
  }
}

