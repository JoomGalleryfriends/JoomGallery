<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
******************************************************************************************/

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$data = $displayData;

// Receive overridable options
$data['options'] = !empty($data['options']) ? $data['options'] : [];

$noResultsText     = '';
$hideActiveFilters = false;
$showFilterButton  = false;
$showSelector      = false;
$selectorFieldName = $data['options']['selectorFieldName'] ?? 'client_id';

// If a filter form exists.
if(isset($data['view']->filterForm) && !empty($data['view']->filterForm))
{
  // Checks if a selector (e.g. client_id) exists.
  if($selectorField = $data['view']->filterForm->getField($selectorFieldName))
  {
    $showSelector = $selectorField->getAttribute('filtermode', '') === 'selector' ? true : $showSelector;

    // Checks if a selector should be shown in the current layout.
    if(isset($data['view']->layout))
    {
      $showSelector = $selectorField->getAttribute('layout', 'default') != $data['view']->layout ? false : $showSelector;
    }

    // Unset the selector field from active filters group.
    unset($data['view']->activeFilters[$selectorFieldName]);
  }

  // Checks if the filters button should exist.
  $filters = $data['view']->filterForm->getGroup('filter');
  $showFilterButton = isset($filters['filter_search']) && count($filters) === 1 ? false : true;

  // Checks if it should show the be hidden.
  $hideActiveFilters = empty($data['view']->activeFilters);

  // Check if the no results message should appear.
  if(isset($data['view']->total) && (int) $data['view']->total === 0)
  {
    $noResults = $data['view']->filterForm->getFieldAttribute('search', 'noresults', '', 'filter');

    if(!empty($noResults))
    {
      $noResultsText = Text::_($noResults);
    }
  }
}

// Set some basic options.
$customOptions = [
    'filtersHidden'       => isset($data['options']['filtersHidden']) && $data['options']['filtersHidden'] ? $data['options']['filtersHidden'] : $hideActiveFilters,
    'filterButton'        => isset($data['options']['filterButton']) && $data['options']['filterButton'] ? $data['options']['filterButton'] : $showFilterButton,
    'defaultLimit'        => $data['options']['defaultLimit'] ?? Factory::getApplication()->get('list_limit', 20),
    'searchFieldSelector' => '#filter_search',
    'selectorFieldName'   => $selectorFieldName,
    'selectorClass'       => '',
    'showSelector'        => $showSelector,
    'orderFieldSelector'  => '#list_fullordering',
    'showNoResults'       => !empty($noResultsText),
    'noResultsText'       => !empty($noResultsText) ? $noResultsText : '',
    'formSelector'        => !empty($data['options']['formSelector']) ? $data['options']['formSelector'] : '#adminForm',
    'barClass'            => '',
    'showSearch'          => true,
    'showList'            => true
];

// Merge custom options in the options array.
$data['options'] = array_merge($customOptions, $data['options']);

// Add class to hide the active filters if needed.
$filtersActiveClass = $hideActiveFilters ? '' : ' js-stools-container-filters-visible';

// Load search tools
HTMLHelper::_('searchtools.form', $data['options']['formSelector'], $data['options']);
?>
<div class="js-stools" role="search">
  <?php if ($data['options']['showSelector']) : ?>
  <div class="js-stools-container-selector <?php echo $data['options']['selectorClass'];?>">
    <?php echo $this->sublayout('selector', $data); ?>
  </div>
  <?php endif; ?>
  <div class="js-stools-container-bar <?php echo $data['options']['barClass'];?>">
    <div class="btn-toolbar">
      <?php if ($data['options']['showSearch']) : ?>
        <?php echo $this->sublayout('bar', $data); ?>
      <?php endif; ?>
      <?php if ($data['options']['showList']) : ?>
        <?php echo $this->sublayout('list', $data); ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filters div -->
  <?php if ($data['options']['filterButton']) : ?>
    <div class="js-stools-container-filters clearfix<?php echo $filtersActiveClass; ?>">
      <?php echo $this->sublayout('filters', $data); ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($data['options']['showNoResults']) : ?>
  <?php echo $this->sublayout('noitems', $data); ?>
<?php endif; ?>
