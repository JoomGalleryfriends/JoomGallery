<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Collection;

\defined('_JEXEC') || die;

use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

/** Creation-only collection form. */
class HtmlView extends JoomGalleryView
{
  protected $form;

  public function display($tpl = null)
  {
    if($this->app->input->getInt('id') || !$this->getAcl()->checkACL('core.create', 'collection'))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $this->setLayout('edit');
    $this->form = $this->getModel()->getCreationForm();

    if(!$this->form)
    {
      throw new \RuntimeException($this->getModel()->getError());
    }

    $this->app->input->set('hidemainmenu', true);
    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_COLLECTION_NEW'), 'folder');
    ToolbarHelper::save('collection.save');
    ToolbarHelper::cancel('collection.cancel');
    parent::display($tpl);
  }
}
