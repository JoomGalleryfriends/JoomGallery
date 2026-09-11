<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryJsonView;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Json view class for an image view of Joomgallery.
 *
 * @package JoomGallery
 * @since   4.4.0
 */
class JsonView extends JoomGalleryJsonView
{
  /**
   * The image object
   *
   * @var  \stdClass
   */
  protected $item;

  /**
   * Display the json view
   *
   * @param   string  $tpl  Template name
   *
   * @return void
   */
  public function display($tpl = null)
  {
    /** @var ImageModel $model */
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
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_UNAVAILABLE_VIEW'), 'error');

      return;
    }

    // Check access view level
    if(!\in_array($this->item->access, $this->user->getAuthorisedViewLevels()))
    {
      $this->output(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'));

      return;
    }

    $this->item->imagetypes = JoomHelper::getRecords('imagetypes');
    $this->item->rating     = JoomHelper::getRating($this->item->id);

    // Transform properties to objects
    $this->item = $this->prepareForJson($this->item);

    // Check for errors.
    if(\count($errors = $model->getErrors()))
    {
      $this->error = true;
      $this->output($errors);

      return;
    }

    $this->output($this->item);
  }
}
