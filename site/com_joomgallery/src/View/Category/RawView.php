<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\View\Category;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\View\Category\RawView as AdminRawView;
use Joomla\CMS\Language\Text;

/**
 * Raw view class for a single Category-Image.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class RawView extends AdminRawView
{
  /**
   * Display the category image
   *
   * @param   string  $tpl  Template name
   *
   * @return void
   */
  public function display($tpl = null)
  {
    /** @var CategoryModel $model */
    $model = $this->getModel();

    $this->state = $model->getState();

    $loaded = true;
    try
    {
      $this->item = $model->getItem();
    }
    catch (\Exception $e)
    {
      $loaded = false;
    }

    // Check published state
    if($loaded && $this->item->published !== 1)
    {
      echo Text::_('COM_JOOMGALLERY_ERROR_UNAVAILABLE_VIEW');

      return;
    }

    // Check access view level
    if(!\in_array($this->item->access, $this->user->getAuthorisedViewLevels()))
    {
      echo Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW');

      return;
    }

    // Load only if category is currently not protected
    if(!$this->item->pw_protected)
    {
      parent::display($tpl);
    }
    else
    {
      echo Text::_('COM_JOOMGALLERY_CATEGORY_PASSWORD_PROTECTED');
    }
  }
}
