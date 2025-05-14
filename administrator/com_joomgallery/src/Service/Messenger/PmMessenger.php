<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomla\CMS\Mail\MailTemplate;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\Messenger;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerInterface;

/**
 * Mail Messenger Class
 *
 * Provides methods to send email messages in the gallery.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class PmMessenger extends Messenger implements MessengerInterface
{
  /**
   * Send a message to com_messages.
   *
   * @param   mixed    $recipients    List of users receiving the message
   *
   * @return  bool     true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function send($recipients): bool
  {
    $template = MailTemplate::getTemplate($this->template_id, $this->language->getTag());

    if(empty($template))
    {
      $this->component->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_MSG_INVALID_TEMPLATE', $this->template_id));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_MSG_INVALID_TEMPLATE', $this->template_id), 'error', 'jerror');

      return false;
    }

    if(!\is_array($recipients))
    {
      $recipients = array($recipients);
    }

    foreach($recipients as $recipient)
    {
      if(\is_numeric($recipient))
      {
        // CMS user id given
        $recipient = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($recipient);
      }

      if(\is_string($recipient))
      {
        // CMS username given
        $recipient = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($recipient);
      }

      if(!$recipient instanceof \Joomla\CMS\User\User)
      {
        $this->component->setError(Text::_('COM_JOOMGALLERY_ERROR_MSG_USER_NOT_FOUND'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_MSG_USER_NOT_FOUND'), 'error', 'jerror');
        continue;
      }

      if($recipient->authorise('core.manage', 'com_message'))
      {
        // Remove users with locked input box from the list of receivers
        if($this->isMessageBoxLocked($recipient->id))
        {
          continue;
        }

        $subject     = $this->replaceTags(Text::_($template->subject), $this->data);
        $messageText = $this->replaceTags(Text::_($template->body), $this->data);

        $message = [
            'id'         => 0,
            'user_id_to' => $recipient->id,
            'subject'    => $subject,
            'message'    => $messageText,
        ];

        // Get the model for private messages
        $modelMessage = $this->app->bootComponent('com_messages')->getMVCFactory()->createModel('Message', 'Administrator');

        if(!$modelMessage->save($message))
        {
          $this->component->setError(Text::_('COM_JOOMGALLERY_ERROR_MSG_FAILED'));
          $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_MSG_FAILED'), 'error', 'jerror');
          continue;
        }

        $this->sent = $this->sent + 1;
      }
    }

    return true;
  }

  /**
   * Check if the message box is locked
   *
   * @param   int     $userId  The user ID which must be checked
   *
   * @return   bool   Return status of message box is locked
   *
   * @since   4.0.0
   */
  protected function isMessageBoxLocked(int $userId): bool
  {
    if(empty($userId))
    {
      return false;
    }

    // Check for locked inboxes would be better to have _cdf settings in the user_object or a filter in users model
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $query = $db->getQuery(true);

    $query->select($db->quoteName('user_id'))
        ->from($db->quoteName('#__messages_cfg'))
        ->where($db->quoteName('user_id') . ' = ' . $userId)
        ->where($db->quoteName('cfg_name') . ' = ' . $db->quote('locked'))
        ->where($db->quoteName('cfg_value') . ' = 1');

    return (int) $db->setQuery($query)->loadResult() === $userId;
  }
}
