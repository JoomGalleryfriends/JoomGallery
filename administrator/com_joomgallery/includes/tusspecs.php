<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Array containing TUS server specifications
 *
 * @package JoomGallery
 * @since   4.0.0
 */
$tus_specs_array = array
(
  'Headers' => array(
    'Tus-Version'     => array( 'Name'        => 'HTTP_TUS_VERSION',
                                'Description' => 'Comma-separated list of protocol versions supported by the Server',
                                'Request'     => array('OPTIONS'),
                                'Type'        => 'string',
                                'Required'    => true,
                                'Default'     => '',
                                'Value'       => ''
                              ),
    'Tus-Resumable'   => array( 'Name'        => 'HTTP_TUS_RESUMABLE',
                                'Description' => 'Version of the protocol used by the Client or the Server',
                                'Request'     => array('OPTIONS'),
                                'Type'        => 'string',
                                'Required'    => true,
                                'Default'     => '',
                                'Value'       => ''
                              ),
    'Tus-Max-Size'    => array( 'Name'        => 'HTTP_TUS_MAX_SIZE',
                                'Description' => 'The maximum allowed size of an entire upload in bytes',
                                'Request'     => array('OPTIONS'),
                                'Type'        => 'integer',
                                'Required'    => false,
                                'Default'     => null,
                                'Value'       => null
                              ),
    'Tus-Extension'   => array( 'Name'        => 'HTTP_TUS_EXTENSION',
                                'Description' => 'Comma-separated list of the extensions supported by the Server',
                                'Request'     => array('OPTIONS'),
                                'Type'        => 'string',
                                'Required'    => false,
                                'Default'     => null,
                                'Value'       => null
                              ),
    'Upload-Offset'   => array( 'Name'        => 'HTTP_UPLOAD_OFFSET',
                                'Description' => 'Number of successfully transferred bytes of the upload',
                                'Request'     => array('HEAD','PATCH'),
                                'Type'        => 'integer',
                                'Required'    => false,
                                'Default'     => 0,
                                'Value'       => 0
                              ),
    'Upload-Length'   => array( 'Name'        => 'HTTP_UPLOAD_LENGTH',
                                'Description' => 'Size of the entire upload in bytes',
                                'Request'     => array('HEAD','POST'),
                                'Type'        => 'integer',
                                'Required'    => false,
                                'Default'     => 0,
                                'Value'       => 0
                              ),
    'Upload-Metadata' => array( 'Name'        => 'HTTP_UPLOAD_METADATA',
                                'Description' => 'Data consist of one or more comma-separated key-value pairs',
                                'Request'     => array('POST'),
                                'Type'        => 'string',
                                'Required'    => false,
                                'Default'     => '',
                                'Value'       => ''
                              ),
    'Content-Type'    => array( 'Name'        => 'HTTP_CONTENT_TYPE',
                                'Description' => 'Media type of the upload',
                                'Request'     => array('POST','PATCH'),
                                'Type'        => 'string',
                                'Required'    => false,
                                'Default'     => 'application/offset+octet-stream',
                                'Value'       => ''
                              ),
    'Content-Length'  => array( 'Name'        => 'HTTP_CONTENT_LENGTH',
                                'Description' => 'Number of remaining bytes of the upload',
                                'Request'     => array('POST','PATCH'),
                                'Type'        => 'integer',
                                'Required'    => false,
                                'Default'     => null,
                                'Value'       => 0
                              ),
  ),
  'Codes' => array(
    200 => 'OK',
    201 => 'Created',
    204 => 'No Content',
    400 => 'Bad Request',
    403 => 'Forbidden',
    404 => 'Not Found',
    409 => 'Conflict',
    410 => 'Gone',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    415 => 'Unsupported Media Type',
    460 => 'Checksum Mismatch',
  )
);
