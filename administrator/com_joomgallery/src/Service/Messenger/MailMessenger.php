<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\Messenger;
use Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\MailTemplate;
use PHPMailer\PHPMailer\Exception as phpMailerException;

/**
 * Mail Template Messenger Class
 *
 * Provides methods to send template based email messages in the gallery.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MailMessenger extends Messenger implements MessengerInterface
{
  /**
   * Send a template based email.
   *
   * @param   mixed    $recipients    List of users or email addresses receiving the message
   *
   * @return  bool     true on success, false otherwise
   *
   * @since   4.0.0
   * @throws  \PHPMailer\PHPMailer\Exception
   */
  public function send($recipients): bool
  {
    if(empty(MailTemplate::getTemplate($this->template_id, $this->language->getTag())))
    {
      $this->component->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_MAIL_INVALID_TEMPLATE', $this->template_id));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_MAIL_INVALID_TEMPLATE', $this->template_id), 'error', 'jerror');

      return false;
    }

    $mailer = new MailTemplate($this->template_id, $this->language->getTag());
    $mailer->addTemplateData($this->data);
    $this->addRecipients($recipients, $mailer);

    try
    {
      $mailer->send();
    }
    catch(MailDisabledException | phpMailerException $exception)
    {
      try
      {
        $this->component->addLog(Text::_($exception->getMessage()), 'warning', 'jerror');

        $this->component->setError(Text::_('COM_JOOMGALLERY_ERROR_MAIL_FAILED'));

        return false;
      }
      catch(\RuntimeException $exception)
      {
        $this->component->addWarning(Text::_('COM_JOOMGALLERY_ERROR_MAIL_FAILED'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_MAIL_FAILED'), 'error', 'jerror');

        return false;
      }
    }

    $num        = (\is_array($recipients)) ? \count($recipients) : 1;
    $this->sent = $this->sent + $num;

    return true;
  }

  /**
   * Method to add one ore more recipients
   *
   * @param   array   $recipients  An array of email addresses or a single one as a string
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function addRecipients($recipients, $mailer)
  {
    if(\is_array($recipients))
    {
      foreach($recipients as $recipient)
      {
        if(is_numeric($recipient))
        {
          // CMS user id given
          $recipient = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($recipient);
        }

        if(\is_object($recipient) && $recipient instanceof \Joomla\CMS\User\User)
        {
          // CMS user object given
          $mailer->addRecipient($recipient->email, $recipient->name);
        }
        else
        {
          $mailer->addRecipient($recipient);
        }
      }
    }
    else
    {
      $mailer->addRecipient($recipients);
    }
  }
}
