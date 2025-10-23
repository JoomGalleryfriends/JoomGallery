<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Test;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for the testing view.
 * 
 * @package JoomGallery
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{
  
  /**
   * Display the view
   *
   * @param   string  $tpl  Template name
   *
   * @return void
   *
   * @throws Exception
   */
  public function display($tpl = null)
  {
    $user = Factory::getApplication()->getIdentity();
    if(!$user->authorise('core.admin', 'com_joomgallery'))
    {
      throw new Exception('Access to this view only for super users.', 1);
    }
    
    ToolBarHelper::title('Testing View' , 'wrench');
    
    // Place here yout code to test:
    $listModel = $this->component->getMVCFactory()->createModel('images', 'administrator');
    $listModel->getState();

    // Filter tags
    $tag_ids = [2];
    $listModel->setState('filter.tag', $tag_ids);
    $listModel->setState('filter.and', 1);
    
    // Get images
    $items = $listModel->getItems();
    dump($items);

    parent::display($tpl);
  }
}
