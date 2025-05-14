<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\TusServer;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Psr\Http\Message\ResponseInterface;

use \Joomgallery\Component\Joomgallery\Administrator\Extension\ResponseTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\ServerInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\FileToolsService;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\Abort;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\BadHeader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\File;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\Max;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\Request;

/**
 * Tus-Server v1.0.0 implementation
 * Changed and adjusted through JoomGallery::ProjectTeam
 *
 * @version   1.0.0
 * @link      https://github.com/Orajo/zf2-tus-server
 * @author    Jaroslaw Wasilewski / @Orajo (orajo@windowslive.com)
 * @author    Simon Leblanc (contact@leblanc-simon.eu)
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Jaroslaw Wasilewski, modified by JoomGallery::ProjectTeam
 * @link      https://tus.io/protocols/resumable-upload.html
 * @package   ZfTusServer
 */
class Server implements ServerInterface
{
    use ResponseTrait;

    public const TIMEOUT = 30;
    public const TUS_VERSION = '1.0.0';
    public const TUS_EXTENSIONS = 'creation,termination';

    /**
     * Array containing all relevant tus headers
     *
     * @var array
     */
    private $specs;

    /**
     * Unique upload identifier
     * Identification of the upload
     *
     * @var string
     */
    private $uuid;

    /**
     * Directory to use for save the file
     * Info: With slash (/) at the end
     *
     * @var string
     */
    private $directory = '';

    /**
     * Location of the TUS server - URI to reach the TUS server without domain
     * Info: With slash (/) in the beginning
     * Example: /index.php?target=tus
     *
     * @var string
     */
    private $location = '/';

    /**
     * Name of the domain, on which the file upload is provided
     * Info: Without slash (/) at the end
     * Example: http://example.org
     *
     * @var string
     */
    private $domain = '';

    /**
     * Access-Control-Allow-Origin header value
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
     *
     * @var string
     */
    private $allowAccess = '*';

    /**
     * Switch GET method.
     * GET method needed to download uploaded files.
     *
     * @var bool
     */
    private $allowGetMethod = true;

    /**
     * Max filesize allowed to upload in bytes
     *
     * @var int
     */
    private $allowMaxSize = 262144000; // 250MB

    /**
     * Storage to collect upload meta data
     *
     * @var array
     */
    private $metaData;

    /**
     * Switches debug mode.
     * In this mode downloading info files is allowed (useful for testing)
     *
     * @var bool
     */
    private $debugMode;

    /**
     * Filetype of the uploaded file
     *
     * @var string
     */
    private $fileType  = '';

    /**
     * Name of the uploaded file
     *
     * @var string
     */
    private $realFileName = '';

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
    public function __construct(string $directory, string $location, bool $debug = false)
    {
        $this->setDirectory($directory);
        $this->setLocation($location);

        $this->app = Factory::getApplication();
        $this->debugMode = $debug;

        require JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/tusspecs.php';
        $this->specs = $tus_specs_array;
    }

    /**
     * Process the client request
     *
     * @param   bool             $send    True to send the response, false to return the response
     *
     * @return  void|Response    void if send = true else Response object
     *
     * @throws Exception\Request If the method isn't available
     * @throws BadHeader
     */
    public function process($send = true)
    {
        try
        {
            $method = $this->app->input->getMethod();

            $isOption = false;
            switch ($method)
            {
                case 'POST':
                    if (!$this->checkTusVersion())
                    {
                      $this->component->addLog('The requested protocol version is not supported 405', 'error', 'jerror');
                      throw new Request('The requested protocol version is not supported', 405);
                    }
                    $this->buildUuid();
                    $this->processPost();
                    break;

                case 'HEAD':
                    if (!$this->checkTusVersion())
                    {
                      $this->component->addLog('The requested protocol version is not supported 405', 'error', 'jerror');
                      throw new Request('The requested protocol version is not supported', 405);
                    }
                    $this->getUserUuid();
                    $this->processHead();
                    break;

                case 'PATCH':
                    if (!$this->checkTusVersion())
                    {
                      $this->component->addLog('The requested protocol version is not supported 405', 'error', 'jerror');
                      throw new Request('The requested protocol version is not supported', 405);
                    }
                    $this->getUserUuid();
                    $this->processPatch();
                    break;

                case 'OPTIONS':
                    $isOption = true;
                    $this->processOptions();
                    break;

                case 'GET':
                    $this->getUserUuid();
                    $this->processGet();
                    break;

                case 'DELETE':
                  $this->getUserUuid();
                  $this->processDelete();
                  break;

                default:
                    $this->component->addLog('The requested method ' . $method . ' is not allowed 405', 'error', 'jerror');
                    throw new Request('The requested method ' . $method . ' is not allowed', 405);
            }

            $this->addCommonHeader($isOption);

            if($send === false)
            {
                return $this->getResponse();
            }

        }
        catch (BadHeader $exp)
        {
            if($send === false)
            {
                throw $exp;
            }

            $this->setStatusCode(400);
            $this->addCommonHeader();

        }
        catch (Request $exp)
        {
            if($send === false)
            {
                throw $exp;
            }

            $this->setStatusCode($exp->getCode());
            $this->addCommonHeader();
        }
        catch (File $exp)
        {
            if($send === false)
            {
                throw $exp;
            }

            $this->setStatusCode(500);
            $this->setContent($exp->getMessage());
            $this->addCommonHeader();
        }
        catch (\Exception $exp)
        {
            if($send === false)
            {
                throw $exp;
            }

            $this->setStatusCode(500);
            $this->setContent($exp->getMessage());
            $this->addCommonHeader();
        }

        $this->app->sendHeaders();
		    echo $this->app->getBody();

        // The process must only sent the HTTP headers and content: kill request after send
        exit;
    }

    /**
     * Loads an upload into the object
     *
     * @param   string  $uuid  The uuid of the upload to load
     *
     * @return  bool    True on success, false otherwise
     */
    public function loadUpload(?string $uuid = null): bool
    {
      $this->uuid = $uuid;
      $this->getUserUuid();

      // Load the metadata and check for the uuid
      if($this->existsInMetaData('id') === false)
      {
          return false;
      }

      $this->setRealFileName();

      return true;
    }



    /**
     * Process the POST request
     *
     * @link https://tus.io/protocols/resumable-upload.html#post
     *
     * @throws  \Exception    If the uuid already exists
     * @throws  BadHeader     If the final length header isn't a positive integer
     * @throws  File          If the file already exists in the filesystem
     * @throws  File          If the creation of file failed
     *
     * @return void
     */
    private function processPost(): void
    {
        if($this->existsInMetaData('id') === true)
        {
            $this->component->addLog('The UUID already exists', 'error', 'jerror');
            throw new \RuntimeException('The UUID already exists');
        }

        $headers = $this->extractHeaders(['Upload-Length', 'Upload-Metadata']);

        if(is_numeric($headers['Upload-Length']) === false || $headers['Upload-Length'] < 0)
        {
            $this->component->addLog('Upload-Length must be a positive integer', 'error', 'jerror');
            throw new BadHeader('Upload-Length must be a positive integer');
        }

        $finalLength = (int)$headers['Upload-Length'];

        if($finalLength > $this->allowMaxSize)
        {
          $this->component->addLog('Request Entity Too Large 413', 'error', 'jerror');
          throw new Request('Request Entity Too Large', 413);
        }

        $this->setMetaData($this->parseMetaDataHeader($headers['Upload-Metadata']), false);
        $this->setRealFileName();

        $file = $this->directory . $this->getFilename();

        if(file_exists($file) === true)
        {
            $this->component->addLog('File already exists : ' . $file . ' 500', 'error', 'jerror');
            throw new File('File already exists : ' . $file, 500);
        }

        if (touch($file) === false)
        {
            $this->component->addLog('Impossible to touch ' . $file . ' 500', 'error', 'jerror');
            throw new File('Impossible to touch ' . $file, 500);
        }

        $this->setMetaDataValue('id', $this->uuid);
        $this->saveMetaData($finalLength, 0, false, true);

        $this->setStatusCode(201);

        $location = $this->app->input->server->get('REQUEST_URI', $this->getLocation(), 'string');
        $domain   = $this->getDomain() ?: '';

        $this->addHeaderLine('Location', $domain . $location . '&uuid=' . $this->uuid);

        unset($path);
    }


    /**
     * Process the HEAD request
     *
     * @link http://tus.io/protocols/resumable-upload.html#head
     *
     * @throws \Exception If the uuid isn't know
     *
     * @return void
     */
    private function processHead(): void
    {
        if ($this->existsInMetaData('id') === false)
        {
            $this->setStatusCode(404);
            return;
        }

        // if file in storage does not exists
        if (!file_exists($this->directory . $this->getFilename()))
        {
            // allow new upload
            $this->deleteMetaData($this->uuid);
            $this->setStatusCode(404);
            return;
        }

        $offset  = $this->getMetaDataValue('offset', true);
        $this->addHeaderLine('Upload-Offset', $offset);

        $length = $this->getMetaDataValue('size', true);
        $this->addHeaderLine('Upload-Length', $length);

        $this->addHeaderLine('Cache-Control', 'no-store');

        $this->setStatusCode(200);
    }

    /**
     * Process the PATCH request
     *
     * @link http://tus.io/protocols/resumable-upload.html#patch
     *
     * @throws \Exception If the uuid isn't know
     * @throws BadHeader If the Upload-Offset header isn't a positive integer
     * @throws BadHeader If the Content-Length header isn't a positive integer
     * @throws BadHeader If the Content-Type header isn't "application/offset+octet-stream"
     * @throws BadHeader If the Upload-Offset header and session offset are not equal
     * @throws File If it's impossible to open php://input
     * @throws File If it's impossible to open the destination file
     * @throws File If it's impossible to set the position in the destination file
     *
     * @return void
     */
    private function processPatch()
    {
        // Check the uuid
        if($this->existsInMetaData('id') === false)
        {
            $this->component->addLog('The UUID doesn\'t exists', 'error', 'jerror');
            throw new \RuntimeException('The UUID doesn\'t exists');
        }

        // Check HTTP headers
        $headers = $this->extractHeaders(['Upload-Offset', 'Content-Length', 'Content-Type']);

        if(is_numeric($headers['Upload-Offset']) === false || $headers['Upload-Offset'] < 0)
        {
            $this->component->addLog('Upload-Offset must be a positive integer', 'error', 'jerror');
            throw new BadHeader('Upload-Offset must be a positive integer');
        }

        if(isset($headers['Content-Length']) && (is_numeric($headers['Content-Length']) === false || $headers['Content-Length'] < 0))
        {
            $this->component->addLog('Content-Length must be a positive integer', 'error', 'jerror');
            throw new BadHeader('Content-Length must be a positive integer');
        }

        if(is_string($headers['Content-Type']) === false || $headers['Content-Type'] !== 'application/offset+octet-stream')
        {
            $this->component->addLog('Content-Type must be "application/offset+octet-stream"', 'error', 'jerror');
            throw new BadHeader('Content-Type must be "application/offset+octet-stream"');
        }

        // Offset of current PATCH request
        $offsetHeader = (int)$headers['Upload-Offset'];
        // Length of data of the current PATCH request
        $contentLength = isset($headers['Content-Length']) ? (int)$headers['Content-Length'] : null;
        // Last offset, taken from session
        $offsetSession = (int)$this->getMetaDataValue('offset', true);
        // Total length of file (expected data)
        $lengthSession = (int)$this->getMetaDataValue('size', true);

        $this->setRealFileName();

        // Check consistency (user vars vs session vars)
        if($offsetSession === null || $offsetSession !== $offsetHeader)
        {
            $this->setStatusCode(409);
            $this->addHeaderLine('Upload-Offset', $offsetSession);
            return;
        }

        // Check if the file is already entirely write
        if($offsetSession === $lengthSession || $lengthSession === 0)
        {
            // the whole file was uploaded
            $this->setStatusCode(204);
            $this->addHeaderLine('Upload-Offset', $offsetSession);
            return;
        }

        // Read / Write data
        $handleInput = fopen('php://input', 'rb');
        if($handleInput === false)
        {
            $this->component->addLog('Impossible to open php://input', 'error', 'jerror');
            throw new File('Impossible to open php://input');
        }

        $file = $this->directory . $this->getFilename();
        $handleOutput = fopen($file, 'ab');
        if ($handleOutput === false)
        {
            $this->component->addLog('Impossible to open file to write into', 'error', 'jerror');
            throw new File('Impossible to open file to write into');
        }

        if (fseek($handleOutput, $offsetSession) === false)
        {
            $this->component->addLog('Impossible to move pointer in the good position', 'error', 'jerror');
            throw new File('Impossible to move pointer in the good position');
        }

        ignore_user_abort(false);

        /* @var $currentSize Int Total received data length, including all chunks */
        $currentSize = $offsetSession;
        /* @var $totalWrite Int Length of saved data in current PATCH request */
        $totalWrite = 0;

        $returnCode = 204;
        $returnMsg  = 'No Content';

        try {
            while (true)
            {
                set_time_limit(self::TIMEOUT);

                // Manage user abort
                // according to comments on PHP Manual page (http://php.net/manual/en/function.connection-aborted.php)
                // this method doesn't work, but we cannot send 0 to browser, because it's not compatible with TUS.
                // But maybe some day (some PHP version) it starts working. Thath's why I leave it here.

                // echo "\n";
                // ob_flush();
                // flush();

                if(connection_status() !== CONNECTION_NORMAL)
                {
                    $this->component->addLog('User abort connexion', 'error', 'jerror');
                    throw new Abort('User abort connexion');
                }

                $data = fread($handleInput, 8192);
                if($data === false)
                {
                    $this->component->addLog('Impossible to read the data', 'error', 'jerror');
                    throw new File('Impossible to read the data');
                }

                $sizeRead = strlen($data);

                // If user sent 0 bytes and we do not write all data yet, abort
                if($sizeRead === 0)
                {
                    if($contentLength !== null && $totalWrite < $contentLength)
                    {
                        $this->component->addLog('tream unexpectedly ended. Maybe user aborted?', 'error', 'jerror');
                        throw new Abort('Stream unexpectedly ended. Maybe user aborted?');
                    }

                    // end of stream
                    break;
                }

                // If user sent more data than expected (by POST Final-Length), abort
                if($contentLength !== null && ($sizeRead + $currentSize > $lengthSession))
                {
                    $this->component->addLog('Size sent is greater than max length expected', 'error', 'jerror');
                    throw new Max('Size sent is greater than max length expected');
                }

                // If user sent more data than expected (by PATCH Content-Length), abort
                if($contentLength !== null && ($sizeRead + $totalWrite > $contentLength))
                {
                    $this->component->addLog('Size sent is greater than max length expected', 'error', 'jerror');
                    throw new Max('Size sent is greater than max length expected');
                }

                // Write data
                $sizeWrite = fwrite($handleOutput, $data);
                if($sizeWrite === false)
                {
                    $this->component->addLog('Unable to write data', 'error', 'jerror');
                    throw new File('Unable to write data');
                }

                $currentSize += $sizeWrite;
                $totalWrite += $sizeWrite;
                $this->setMetaDataValue('offset', $currentSize);

                if($currentSize === $lengthSession)
                {
                    $this->saveMetaData($lengthSession, $currentSize, true, false);
                    break;
                }

                $this->saveMetaData($lengthSession, $currentSize, false, true);
            }

            $this->addHeaderLine('Upload-Offset', $currentSize);

        }
        catch (Max $exp)
        {
            $returnCode = 400;
            $returnMsg  = $exp->getMessage();
        }
        catch (File $exp)
        {
            $returnCode = 500;
            $returnMsg  = $exp->getMessage();
        }
        catch (Abort $exp)
        {
            $returnCode = 100;
            $returnMsg  = $exp->getMessage();
        }
        catch (\Exception $exp)
        {
            $returnCode = 500;
            $returnMsg  = $exp->getMessage();
        }
        finally
        {
            fclose($handleInput);
            fclose($handleOutput);
        }

        $this->setStatusCode($returnCode);
        $this->setContent($returnMsg);
    }

    /**
     * Process the OPTIONS request
     *
     * @link http://tus.io/protocols/resumable-upload.html#options
     *
     * @return void
     */
    private function processOptions(): ResponseInterface
    {
        $this->uuid = null;

        $this->setStatusCode(204);
    }

    /**
     * Process the GET request
     *
     * @return void
     */
    private function processGet(): void
    {
        if (!$this->allowGetMethod)
        {
            $this->component->addLog('The requested method Get is not allowed 405', 'error', 'jerror');
            throw new Request('The requested method Get is not allowed', 405);
        }

        $file = $this->directory . $this->getFilename();
        if(!file_exists($file))
        {
            $this->component->addLog('The file ' . $this->uuid . ' doesn\'t exist 404', 'error', 'jerror');
            throw new Request('The file ' . $this->uuid . ' doesn\'t exist', 404);
        }

        if(!is_readable($file))
        {
            $this->component->addLog('The file ' . $this->uuid . ' is unaccessible 403', 'error', 'jerror');
            throw new Request('The file ' . $this->uuid . ' is unaccessible', 403);
        }

        if(!file_exists($file . '.info') || !is_readable($file . '.info'))
        {
            $this->component->addLog('The file ' . $this->uuid . ' has no metadata 500', 'error', 'jerror');
            throw new Request('The file ' . $this->uuid . ' has no metadata', 500);
        }

        $fileName = $this->getMetaDataValue('filename', true);

        if ($this->debugMode)
        {
            $isInfo = $this->app->get('info', -1, 'integer');
            if($isInfo !== -1)
            {
                FileToolsService::downloadFile($file . '.info', $fileName . '.info');
            }
            else
            {
                $mime = FileToolsService::detectMimeType($file);
                FileToolsService::downloadFile($file, $fileName, $mime);
            }
        }
        else
        {
            $mime = FileToolsService::detectMimeType($file);
            FileToolsService::downloadFile($file, $fileName, $mime);
        }

        exit;
    }

    /**
     * Process the DELETE request
     *
     * @link http://tus.io/protocols/resumable-upload.html#delete
     *
     * @return void
     */
    private function processDelete(): void
    {
      if($this->existsInMetaData('id') === false)
      {
          $this->setStatusCode(404);
          return;
      }

      // if file in storage does not exists
      if(!file_exists($this->directory . $this->getFilename()))
      {
          // allow new upload
          $this->deleteMetaData($this->uuid);
          $this->setStatusCode(404);
          return;
      }

      // Delete files of upload in storage
      $this->deleteUpload($this->uuid);

      $this->setStatusCode(204);
    }

    ///////////////////////////////////////////
    ///////////////////////////////////////////

    /**
     * Checks compatibility with requested Tus protocol
     *
     * @return boolean
     */
    private function checkTusVersion(): bool
    {
        $tusVersion = $this->app->input->server->get($this->specs['Headers']['Tus-Resumable']['Name'], $this->specs['Headers']['Tus-Resumable']['Default'], $this->specs['Headers']['Tus-Resumable']['Type']);

        if($tusVersion === self::TUS_VERSION)
        {
          return true;
        }
        else
        {
          return false;
        }
    }

    /**
     * Add the commons headers to the HTTP response
     *
     * @param bool $isOption Is OPTION request
     */
    private function addCommonHeader($isOption = false): void
    {
        $this->addHeaderLine('Tus-Resumable', self::TUS_VERSION);
        $this->addHeaderLine('Access-Control-Allow-Origin', $this->allowAccess);
        $this->addHeaderLine('Access-Control-Expose-Headers', 'Upload-Offset, Location, Upload-Length, Tus-Version, Tus-Resumable, Tus-Max-Size, Tus-Extension, Upload-Metadata');

        if($isOption)
        {
            $allowedMethods = 'OPTIONS,HEAD,POST,PATCH,DELETE';

            if($this->getAllowGetMethod())
            {
                $allowedMethods .= ',GET';
            }

            $this->addHeaderLine('Tus-Version', self::TUS_VERSION);
            $this->addHeaderLine('Tus-Extension', self::TUS_EXTENSIONS);
            $this->addHeaderLine('Allow', $allowedMethods);
            $this->addHeaderLine('Access-Control-Allow-Methods', $allowedMethods);
            $this->addHeaderLine('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Final-Length, Upload-Offset, Upload-Length, Tus-Resumable, Upload-Metadata');

            if($this->allowMaxSize > 0)
            {
                $this->addHeaderLine('Tus-Max-Size', $this->allowMaxSize);
            }
        }

        return;
    }

    /**
     * Build a new UUID (use in the POST request)
     *
     * @return void
     */
    private function buildUuid(): void
    {
        $this->uuid = hash('md5', uniqid(mt_rand() . php_uname(), true));
    }

    /**
     * Get the UUID of the request (use for HEAD and PATCH request)
     *
     * @return  string  The UUID of the request
     *
     * @throws \InvalidArgumentException If the UUID is empty
     */
    private function getUserUuid(): string
    {
        if($this->uuid === null)
        {
            $uuid = $this->app->input->get('uuid', '', 'string');

            if(strlen($uuid) === 32 && preg_match('/[a-z0-9]/', $uuid))
            {
                $this->uuid = $uuid;
            }
            else
            {
                $this->component->addLog('The uuid cannot be empty.', 'error', 'jerror');
                throw new \InvalidArgumentException('The uuid cannot be empty.');
            }
        }

        return $this->uuid;
    }

    /**
     * Get metaData from property
     * Reads metaData from file if property is empty
     *
     * @return array
     */
    private function getMetaData(): array
    {
        if(empty($this->metaData))
        {
            $this->metaData = $this->readMetaData($this->getUserUuid());
        }

        return $this->metaData;
    }

    /**
     * Set metaData array to property
     *
     * @param  array  $metadata  The metadata array
     * @param  bool   $replace   True to replace, false to append
     *
     * @return void
     */
    private function setMetaData($metadata, $replace=true)
    {
      // Make keys lowercase
      foreach($metadata as $key => $value)
      {
        $metadata[\strtolower($key)] = $value;
      }

      if($replace)
      {
        $this->metaData = $metadata;
      }
      else
      {
        $this->metaData = \array_merge($this->metaData, $metadata);
      }
    }

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
    public function getMetaDataValue($key, $throw=false)
    {
        $data = $this->getMetaData();
        if(isset($data[$key]))
        {
            return $data[$key];
        }

        if($throw)
        {
          $this->component->addLog($key . ' is not defined in medatada', 'error', 'jerror');
          throw new \RuntimeException($key . ' is not defined in medatada');
        }
        else
        {
          return false;
        }
    }

    /**
     * Set a metaData value in the property
     *
     * @param  string  $key    The key for which you want set the value
     * @param  mixed   $value  The value for the id-key to save
     *
     * @return void
     */
    private function setMetaDataValue($key, $value): void
    {
        $data = $this->getMetaData();
        $key  = \strtolower($key);

        if($key == 'size')
        {
          if($data['size'] === 0)
          {
            $data['size'] = $value;
          }
        }
        else
        {
          $data[$key] = $value;
        }

        $this->metaData = $data;
    }

    /**
     * Check if $key exists in metaData property
     *
     * @param $key
     *
     * @return bool  True if the id exists, false else
     */
    private function existsInMetaData($key): bool
    {
        $data = $this->getMetaData();

        return isset($data[$key]) && !empty($data[$key]);
    }

    /**
     * Parse the Tus Upload-Metadata header
     *
     * @param  string   $header   Upload-Metadata header string
     *
     * @return array    Associative array with all Upload-Metadata
     */
    private function parseMetaDataHeader($header)
    {
      $parts = explode(',', $header);

      if(\count($parts) <= 1)
      {
        // if only one metadata exists, it is the filename
        return array('filename' => $header);
      }

      // multiple metadata submitted
      $metadata = array();
      foreach($parts as $part)
      {
        $pair = explode(' ', $part);
        $metadata[\strtolower($pair[0])] = base64_decode($pair[1]);
      }

      return $metadata;
    }

    /**
     * Reads or initialize metaData from file.
     *
     * @param  string  $filename  The filename of the file
     *
     * @return array
     */
    private function readMetaData($filename): array
    {
        $refData = [
            'id' => '',
            'size' => 0,
            'offset' => 0,
            'extension' => '',
            'filename' => '',
            'mimetype' => '',
            'ispartial' => true,
            'isfinal' => false,
        ];

        $file = $this->directory . $filename . '.info';

        if(\file_exists($file))
        {
            $json = \file_get_contents($file);
            $data = \json_decode($json, true);

            if(\is_array($data))
            {
                return \array_merge($refData, $data);
            }
        }

        return $refData;
    }

    /**
     * Saves metadata to file.
     * Metadata are saved into a file with name mask 'uuid'.info
     *
     * @param  int   $size
     * @param  int   $offset
     * @param  bool  $isFinal
     * @param  bool  $isPartial
     *
     * @throws \Exception
     */
    private function saveMetaData(int $size, int $offset = 0, bool $isFinal = false, bool $isPartial = false): void
    {
        $this->setMetaDataValue('id', $this->getUserUuid());
        $this->setMetaDataValue('offset', $offset);
        $this->setMetaDataValue('ispartial', $isPartial);
        $this->setMetaDataValue('isfinal', $isFinal);
        $this->setMetaDataValue('size', $size);


        if(empty($this->metaData['filename']))
        {
            $this->setMetaDataValue('filename', $this->getRealFileName());
        }

        if(empty($this->metaData['extension']))
        {
            $info = new \SplFileInfo($this->getRealFileName());
            $this->setMetaDataValue('extension', $info->getExtension());
        }

        if($isFinal)
        {
            if(!$this->fileType)
            {
                $this->fileType = FileToolsService::detectMimeType($this->directory.$this->getUserUuid(), $this->getRealFileName());
            }
            $this->setMetaDataValue('mimetype', $this->fileType);
        }

        $json = \json_encode($this->getMetaData());

        file_put_contents($this->directory . $this->getUserUuid() . '.info', $json);
    }

    /**
     * Deletes a metaData file
     *
     * @param  string  $filename  The filename of the file
     *
     * @return bool    True on success, false otherwise
     */
    private function deleteMetaData($filename): bool
    {
        $file = $this->directory . $filename . '.info';

        if(file_exists($file) && is_writable($file))
        {
            unset($file);
            return true;
        }

        return false;
    }

    /**
     * Set realFileName and fileType from metaData
     *
     * @return void
     */
    private function setRealFileName()
    {
        if($this->existsInMetaData('filename'))
        {
          $this->realFileName = $this->getMetaDataValue('filename', true);
        }

        if($this->existsInMetaData('filetype'))
        {
          $this->fileType = $this->getMetaDataValue('filetype', true);
        }
    }

    /**
     * Get real name of transferred file
     *
     * @return string  Real name of file
     */
    public function getRealFileName(): string
    {
        return $this->realFileName;
    }

    /**
     * Get the filename to use when save the uploaded file
     *
     * @return  string  The filename to use
     *
     * @throws \DomainException If the uuid isn't define
     */
    private function getFilename(): string
    {
        if($this->uuid === null)
        {
            $this->component->addLog('Uuid can\'t be null when call ' . __METHOD__, 'error', 'jerror');
            throw new \DomainException('Uuid can\'t be null when call ' . __METHOD__);
        }

        return $this->uuid;
    }

    /**
     * Get the filename to use when save the uploaded file
     *
     * @param   string  $uuid   UUID of the upload to delete
     *
     * @return  void
     *
     * @throws File If one or multiple files are not deletable
     */
    private function deleteUpload($uuid)
    {
      // List of name of files inside upload folder
      $files = glob($this->directory.'*');

      $num_files = 0;
      foreach($files as $file)
      {
        if(\strpos(\basename($file), $uuid) !== false)
        {
          // Delete file with uuid in its name
          if(!\unlink($file))
          {
            $this->component->addLog('File with name "' . $file . '" can not be deleted. 500', 'error', 'jerror');
            throw new File('File with name "' . $file . '" can not be deleted.', 500);
          }
        }
      }
    }

    /**
     * Extract a list of headers in the HTTP headers
     *
     * @param   array  $headers   A list of header name to extract
     *
     * @return  array  A list if header ([header name => header value])
     *
     * @throws BadHeader
     */
    private function extractHeaders($headers): array
    {
        if(is_array($headers) === false)
        {
            $this->component->addLog('Headers must be an array', 'error', 'jerror');
            throw new \InvalidArgumentException('Headers must be an array');
        }

        $headersValues = [];
        foreach ($headers as $headerName)
        {
            $value = $this->app->input->server->get($this->specs['Headers'][$headerName]['Name'], $this->specs['Headers'][$headerName]['Default'], $this->specs['Headers'][$headerName]['Type']);

            if($this->specs['Headers'][$headerName]['Type'] == 'string' && trim($value) === '')
            {
                throw new BadHeader($headerName . ' can\'t be empty');
            }

            $headersValues[$headerName] = $value;
        }

        return $headersValues;
    }

    /**
     * Is GET method allowed
     *
     * @return bool
     */
    public function getAllowGetMethod(): bool
    {
        return $this->allowGetMethod;
    }

    /**
     * Allows GET method (it means allow download uploaded files)
     *
     * @param bool $allow
     *
     * @return void
     */
    public function setAllowGetMethod($allow)
    {
        $this->allowGetMethod = (bool)$allow;

        return $this;
    }

    /**
     * Sets upload size limit
     *
     * @param int $value
     *
     * @return void
     * @throws \BadMethodCallException
     */
    public function setAllowMaxSize(int $value)
    {
        if ($value > 0) {
            $this->allowMaxSize = $value;
        }
        else {
            $this->component->addLog('given $value must be integer, greater them 0', 'error', 'jerror');
            throw new \BadMethodCallException('given $value must be integer, greater them 0');
        }

        return $this;
    }

    /**
     * Set the directory where the file will be store
     *
     * @param   string   $directory   The directory where the file are stored
     *
     * @return  Server
     *
     * @throws File
     * @throws \InvalidArgumentException
     */
    private function setDirectory(string $directory): Server
    {
        if(is_dir($directory) === false || is_writable($directory) === false)
        {
            $this->component->addLog($directory . ' doesn\'t exist or isn\'t writable', 'error', 'jerror');
            throw new File($directory . ' doesn\'t exist or isn\'t writable');
        }

        $this->directory = $directory . (substr($directory, -1) !== DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '');

        return $this;
    }

    /**
     * Get the directory where the file is stored
     *
     * @return string
     */
    public function getDirectory(): string
    {
      if(\substr($this->directory, -1) != '/')
      {
        // directory should end with a slash (/)
        return $this->directory. '/';
      }

      return $this->directory;
    }

    /**
     * Get the location (uri) of the TUS server
     *
     * @return string
     */
    public function getLocation(): string
    {
      if(\substr($this->location, 0, 1) != '/')
      {
        // location should always starts with a slash (/)
        return '/' . $this->location;
      }

      return $this->location;
    }

    /**
     * Sets the location (uri) of the TUS server
     *
     * @param string $location
     *
     * @return void
     *
     * @throws \Exception
     */
    private function setLocation(string $location)
    {
      if(\strpos($location, 'http') !== false || \strpos($location, '://') !== false || \strpos($location, 'www.') !== false)
      {
        // looks like $location contains the domain
        $this->component->addLog('Location should not contain the domain. Please provide the domain separately using setDomain() method.', 'error', 'jerror');
        throw new \Exception('Location should not contain the domain. Please provide the domain separately using setDomain() method.', 1);
      }

      if(\substr($location, 0, 1) != '/')
      {
        // location should always starts with a slash (/)
        $location = '/' . $location;
      }

      $this->location = $location;

      return;
    }

    /**
     * Get the domain of the server
     *
     * @return string
     */
    public function getDomain(): string
    {
      if(\substr($this->domain, -1) == '/')
      {
        // domain should never ends with a slash (/)
        return \substr($this->domain, 0, -1);
      }

      return $this->domain;
    }

    /**
     * Sets the domain of the server
     *
     * @param string $domain
     *
     * @return void
     */
    public function setDomain(string $domain)
    {
      if(\substr($domain, -1) == '/')
      {
        // domain should never ends with a slash (/)
        $domain = \substr($domain, 0, -1);
      }

      $this->domain = $domain;

      return;
    }

    /**
     * Sets the Access-Control-Allow-Origin header (CORS)
     *
     * @param  string  $domain  Domain to allow access from
     *
     * @return void
     */
    public function setAccessControlHeader(string $domain)
    {
      $this->allowAccess = $domain;
    }
}
