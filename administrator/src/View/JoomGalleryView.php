<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * Parent HTML View Class for JoomGallery
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryView extends BaseHtmlView
{
  /**
   * JApplication object
   *
   * @access  protected
   * @var     object
   */
  var $app;

  /**
   * JoomConfig object
   *
   * @access  protected
   * @var     object
   */
  var $config;

  /**
   * JoomAmbit object
   *
   * @access  protected
   * @var     object
   */
  var $ambit;

  /**
   * JUser object, holds the current user data
   *
   * @access  protected
   * @var     object
   */
  var $user;

  /**
   * JDocument object
   *
   * @access  protected
   * @var     object
   */
  var $document;

  /**
   * Constructor
   *
   * @access  protected
   * @return  void
   * @since   1.5.5
   */
  function __construct($config = array())
  {
    parent::__construct($config);

    $this->app       = Factory::getApplication('administrator');
    //$this->_ambit    = JoomAmbit::getInstance();
    //$this->ambit     = $this->app->bootComponent('com_joomgallery')->ambit;
    $this->config    = $this->app->bootComponent(_JOOM_OPTION)->config;
    $this->user      = Factory::getUser();
    $this->document  = Factory::getDocument();

    //$this->document->addStyleSheet($this->_ambit->getStyleSheet('admin.joomgallery.css'));

    //JHtmlBehavior::framework();
    //$this->_doc->addScript($this->_ambit->getScript('admin.js'));

    //JoomHelper::addSubmenu();

    //JHTML::addIncludePath(JPATH_COMPONENT.'/helpers/html');
    //--> use services instead (https://blog.astrid-guenther.de/joomla-dependency-injection/)

    // Check for available updates
    // $controller = JRequest::getCmd('controller');
    // if(!$checked = $this->app->getUserState('joom.update.checked'))
    // {
    //   if($this->config->get('jg_checkupdate') && $controller && $controller != 'control')
    //   {
    //     $dated_extensions = JoomExtensions::checkUpdate();
    //     if(count($dated_extensions))
    //     {
    //       $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ADMENU_SYSTEM_NOT_UPTODATE'), 'warning');
    //       $this->app->setUserState('joom.update.checked', -1);
    //     }
    //     else
    //     {
    //       $this->app->setUserState('joom.update.checked', 1);
    //     }
    //   }
    // }
    // else
    // {
    //   if($checked == -1)
    //   {
    //     if($controller && $controller != 'control')
    //     {
    //       $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ADMENU_SYSTEM_NOT_UPTODATE'), 'warning');
    //     }
    //   }
    // }
  }
}
