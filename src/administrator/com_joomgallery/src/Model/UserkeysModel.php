<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Registry\Registry;

/**
 * Model for editing the current user's AI credentials.
 *
 * The current identity is deliberately resolved server-side. No user ID from
 * the request is accepted by this model.
 *
 * @since  __DEPLOY_VERSION__
 */
class UserkeysModel extends FormModel
{
  /**
   * Get the credentials form.
   *
   * @param   array  $data      Form data
   * @param   bool   $loadData  Whether stored data should be loaded
   *
   * @return  Form|false
   */
  public function getForm($data = [], $loadData = true)
  {
    $this->assertUserKeyStorageEnabled();

    return $this->loadForm('com_joomgallery.userkeys', 'userkeys', ['control' => 'jform', 'load_data' => $loadData]);
  }

  /**
   * Load the current user's stored credentials.
   *
   * @return  array
   */
  protected function loadFormData()
  {
    $userId = (int) Factory::getApplication()->getIdentity()->id;

    if($userId < 1)
    {
      return [];
    }

    $userModel = Factory::getApplication()->bootComponent('com_joomgallery')
      ->getMVCFactory()->createModel('User', 'Administrator', ['ignore_request' => true]);
    $profile   = $userModel->getItemByCmsUser($userId);
    $params    = new Registry($profile->params ?? '{}');

    return [
      'jg_aiint_key'           => (string) $params->get('jg_aiint_key', ''),
      'jg_aiint_provider_keys' => $params->get('jg_aiint_provider_keys', []),
    ];
  }

  /**
   * Save AI credentials for the current user while preserving other params.
   *
   * @param   array  $data  Validated form data
   *
   * @return  bool
   */
  public function save(array $data): bool
  {
    $this->assertUserKeyStorageEnabled();

    $userId = (int) Factory::getApplication()->getIdentity()->id;

    if($userId < 1)
    {
      throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
    }

    $component = Factory::getApplication()->bootComponent('com_joomgallery');
    $factory   = $component->getMVCFactory();
    $userModel = $factory->createModel('User', 'Administrator', ['ignore_request' => true]);
    $profileId = $userModel->createProfile($userId);
    $table     = $factory->createTable('User', 'Administrator');

    if(!$table->load($profileId))
    {
      throw new \RuntimeException($table->getError() ?: 'Unable to load the JoomGallery user profile.');
    }

    $params = new Registry($table->params ?: '{}');
    $params->set('jg_aiint_key', (string) ($data['jg_aiint_key'] ?? ''));
    $params->set('jg_aiint_provider_keys', (array) ($data['jg_aiint_provider_keys'] ?? []));

    if(!$table->bind(['id' => $profileId, 'cmsuser' => $userId, 'params' => $params->toArray()])
      || !$table->check() || !$table->store())
    {
      throw new \RuntimeException($table->getError() ?: 'Unable to save the AI credentials.');
    }

    return true;
  }

  /**
   * Ensure the global configuration permits user-managed credentials.
   *
   * @return  void
   */
  protected function assertUserKeyStorageEnabled(): void
  {
    $component = Factory::getApplication()->bootComponent('com_joomgallery');
    $component->createConfig();

    if($component->getConfig()->get('jg_aiint_keys_storage', 'configuration') !== 'user')
    {
      throw new \RuntimeException('COM_JOOMGALLERY_USERKEYS_DISABLED', 403);
    }
  }
}
