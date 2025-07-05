<?php

/**
 ******************************************************************************************
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later                          **
 *****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Metadata;

// No direct access
\defined('_JEXEC') or die;

/**
 * Editor class to handle iptc data type editing
 * 
 * @package JoomGallery
 * @since 4.1.0
 */
class IptcDataEditor
{    
    /**
     * @var array
     */
    public $iptcStringArray = array(
        '2#003' => [3, 67],
        '2#005' => [0, 64],
        '2#007' => [0, 64],
        '2#015' => [0, 3],
        '2#020' => [0, 32],
        '2#022' => [0, 32],
        '2#025' => [0, 64],
        '2#040' => [0, 256],
        '2#080' => [0, 32],
        '2#085' => [0, 32],
        '2#090' => [0, 32],
        '2#092' => [0, 32],
        '2#095' => [0, 32],
        '2#100' => [3, 3],
        '2#101' => [0, 64],
        '2#105' => [0, 256],
        '2#110' => [0, 32],
        '2#115' => [0, 32],
        '2#116' => [0, 128],
        '2#118' => [0, 128],
        '2#120' => [0, 2000],
        '2#122' => [0, 32]
    );

    /**
     * @var array
     */
    public $iptcDigitsArray = array(
        '2#008' => 2,
        '2#010' => 1,
        '2#055' => 8
    );

    /**
     * Validates input and creates the octet structure to be saved with iptcembed.
     * 
     * @param   string  $tag  The record & dataset tags in a 0#000 format
     * @param   mixed   $data The data to be stored
     * 
     * @return  mixed         Octet structure that complies to IPTC's specification
     * 
     * @since   4.1.0
     */
    public function createEdit(string $tag, mixed $data): mixed
    {
        if( ( isset($this->iptcStringArray) && $tag != '2#025' &&
              $this->iptcStringArray[$tag][0] <= \strlen($data) && \strlen($data) <= $this->iptcStringArray[$tag][1]
            ) || (isset($this->iptcDigitsArray) && $this->iptcDigitsArray[$tag] >= $data)
          )
        {
            $explode     = \explode('#', $tag);
            $octetStruct = self::makeTag(\intval($explode[0]), intval($explode[1]), $data);

            return $octetStruct;
        }
        elseif(isset($this->iptcStringArray) && $tag == '2#025')
        {
            // Special case for keywords array
            $octetStruct = '';
            foreach($data as $keyword)
            {
                $keyword = \trim($keyword);
                if( \strlen($keyword) > 0 && $this->iptcStringArray[$tag][0] <= \strlen($keyword) &&
                    \strlen($keyword) <= $this->iptcStringArray[$tag][1]
                  )
                {
                    $octetStruct .= self::makeTag(2, 25, $keyword);
                }
            }

            return $octetStruct;
        }

        return false;
    }

    /**
     * Create the necessary octet structure to be saved.
     * Function by Thies C. Arntzen, posted as example under the iptcembed PHP Documentation page.
     * 
     * @param   int   $rec    IPTC Record Number
     * @param   int   $data   IPTC DataSet Number
     * @param   mixed $value  Value to be stored
     * 
     * @return  string        String of chars to embed
     * 
     * @since   4.1.0
     */
    private function makeTag(int $rec, int $data, mixed $value): string
    {
        $length = \strlen($value);

        // First 3 octets (Tag Marker, Record Number, DataSet Number).
        $retval = \chr(0x1C) . \chr($rec) . \chr($data);

        if($length < 0x8000)
        {
            // 4th and 5th octet (total amount of octets that the value contains). Standard DataSet Tag
            // Maximum total of octets is 32767.
            $retval .= chr($length >> 8) .  chr($length & 0xFF);
        }
        else
        {
            // 4th to nth octet. Extended DataSet Tag
            // Most significant bit of octet 4 is always 1 (Flag for Extended format),
            // remaining bits in octet 4 and 5 describe the length of the Data Field
            // (in this instance predefined to 4).
            // 6th to 9th octet describe the total amount of octets that the value contains.
            $retval .= \chr(0x80) .
                       \chr(0x04) .
                       \chr(($length >> 24) & 0xFF) .
                       \chr(($length >> 16) & 0xFF) .
                       \chr(($length >> 8) & 0xFF) .
                       \chr($length & 0xFF);
        }

        return $retval . $value;
    }

    /**
     * Converts the APP13 section of a JPEG image to a string to be embedded.
     * 
     * @param  array $app13  APP13 section from the info return value of getimagesize()
     * 
     * @return string        APP13 as an iptcembed() compatible string
     * 
     * @since 4.1.0
     */
    public function convertIptcToString(array $app13): string
    {
        $retval = '';
        foreach($app13 as $tag => $value)
        {
            $explode = \explode('#', $tag);
            $retval .= self::makeTag(\intval($explode[0]), \intval($explode[1]), $value[0]);
        }

        return $retval;
    }
}
