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

use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\FileNotFoundException;

/**
 * Service with tools for file download support
 *
 * @author   Jarosław Wasilewski <orajo@windowslive.com>
 * @package  ZfTusServer
 * @access   public
 */
class FileToolsService
{

    /**
     * Download using Content-Disposition: Attachment
     */
    const OPEN_MODE_ATTACHMENT = 'Attachment';

    /**
     * Download using Content-Disposition: Inline (open in browser, if possible)
     */
    const OPEN_MODE_INLINE = 'Inline';

    /**
     * Handles file download to browser
     *
     * @link https://gist.github.com/854168 this method is based on this code
     * @access public
     * @api
     * @param string $filePath full local path to downloaded file (typically contains hashed file name)
     * @param string $fileName original file name
     * @param string|null $mime MIME type; if null tries to guess using @see FileToolsService::downloadFile()
     * @param int $size file size in bytes
     * @return boolean
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public static function downloadFile($filePath, $fileName, $mime = '', $size = -1, $openMode = self::OPEN_MODE_ATTACHMENT)
    {
        if (!file_exists($filePath)) {
            $this->component->addLog('File not found: ' . $filePath, 'error', 'jerror');
            throw new FileNotFoundException(null, 0, null, $filePath);
        }
        if (!is_readable($filePath)) {
            $this->component->addLog(sprintf('File %s is not readable', $filePath), 'error', 'jerror');
            throw new FileNotFoundException(sprintf('File %s is not readable', $filePath), 0, null, $filePath);
        }

        // Fetching File
        $mtime = ($mtime = filemtime($filePath)) ? $mtime : gmtime();

        if ($mime === '') {
            header("Content-Type: application/force-download");
            header('Content-Type: application/octet-stream');
        }
        else {
            if(is_null($mime)) {
                $mime = self::detectMimeType($filePath, $fileName);
            }
            header('Content-Type: ' . $mime);
        }

        if (strstr(filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_UNSAFE_RAW), "MSIE") != false) {
            header("Content-Disposition: ".$openMode."; filename=" . urlencode($fileName) . '; modification-date="' . date('r', $mtime) . '";');
        }
        else {
            header("Content-Disposition: ".$openMode."; filename=\"" . $fileName . '"; modification-date="' . date('r', $mtime) . '";');
        }

        if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules())) {
            // Sending file via mod_xsendfile
            header("X-Sendfile: " . $filePath);
        }
        else {
            // Sending file directly via script
            // according memory_limit byt not higher than 1GB
            $memory_limit = ini_get('memory_limit');
            // get file size
            if ($size === -1) {
                $size = filesize($filePath);
            }

            if (intval($size + 1) > self::toBytes($memory_limit) && intval($size * 1.5) <= 1073741824) {
                // Setting memory limit
                ini_set('memory_limit', intval($size * 1.5));
            }

            @ini_set('zlib.output_compression', 0);
            header("Content-Length: " . $size);
            // Set the time limit based on an average D/L speed of 50kb/sec
            set_time_limit(min(7200, // No more than 120 minutes (this is really bad, but...)
                            ($size > 0) ? intval($size / 51200) + 60 // 1 minute more than what it should take to D/L at 50kb/sec
                                    : 1 // Minimum of 1 second in case size is found to be 0
            ));
            $chunkSize = 1 * (1024 * 1024); // how many megabytes to read at a time
            if ($size > $chunkSize) {
                // Chunking file for download
                $handle = fopen($filePath, 'rb');
                if ($handle === false) {
                    return false;
                }
                $buffer = '';
                while (!feof($handle)) {
                    $buffer = fread($handle, $chunkSize);
                    echo $buffer;

                    // if somewhare before was ob_start()
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
                fclose($handle);
            }
            else {
                // Streaming whole file for download
                readfile($filePath);
            }
        }

        exit;
    }

    /**
     * Internal method to detect the mime type of a file
     *
     * @param string $fileName File name on storage; could be a hash or anything
     * @param string $userFileName Real name of file, understandable for users. If omitted $fileName will be used.
     * @return string Mimetype of given file
     */
    public static function detectMimeType($fileName, $userFileName = ''): string
    {
        if (!file_exists($fileName)) {
            return '';
        }

        $mime = '';

        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;

            if (empty($mime)) {
                $mime = @finfo_open($const);
            }

            if (!empty($mime)) {
                $result = finfo_file($mime, $fileName);
            }
            unset($mime);
        }

        if (empty($result) && (function_exists('mime_content_type') && ini_get('mime_magic.magicfile'))) {
            $result = mime_content_type($fileName);
        }

        // dodatkowe sprawdzenie i korekta dla docx, xlsx, pptx
        if (empty($result) || $result == 'application/zip') {
            if (empty($userFileName)) {
                $userFileName = $fileName;
            }

            $pathParts = pathinfo($userFileName);
            if (isset($pathParts['extension'])) {
                switch ($pathParts['extension']) {
                    case '7z':
                        $result = 'application/x-7z-compressed';
                        break;
                    case 'xlsx':
                    case 'xltx':
                    case 'xlsm':
                    case 'xltm':
                    case 'xlam':
                    case 'xlsb':
                        $result = 'application/msexcel';
                        break;
                    case 'docx':
                    case 'dotx':
                    case 'docm':
                    case 'dotm':
                        $result = 'application/msword';
                        break;
                    case 'pptx':
                    case 'pptx':
                    case 'potx':
                    case 'ppsx':
                    case 'ppam':
                    case 'pptm':
                    case 'potm':
                    case 'ppsm':
                        $result = 'application/mspowerpoint';
                        break;
                    case 'vsd':
                    case 'vsdx':
                        $result = 'application/x-visio';
                        break;
                }
            }
        }

        if (empty($result)) {
            $result = 'application/octet-stream';
        }

        return $result;
    }

    /**
     * Converts {@see memory_limit} result to bytes
     *
     * @param string $val
     * @return int
     */
    private static function toBytes($val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int)$val;
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Format file size according to specified locale
     *
     * @param int|null $size          File size in [B] bytes
     * @param string   $locale        name of locale settings
     * @param string   $emptyValue    what is returned if $size is empty or zero
     *
     * @return string value and unit
     *
     * @assert (1024, 'pl_PL') == '1 kB'
     * @assert (356, 'pl_PL') == '356 B'
     * @assert (6587, 'pl_PL') == '6,43 kB'
     */
    public static function formatFileSize($size, string $locale, string $emptyValue = '-'): string
    {
        $sizes = array(' B', ' kB', ' MB', ' GB', ' TB', ' PB');
        if (is_null($size) || $size == 0) {
            return($emptyValue);
        }

        $precision = 2;
        if ($size == (int) $size && $size < 1024) { // < 1MB
            $precision = 0;
        }

        $size = round($size / pow(1024, ($i = floor(log($size, 1024)))), $precision);
        // if (class_exists('NumberFormat')) {
        //     $filter = new NumberFormat($locale, NumberFormatter::DECIMAL, NumberFormatter::TYPE_DOUBLE);
        //     return $filter->filter($size) . $sizes[$i];
        // }
        return $size . $sizes[$i];
    }
}
