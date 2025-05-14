<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\TusServer;

\defined('_JEXEC') or die;

use Psr\Http\Message\ResponseInterface;

/**
* TUS server Interface
*
* @since  4.0.0
*/
interface ServerInterface
{
  /**
   * Constructor
   *
   * @param  string   $directory   The directory to use for save the file
   * @param  string   $location    The uri to reach the TUS server
   * @param  bool     $debug       Switches debug mode - {@see Server::debugMode}
   *
   * @throws File
   * @access public
   */
  public function __construct(string $directory, string $location, bool $debug = false);

  /**
   * Process the client request
   *
   * @param   bool            $send  True to send the response, false to return the response
   *
   * @return  void|Response   void if send = true else Response object
   * 
   */
  public function process(bool $send = false);

  /**
   * Loads an upload into the object
   *
   * @param   string  $uuid  The uuid of the upload to load
   * 
   * @return  bool    True on success, false otherwise
   */
  public function loadUpload(?string $uuid = null): bool;

  /**
   * Sets upload size limit
   *
   * @param int $value
   *
   * @return void
   */
  public function setAllowMaxSize(int $value);

  /**
   * Allows GET method (it means allow download uploaded files)
   *
   * @param bool $allow
   *
   * @return void
   */
  public function setAllowGetMethod($allow);

  /**
   * Sets the Access-Control-Allow-Origin header (CORS)
   * 
   * @param  string  $domain  Domain to allow access from
   *
   * @return void
   */
  public function setAccessControlHeader(string $domain);

  /**
   * Get the location (uri) of the TUS server
   * 
   * @return string
   */
  public function getLocation(): string;

  /**
   * Get real name of transferred file
   *
   * @return string  Real name of file
   */
  public function getRealFileName(): string;

  /**
   * Get a metaData value from property
   *
   * @param string $key    The key for which you want value
   * @param bool   $throw  True if exception should be thrown
   *
   * @return mixed The value for the id-key, false on failure
   * 
   * @throws \Exception key is not defined in medatada
   */
  public function getMetaDataValue($key, $throw=false);
}
