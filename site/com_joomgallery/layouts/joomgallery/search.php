<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   object    $provider       The search provider object
 * @var   string    $query          The applied serach query
 * @var   Registry  $params         The view params
 * @var   string    $search_url     The endpoint for the search request
 * @var   string    $suggest_url    The endpoint for the auto suggestions to load
 **/

$app = Factory::getApplication();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addRegistryFile('media/com_finder/joomla.asset.json');
$wa->useStyle('com_finder.finder');
$wa->useScript('com_finder.finder');

/*
* This segment of code sets up the autocompleter.
*/
if($provider->getName() == 'finder' & $params->get('jg_gallery_view_autosuggest', 1))
{
  $app->getDocument()->getWebAssetManager()->usePreset('awesomplete');
  $app->getDocument()->addScriptOptions('finder-search', ['url' => $suggest_url]);

  Text::script('COM_FINDER_SEARCH_FORM_LIST_LABEL');
  Text::script('JLIB_JS_AJAX_ERROR_OTHER');
  Text::script('JLIB_JS_AJAX_ERROR_PARSE');
}

?>

<form action="<?php echo $search_url; ?>" method="get" class="js-finder-searchform">
  <div class="form-inline">
    <div class="input-group">
      <div class="">
        <input type="text" name="q" id="q" class="js-finder-search-query form-control" value="<?php echo $this->escape($query); ?>">
        <button type="submit" class="btn btn-primary">
          <span class="icon-search icon-white" aria-hidden="true"></span>
          <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>
        </button>
      </div>

    </div>
  </div>
</form>
