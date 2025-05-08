<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Control;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Helper\ModuleHelper;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * HTML View class for the control panel view
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
  /**
   * HTML view display method
   *
   * @param   string  $tpl  The name of the template file to parse
   * @return  void
   * @since   4.0.0
   */
  public function display($tpl = null)
  {
    ToolBarHelper::title(Text::_('COM_JOOMGALLERY_CONTROL_PANEL') , 'home');

    /** @var ControlModel $model */
    $model = $this->getModel();

    // get module positions in cpanel
    $this->modules = ModuleHelper::getModules('joom_cpanel');

    // get statistic data
    $this->statisticdata = $model->getStatisticData();

    // get gallery info data
    $this->galleryinfodata = $model->getGalleryInfoData();

    // get official extensions data
    $this->galleryofficialextensionsdata = $model->getOfficialExtensionsData();

    // get installed extensions data
    $this->galleryinstalledextensionsdata = $model->getInstalledExtensionsData();

    // get php system info
    $this->php_settings = [
        'memory_limit'        => \ini_get('memory_limit'),
        'upload_max_filesize' => \ini_get('upload_max_filesize'),
        'post_max_size'       => \ini_get('post_max_size'),
        'file_uploads'        => \ini_get('file_uploads') == '1',
        'max_execution_time'  => \ini_get('max_execution_time'),
        'max_input_vars'      => \ini_get('max_input_vars'),
        // 'zlib'                => \extension_loaded('zlib'),
        'zip'                 => \function_exists('zip_open') && \function_exists('zip_read'),
        'gd'                  => \extension_loaded('gd'),
        'exif'                => \extension_loaded('exif'),
        'iconv'               => \function_exists('iconv')
      ];

    $this->addToolbar();

    parent::display($tpl);
  }

  /**
   * Add the page title and toolbar.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function addToolbar()
  {
    /** @var Toolbar $model */
    $toolbar = $this->getDocument()->getToolbar();

    // Images button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=images" class="btn btn-primary"><span class="icon-images" title="'.Text::_('COM_JOOMGALLERY_IMAGES').'"></span> '.Text::_('COM_JOOMGALLERY_IMAGES').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Multiple add button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=image&amp;layout=upload" class="btn btn-primary"><span class="icon-upload" title="'.Text::_('Upload').'"></span> '.Text::_('Upload').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Categories button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=categories" class="button-folder-open btn btn-primary"><span class="icon-folder-open" title="'.Text::_('JCATEGORIES').'"></span> '.Text::_('JCATEGORIES').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Tags button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=tags" class="btn btn-primary"><span class="icon-tags" title="'.Text::_('COM_JOOMGALLERY_TAGS').'"></span> '.Text::_('COM_JOOMGALLERY_TAGS').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Configs button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=configs" class="btn btn-primary"><span class="icon-sliders-h" title="'.Text::_('COM_JOOMGALLERY_CONFIG_SETS').'"></span> '.Text::_('COM_JOOMGALLERY_CONFIG_SETS').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Maintenance button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=faulties" class="btn btn-primary"><span class="icon-wrench" title="'.Text::_('COM_JOOMGALLERY_MAINTENANCE').'"></span> '.Text::_('COM_JOOMGALLERY_MAINTENANCE').'</a>';
    // $toolbar->appendButton('Custom', $html);

    if($this->getAcl()->checkACL('core.admin'))
    {
      ToolBarHelper::preferences('com_joomgallery');
    }

    // Set sidebar action
    Sidebar::setAction('index.php?option=com_joomgallery&view=control');
  }
}
