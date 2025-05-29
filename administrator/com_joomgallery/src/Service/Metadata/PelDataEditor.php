<?php

/**
 ******************************************************************************************
 **   @version    4.0.0-dev                                                              **
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2024  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later                          **
 *****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Metadata;

use \lsolesen\pel\PelEntryAscii;
use \lsolesen\pel\PelEntryCopyright;
use \lsolesen\pel\PelEntryLong;
use \lsolesen\pel\PelEntryShort;
use \lsolesen\pel\PelEntryTime;
use \lsolesen\pel\PelEntryUserComment;
use \lsolesen\pel\PelFormat;
use \lsolesen\pel\PelIfd;
use \lsolesen\pel\PelTag;

// No direct access
\defined('_JEXEC') or die;

/**
 * Editor class to handle exif data type editing
 * 
 * @package JoomGallery
 * @since 4.0.0
 */
class PelDataEditor {

    /**
     * @var array
     */
    protected static $timeTags = [PelTag::DATE_TIME, PelTag::DATE_TIME_ORIGINAL, PelTag::DATE_TIME_DIGITIZED];

    /**
     * Makes an edit to the metadata directly.
     * Note: File must be saved with the PelJpeg/PelTiff object for the edits to persist.
     * 
     * @param   PelIfd $ifd    The IFD that contains the necessary entry
     * @param   int    $tag    The tag that contains the necessary data
     * @param   mixed  $data   The data to be saved
     * @param   int    $format The format in which the data must be saved (given as one of the PelTag consts)
     * 
     * @since   4.0.0
     */
    public function makeEdit(PelIfd $ifd, int $tag, mixed $data, int $format)
    {
        // Datetime, Copyright and UserComment entries all have specific ways that they need to be handled.
        $entry = $ifd->getEntry($tag);
        if (in_array($tag, self::$timeTags)) {
            if ($entry == null) {
                $entry = new PelEntryTime($tag, $data, PelEntryTime::EXIF_STRING);
                $ifd->addEntry($entry);
            } else {
                $entry->setValue($data, PelEntryTime::EXIF_STRING);
            }
        } else if ($tag == PelTag::COPYRIGHT) {
            if ($entry == null) {
                $entry = new PelEntryCopyright($data[0], $data[1]);
                $ifd->addEntry($entry);
            } else {
                $entry->setValue($data[0], $data[1]);
            }
        } else if ($tag == PelTag::USER_COMMENT) {
            // We need to strip the first 8 characters to comply with UserComments saved before PEL.
            if ($entry == null) {
                $entry = new PelEntryUserComment(substr($data, 8));
                $ifd->addEntry($entry);
            } else {
                $entry->setValue($data);
            }
        } else {
            $entryClass = "\lsolesen\pel\PelEntry" . PelFormat::getName($format);
            if ($entry == null) {
                $entry = new $entryClass($tag, $data);
                $ifd->addEntry($entry);
            } else {
                $entry->setValue($data);
            }
        }
    }
}