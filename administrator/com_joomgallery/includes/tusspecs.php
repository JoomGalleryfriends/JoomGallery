<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Array containing TUS server specifications
 *
 * @package JoomGallery
 * @since   4.0.0
 */
$tus_specs_array = [
  'Headers' => [
    'Tus-Version'     => [  'Name'        => 'HTTP_TUS_VERSION',
                            'Description' => 'Comma-separated list of protocol versions supported by the Server',
                            'Request'     => ['OPTIONS'],
                            'Type'        => 'string',
                            'Required'    => true,
                            'Default'     => '',
                            'Value'       => '',
                          ],
    'Tus-Resumable'   => [  'Name'        => 'HTTP_TUS_RESUMABLE',
                            'Description' => 'Version of the protocol used by the Client or the Server',
                            'Request'     => ['OPTIONS'],
                            'Type'        => 'string',
                            'Required'    => true,
                            'Default'     => '',
                            'Value'       => '',
                          ],
    'Tus-Max-Size'    => [  'Name'        => 'HTTP_TUS_MAX_SIZE',
                            'Description' => 'The maximum allowed size of an entire upload in bytes',
                            'Request'     => ['OPTIONS'],
                            'Type'        => 'integer',
                            'Required'    => false,
                            'Default'     => null,
                            'Value'       => null,
                          ],
    'Tus-Extension'   => [  'Name'        => 'HTTP_TUS_EXTENSION',
                            'Description' => 'Comma-separated list of the extensions supported by the Server',
                            'Request'     => ['OPTIONS'],
                            'Type'        => 'string',
                            'Required'    => false,
                            'Default'     => null,
                            'Value'       => null,
                          ],
    'Upload-Offset'   => [  'Name'        => 'HTTP_UPLOAD_OFFSET',
                            'Description' => 'Number of successfully transferred bytes of the upload',
                            'Request'     => ['HEAD','PATCH'],
                            'Type'        => 'integer',
                            'Required'    => false,
                            'Default'     => 0,
                            'Value'       => 0,
                          ],
    'Upload-Length'   => [  'Name'        => 'HTTP_UPLOAD_LENGTH',
                            'Description' => 'Size of the entire upload in bytes',
                            'Request'     => ['HEAD','POST'],
                            'Type'        => 'integer',
                            'Required'    => false,
                            'Default'     => 0,
                            'Value'       => 0,
                          ],
    'Upload-Metadata' => [  'Name'        => 'HTTP_UPLOAD_METADATA',
                            'Description' => 'Data consist of one or more comma-separated key-value pairs',
                            'Request'     => ['POST'],
                            'Type'        => 'string',
                            'Required'    => false,
                            'Default'     => '',
                            'Value'       => '',
                          ],
    'Content-Type'    => [  'Name'        => 'HTTP_CONTENT_TYPE',
                            'Description' => 'Media type of the upload',
                            'Request'     => ['POST','PATCH'],
                            'Type'        => 'string',
                            'Required'    => false,
                            'Default'     => 'application/offset+octet-stream',
                            'Value'       => '',
                          ],
    'Content-Length'  => [  'Name'        => 'HTTP_CONTENT_LENGTH',
                            'Description' => 'Number of remaining bytes of the upload',
                            'Request'     => ['POST','PATCH'],
                            'Type'        => 'integer',
                            'Required'    => false,
                            'Default'     => null,
                            'Value'       => 0,
                          ],
  ],
  'Codes' => [
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
  ],
];
