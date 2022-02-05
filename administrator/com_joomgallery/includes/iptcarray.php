<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;

$iptc_config_array = array
(
  "IPTC" => array
  (
//            204 => array('Attribute'   => "Object Attribute Reference",
//                         'Name'        => Text::_('COM_JOOMGALLERY_IPTC_INTELLECTUALGENRE'),
//                         'Description' => Text::_('COM_JOOMGALLERY_IPTC_INTELLECTUALGENRE_DEFINITION'),
//                         'Group'       => "Object",
//                         'IMM'         => "2:004",
//                         'Format'      => "Characters",
//                         'Length'      => "256"
//                        ),
        205 => array('Attribute'   => "Object Name",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_TITLE'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_TITLE_DEFINITION'),
                    'Group'       => "Object",
                    'IMM'         => "2:005",
                    'Format'      => "Characters",
                    'Length'      => "64"
                    ),
        225 => array('Attribute'   => "Keywords",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_KEYWORDS'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_KEYWORDS_DEFINITION'),
                    'Group'       => "Keywords",
                    'IMM'         => "2:025",
                    'Format'      => "Characters",
                    'Length'      => "each max. 64"
                    ),
        240 => array('Attribute'   => "Special Instructions",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_INSTRUCTIONS'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_INSTRUCTIONS_DEFINITION'),
                    'Group'       => "Caption",
                    'IMM'         => "2:040",
                    'Format'      => "Characters",
                    'Length'      => "256"
                    ),
        255 => array('Attribute'   => "Date Created",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_DATECREATED'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_DATECREATED_DEFINITION'),
                    'Group'       => "Object",
                    'IMM'         => "2:055",
                    'Format'      => "Numeric",
                    'Length'      => "8"
                    ),
//            260 => array('Attribute'   => "Time Created",
//                         'Name'        => Text::_('COM_JOOMGALLERY_IPTC_TIMECREATED'),
//                         'Description' => Text::_('COM_JOOMGALLERY_IPTC_TIMECREATED_DEFINITION'),
//                         'Group'       => "Object",
//                         'IMM'         => "2:060",
//                         'Format'      => "Characters",
//                         'Length'      => "11"
//                        ),
        280 => array('Attribute'   => "By-line",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_CREATOR'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_CREATOR_DEFINITION'),
                    'Group'       => "Contact",
                    'IMM'         => "2:080",
                    'Format'      => "Characters",
                    'Length'      => "32"
                    ),
        285 => array('Attribute'   => "By-line Title",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_CREATORSJOBTITLE'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_CREATORSJOBTITLE_DEFINITION'),
                    'Group'       => "Contact",
                    'IMM'         => "2:085",
                    'Format'      => "Characters",
                    'Length'      => "32"
                    ),
        290 => array('Attribute'   => "City",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_CITYLEGACY'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_CITYLEGACY_DEFINITION'),
                    'Group'       => "Object",
                    'IMM'         => "2:090",
                    'Format'      => "Characters",
                    'Length'      => "32"
                    ),
        292 => array('Attribute'   => "Sublocation",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_SUBLOCATIONLEGACY'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_SUBLOCATIONLEGACY_DEFINITION'),
                    'Group'       => "Object",
                    'IMM'         => "2:092",
                    'Format'      => "Characters",
                    'Length'      => "32"
                    ),
        295 => array('Attribute'   => "Province/State",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_PROVINCEORSTATELEGACY'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_PROVINCEORSTATELEGACY_DEFINITION'),
                    'Group'       => "Object",
                    'IMM'         => "2:095",
                    'Format'      => "Characters",
                    'Length'      => "32"
                    ),
//           2100 => array('Attribute'   => "Country/Primary Location Code",
//                         'Name'        => Text::_('COM_JOOMGALLERY_IPTC_COUNTRYCODELEGACY'),
//                         'Description' => Text::_('COM_JOOMGALLERY_IPTC_COUNTRYCODELEGACY_DEFINITION'),
//                         'Group'       => "Object",
//                         'IMM'         => "2:100",
//                         'Format'      => "Characters",
//                         'Length'      => "2 or 3"
//                        ),
      2101 => array('Attribute'   => "Country/Primary Location Name",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_COUNTRYLEGACY'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_COUNTRYLEGACY_DEFINITION'),
                    'Group'       => "Object",
                    'IMM'         => "2:101",
                    'Format'      => "Characters",
                    'Length'      => "64"
                    ),
      2105 => array('Attribute'   => "Headline",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_HEADLINE'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_HEADLINE_DEFINITION'),
                    'Group'       => "Caption",
                    'IMM'         => "2:105",
                    'Format'      => "Characters",
                    'Length'      => "256"
                    ),
      2110 => array('Attribute'   => "Credit",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_CREDITLINE'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_CREDITLINE_DEFINITION'),
                    'Group'       => "Credit",
                    'IMM'         => "2:110",
                    'Format'      => "Characters",
                    'Length'      => "32"
                    ),
      2115 => array('Attribute'   => "Source",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_SOURCE'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_SOURCE_DEFINITION'),
                    'Group'       => "Credit",
                    'IMM'         => "2:115",
                    'Format'      => "Characters",
                    'Length'      => "32"
                    ),
      2116 => array('Attribute'   => "Copyright Notice",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_COPYRIGHTNOTICE'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_COPYRIGHTNOTICE_DEFINITION'),
                    'Group'       => "Credit",
                    'IMM'         => "2:116",
                    'Format'      => "Characters",
                    'Length'      => "128"
                    ),
      2118 => array('Attribute'   => "Contact",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_CONTACT'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_CONTACT_DEFINITION'),
                    'Group'       => "Credit",
                    'IMM'         => "2:118",
                    'Format'      => "Characters",
                    'Length'      => "128"
                    ),
      2120 => array('Attribute'   => "Caption/Abstract",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_DESCRIPTION'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_DESCRIPTION_DEFINITION'),
                    'Group'       => "Caption",
                    'IMM'         => "2:120",
                    'Format'      => "Characters",
                    'Length'      => "2000"
                    ),
      2122 => array('Attribute'   => "Writer/Editor",
                    'Name'        => Text::_('COM_JOOMGALLERY_IPTC_DESCRIPTIONWRITER'),
                    'Description' => Text::_('COM_JOOMGALLERY_IPTC_DESCRIPTIONWRITER_DEFINITION'),
                    'Group'       => "Caption",
                    'IMM'         => "2:122",
                    'Format'      => "Characters",
                    'Length'      => "128"
                    ),
  ),
);
