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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   object    $provider       The search provider object
 * @var   string    $query          The applied serach query
 * @var   Registry  $params         The view params
 * @var   array     $menuitem       The active menuitem
 * @var   array     $search_url     The endpoint for the search request
 * @var   string    $suggest_url    The endpoint for the auto suggestions to load
 * @var   object    $filterForm     The form object
 * @var   array     $activeFilters  The filter state object
 **/

$app = Factory::getApplication();

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addRegistryFile('media/com_finder/joomla.asset.json');
$wa->useStyle('com_finder.finder');
$wa->useScript('com_finder.finder');
$wa->useScript('bootstrap.collapse');

$search_obj                = new stdClass();
$search_obj->filterForm    = $filterForm;
$search_obj->activeFilters = $activeFilters;

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

<div class="jg-searchform">
  <form id="image-searchform" action="<?php echo Route::_('index.php?option=' . $search_url['option'] . '&view=' . $search_url['view']); ?>" method="get" class="js-finder-searchform">
    <div class="form-inline">
      <div class="input-group">
        <input type="hidden" name="option" value="<?php echo $search_url['option']; ?>">
        <input type="hidden" name="view" value="<?php echo $search_url['view']; ?>">
        <input type="hidden" name="Itemid" value="<?php echo $menuitem['itemid']; ?>">
        <input type="text" name="q" id="q" placeholder="<?php echo Text::_('COM_JOOMGALLERY_SEARCH_TERM');?>" class="js-finder-search-query form-control" value="<?php echo $this->escape($query); ?>">
        <button type="submit" class="btn btn-primary">
          <span class="icon-search icon-white" aria-hidden="true"></span>
          <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>
        </button>
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#toggleFilter" aria-expanded="false" aria-controls="toggleFilter">
          <?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>
        </button>
        <button class="btn btn-outline-primary" onclick="clearFilters(event)">
          <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
        </button>
      </div>
      <div class="filter-input collapse" id="toggleFilter">
        <div class="card card-body">
          <h5>Filter Images</h5>
          <div class="filter-group">
            <?php if(!empty($filterForm))
            {
              echo LayoutHelper::render('joomla.searchtools.default.filters', ['view' => $search_obj]);
            } ?>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  function clearFilters(event) {
    event.preventDefault();

    const form  = document.getElementById("image-searchform");
    form.action = "<?php echo Route::_('index.php?option=' . $menuitem['option'] . '&task=' . $menuitem['model'] . '.clear'); ?>";
    form.method = "post";

    addHiddenInput(form, "redirect", "<?php echo $menuitem['view']; ?>");
    addHiddenInput(form, "<?php echo Session::getFormToken(); ?>", "1");

    form.submit();
  }

  function addHiddenInput(form, name, value)
  {
    let input = form.querySelector('input[name="' + CSS.escape(name) + '"]');

    if(!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      form.appendChild(input);
    }

    input.value = value;
  }
</script>
