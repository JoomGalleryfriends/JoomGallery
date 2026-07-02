<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\Registry\Registry;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   object    $provider       The search provider object
 * @var   Registry  $params         The view params
 * @var   Form      $form      Form object of the filters
 * @var   array     $active    The active filters
 **/

// Load the form filters
$filters = $form->getGroup('filter');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
?>

<?php if($filters) : ?>
    <?php foreach($filters as $fieldName => $field) : ?>
        <?php
            $hasTags   = \in_array('tags', $provider->getFilters());
            $hasLogOps = \in_array('and', $provider->getFilters());

            if(str_contains($fieldName, 'and') && $hasTags && !$hasLogOps)
            {
                # The search provider does not support logical operator for tags
                continue;
            }
        ?>
        <?php if($fieldName !== 'filter_search') : ?>
            <?php $dataShowOn = ''; ?>
            <?php if($field->showon) : ?>
                <?php $wa->useScript('showon'); ?>
                <?php $dataShowOn = " data-showon='" . json_encode(FormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group)) . "'"; ?>
            <?php endif; ?>
            <div class="js-stools-field-filter"<?php echo $dataShowOn; ?>>
                <span class="visually-hidden"><?php echo $field->label; ?></span>
                <?php echo $field->input; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
