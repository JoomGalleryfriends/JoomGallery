<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Category controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryController extends JoomFormController
{
    protected $view_list      = 'categories';
    protected $savedVariantId = 0;

    /**
     * Method to save a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   4.0.0
     */
    public function save($key = null, $urlVar = null)
    {
        $task = $this->getTask();

        // The save2copy task needs to be handled slightly differently.
        if($task === 'save2copy')
        {
            $this->input->set('origin_id', $this->input->getInt('id'));
        }

        $originId          = $this->input->getInt('origin_id', 0);
        $forcedLanguage    = $this->input->getString('forcedLanguage', '');
        $isLanguageVariant = $originId > 0 && $forcedLanguage !== '';

        if($isLanguageVariant && $task === 'save')
        {
            $result = parent::save($key, $urlVar);

            if($result)
            {
                $url = 'index.php?option=com_joomgallery&view=category&layout=edit'
                    . '&id=' . $this->savedVariantId
                    . '&origin_id=' . $originId
                    . '&forcedLanguage=' . rawurlencode($forcedLanguage)
                    . '&variant_saved=1&tmpl=component';

                $this->setRedirect(\Joomla\CMS\Router\Route::_($url, false));
            }

            return $result;
        }

        return parent::save($key, $urlVar);
    }

    /**
     * Set the redirect message, treating an associated-category permission denial as information.
     *
     * The base FormController normally marks every save failure as an error. A denied
     * associated-category change is an intentional all-or-nothing cancellation, so
     * convert only this specific model error to Joomla's information message type.
     *
     * @param   string  $text  Message to display on redirect.
     * @param   string  $type  Message type.
     *
     * @return  string  Previous message.
     *
     * @since   4.5.0
     */
    public function setMessage($text, $type = 'message')
    {
        $permissionError = Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED');
        $saveFailedError = Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $permissionError);

        // FormController::save() passes the fully formatted save-failed message here.
        // Match that message directly instead of relying on getModel(), which may
        // resolve a different model instance in this controller context.
        if($type === 'error' && $text === $saveFailedError)
        {
            // Use JoomGallery's warning queue. It renders the same blue
            // Information message as the category-list permission warning.
            $this->component->addWarning($permissionError);

            // Do not queue a Joomla controller message, which would render green/red.
            return null;
        }

        return parent::setMessage($text, $type);
    }

    protected function postSaveHook(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model, $validData = [])
    {
        if($this->input->getInt('origin_id', 0) > 0 && $this->input->getString('forcedLanguage', '') !== '')
        {
            $this->savedVariantId = (int) $model->getState($model->getName() . '.id', 0);
        }

        parent::postSaveHook($model, $validData);
    }

    public function deleteVariant()
    {
        $this->checkToken('post');
        $model          = $this->getModel();
        $variantId      = $this->input->post->getInt('id', 0);
        $originId       = $this->input->post->getInt('origin_id', 0);
        $forcedLanguage = $this->input->post->getString('forcedLanguage', '');

        try
        {
            if($variantId < 1 || $originId < 1 || $variantId === $originId || $forcedLanguage === '')
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            $variantTable = $model->getTable();

            if(!$variantTable->load($variantId) || $variantTable->language !== $forcedLanguage)
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            $originTable = $model->getTable();

            if(!$originTable->load($originId) || $originTable->language === $forcedLanguage)
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            $db      = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $context = 'com_joomgallery.category';
            $query   = $db->getQuery(true)
                ->select($db->quoteName('a1.key'))
                ->from($db->quoteName('#__associations', 'a1'))
                ->join('INNER', $db->quoteName('#__associations', 'a2'), $db->quoteName('a1.key') . ' = ' . $db->quoteName('a2.key'))
                ->where($db->quoteName('a1.context') . ' = :context1')
                ->where($db->quoteName('a2.context') . ' = :context2')
                ->where($db->quoteName('a1.id') . ' = :originId')
                ->where($db->quoteName('a2.id') . ' = :variantId');
            $query->bind(':context1', $context, ParameterType::STRING);
            $query->bind(':context2', $context, ParameterType::STRING);
            $query->bind(':originId', $originId, ParameterType::INTEGER);
            $query->bind(':variantId', $variantId, ParameterType::INTEGER);
            $db->setQuery($query);

            if(!$db->loadResult())
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            $pks = [$variantId];

            if(!$model->delete($pks))
            {
                throw new \RuntimeException($model->getError() ?: Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            echo new JsonResponse(['id' => $variantId], Text::_('COM_JOOMGALLERY_SUCCESS_DELETE_CATEGORY_VARIANT'));
        }
        catch(\Throwable $e)
        {
            echo new JsonResponse(null, $e->getMessage() ?: Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'), true);
        }

        $this->app->close();
    }

    /**
     * Remove the language association without deleting the category.
     *
     * @return  void
     *
     * @since   4.5.0
     */
    public function unlinkVariant()
    {
        $this->checkToken('post');

        $model          = $this->getModel();
        $variantId      = $this->input->post->getInt('id', 0);
        $originId       = $this->input->post->getInt('origin_id', 0);
        $forcedLanguage = $this->input->post->getString('forcedLanguage', '');

        try
        {
            if($variantId < 1 || $originId < 1 || $variantId === $originId || $forcedLanguage === '')
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            $variantTable = $model->getTable();

            if(!$variantTable->load($variantId) || $variantTable->language !== $forcedLanguage)
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            $originTable = $model->getTable();

            if(!$originTable->load($originId) || $originTable->language === $forcedLanguage)
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            // Check editing permissions for both categories.
            if(!$model->getAcl()->checkACL(
                'edit',
                'category',
                $variantId,
                (int) $variantTable->parent_id,
                true
            ) || !$model->getAcl()->checkACL(
                'edit',
                'category',
                $originId,
                (int) $originTable->parent_id,
                true
            ))
            {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
            }

            $db      = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $context = 'com_joomgallery.category';

            $query = $db->getQuery(true)
                ->select($db->quoteName('a1.key'))
                ->from($db->quoteName('#__associations', 'a1'))
                ->join(
                    'INNER',
                    $db->quoteName('#__associations', 'a2'),
                    $db->quoteName('a1.key') . ' = ' . $db->quoteName('a2.key')
                )
                ->where($db->quoteName('a1.context') . ' = :context')
                ->where($db->quoteName('a2.context') . ' = :context')
                ->where($db->quoteName('a1.id') . ' = :originId')
                ->where($db->quoteName('a2.id') . ' = :variantId');

            $query->bind(':context', $context, ParameterType::STRING);
            $query->bind(':originId', $originId, ParameterType::INTEGER);
            $query->bind(':variantId', $variantId, ParameterType::INTEGER);

            $db->setQuery($query);

            if(!$db->loadResult())
            {
                throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));
            }

            if(!$model->removeAssociationMember($variantId))
            {
                throw new \RuntimeException(
                    $model->getError() ?: Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED')
                );
            }

            echo new JsonResponse(
                ['id' => $variantId],
                Text::_('COM_JOOMGALLERY_SUCCESS_DELETE_CATEGORY_VARIANT')
            );
        }
        catch(\Throwable $e)
        {
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $this->app->close();
    }
}
