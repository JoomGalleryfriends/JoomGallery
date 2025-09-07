<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.list')
  ->useStyle('com_joomgallery.site')
  ->useScript('com_joomgallery.list-view')
  ->useScript('multiselect');

//// ToDo: Remove: copy needed items from admin and load only once may be faster
//// Info: debug messages for task must be loaded in controller
//// Load admin language file
//$lang = Factory::getApplication()->getLanguage();
////$lang->load('com_joomgallery', JPATH_SITE);
//$testOk = $lang->load('com_joomgallery', JPATH_ADMINISTRATOR);
////$lang->load('joomla', JPATH_ADMINISTRATOR);

//$isHasAccess = $this->isUserLoggedIn && $this->isUserHasCategory && $this->isUserCoreManager;
$isHasAccess = $this->isUserLoggedIn && $this->isUserCoreManager;

$config      = $this->params['configs'];

// Prevent any display if userspace is not enabled
$isUserSpaceEnabled = $config->get('jg_userspace');
if ( ! $isUserSpaceEnabled) {
  return;
}

$menuParam = $this->params['menu'];

$isShowTitle              = $menuParam->get('showTitle') ?? true;
$isShowLatestCategoryList = $menuParam->get('showLatestCategoryList') ?? true;
$isShowLatestImagesList   = $menuParam->get('showLatestImagesList') ?? false;
$isShowManageableImages   = $menuParam->get('showManageableImages') ?? false;

$isShowUserLimits      = $menuParam->get('showUserLimits') ?? false;
$isShowUserInformation = $menuParam->get('showUserInformation') ?? false;

$panelView = Route::_('index.php?option=com_joomgallery&view=userpanel');
// return to userpanel;
$returnURL = base64_encode('index.php?option=com_joomgallery&view=userpanel');

$userDataComment                    = [];
$userDataComment['userCatCount']    = Text::_('COM_JOOMGALLERY_NOT_REALLY_ENFORCED'); // Text::_(COM_JOOMGALLERY_CONFIG_MAX_USERCATS_LONG);
$userDataComment['userImgCount']    = Text::_('COM_JOOMGALLERY_NOT_REALLY_ENFORCED'); // Text::_(COM_JOOMGALLERY_CONFIG_MAX_USERIMGS_LONG);
$userDataComment['userImgTimeSpan'] = Text::_('COM_JOOMGALLERY_NOT_REALLY_ENFORCED'); // Text::_(COM_JOOMGALLERY_CONFIG_MAX_USERIMGS_TIMESPAN_LONG);

?>

<div class="jg jg-user-panel ">
  <form class="jg-user-panel"
        action="<?php echo $panelView; ?>"
        method="post" name="adminForm" id="adminForm"
        novalidate aria-label="<?php echo Text::_('COM_JOOMGALLERY_USER_PANEL', true); ?>">

    <?php if($isShowTitle): ?>
    <h3><?php echo Text::_('COM_JOOMGALLERY_USER_PANEL'); ?></h3>
    <hr>
    <?php endif; ?>

    <?php if(empty($isHasAccess)): ?>

    <?php // --- no access ----------------------------------------------------- ?>

    <?php displayNoAccess($this); ?>

    <?php else: ?>

    <?php // --- user buttons ----------------------------------------------------- ?>

    <?php displayUserButtons($returnURL); ?>

    <div class="userLimist">

      <?php // --- user limits ----------------------------------------------------- ?>

      <?php if($isShowUserLimits): ?>
        <?php displayUserPanelLimits($this->config, $this->userData, $userDataComment); ?>
      <?php else : ?>

        <?php // --- user information ----------------------------------------------------- ?>

        <?php if($isShowUserInformation): ?>
          <?php displayUserPanelInfo($this->config, $this->userData, $userDataComment); ?>
        <?php endif; ?>

      <?php endif; ?>
    </div">

    <div class="userCategoriesList">

      <?php // --- panel categories list ----------------------------------------------------- ?>

      <?php if($isShowLatestCategoryList): ?>
        <?php displayLatestCategoryList($this); ?>
      <?php endif; ?>
    </div">

    <div class="userImageList">

      <?php // --- panel images list ----------------------------------------------------- ?>

      <?php if($isShowLatestImagesList): ?>
        <?php displayLatestImagesList($this); ?>
      <?php endif; ?>
    </div">

    <div class="userCategoriesList">

      <?php // --- images list to manage ----------------------------------------------------- ?>

      <?php if($isShowManageableImages): ?>
        <?php displayUserManageableImages($this, $returnURL); ?>
      <?php endif; ?>
    </div">

      <?php endif; ?>

    <input type="hidden" name="task" value=""/>
    <!--input type="hidden" name="id" value="0"/-->
    <input type="hidden" name="return" value="<?php echo $returnURL; ?>"/>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="form_submited" value="1"/>
    <input type="hidden" name="filter_order" value=""/>
    <input type="hidden" name="filter_order_Dir" value=""/>

    <?php echo HTMLHelper::_('form.token'); ?>
  </form>

</div>

<?php
function displayUserPanelLimits($config, array $userData, array $userDataComment)
{
  $usrUserCat        = (string) $userData['userCatCount'];
  $cfgMaxUserCat     = (string) ($config->get('jg_maxusercat') ?? '%');
  $usrUserCatComment = (string) $userDataComment['userCatCount'];

  $usrUserImgCount        = (string) $userData['userImgCount'];
  $cfgMaxUserImg          = (string) ($config->get('jg_maxuserimage') ?? '%');
  $usrUserImgCountComment = (string) $userDataComment['userImgCount'];

  $usrUserImgTimespan        = (string) $userData['userImgTimeSpan'];
  $cfgMaxUserImgTimespan     = (string) ($config->get('jg_maxuserimage_timespan') ?? '%');
  $usrUserImgTimespanComment = (string) $userDataComment['userImgTimeSpan'];


  $classDangerValue = 'table-danger';

  $classDangerValueUserCat      = ($usrUserCat > $cfgMaxUserCat) ? ' '.$classDangerValue : '';
  $classDangerValueUserImgCount = ($usrUserImgCount > $cfgMaxUserImg) ? ' '.$classDangerValue : '';
  // $classDangerValueUserUserImgTimespan = ($usrUserImgTimespan > $cfgMaxUserImgTimespan) ? ' ' . $classDangerValue :  '';
  $classDangerValueUserUserImgTimespan = ($usrUserImgTimespan > $cfgMaxUserImg) ? ' '.$classDangerValue : '';


  ?>

  <div class="col-md-6 mb">

    <div class="card">
      <div class="card-header">
        <h5><?php echo Text::_('COM_JOOMGALLERY_LIMITS'); ?></h5>
      </div>
      <div class="card-body">
        <table class="table table-striped table-bordered table-responsive">
          <thead>
          <tr>
            <td class="text-center"></td>
            <td class="text-center"><?php echo Text::_('COM_JOOMGALLERY_ACTUAL_VALUE'); ?></td>
            <td class="text-center"><?php echo Text::_('COM_JOOMGALLERY_MAXIMUM_VALUE'); ?></td>
            <td><?php echo Text::_('COM_JOOMGALLERY_COMMENT'); ?></td>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td>
              <?php echo Text::_('COM_JOOMGALLERY_USER_CATEGORIES'); ?>
            </td>
            <td class="text-center <?php echo $classDangerValueUserCat; ?>">
              <b><?php echo $usrUserCat; ?></b>
            </td>
            <td class="text-center">
              <?php echo $cfgMaxUserCat; ?>
            </td>
            <td>
              <?php echo $usrUserCatComment; ?>
            </td>
          </tr>

          <?php if($cfgMaxUserImgTimespan == '0'): ?>
            <tr>
              <td>
                <?php echo Text::_('COM_JOOMGALLERY_USER_IMAGES'); ?>
              </td>
              <td class="text-center <?php echo $classDangerValueUserImgCount; ?>">
                <b><?php echo $usrUserImgCount; ?></b>
              </td>
              <td class="text-center">
                <?php echo $cfgMaxUserImg; ?>
              </td>
              <td>
                <?php echo $usrUserImgCountComment; ?>
              </td>
            </tr>
          <?php endif; ?>

          <?php if($cfgMaxUserImgTimespan != '0'): ?>
            <tr>
              <td>
                <?php echo Text::sprintf('COM_JOOMGALLERY_USER_IMAGES_IN_N_DAYS', $cfgMaxUserImgTimespan); ?>
              </td>
              <td class="text-center <?php echo $classDangerValueUserUserImgTimespan; ?>">
                <b><?php echo $usrUserImgTimespan; ?></b>
              </td>
              <td class="text-center">
                <!--                  --><?php //echo $cfgMaxUserImgTimespan; ?>
                <?php echo $cfgMaxUserImg; ?>
              </td>
              <td>
                <?php echo $usrUserImgTimespanComment; ?>
              </td>
            </tr>
          <?php endif; ?>

          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php return;
}

function displayUserPanelInfo($config, array $userData, array $userDataComment)
{
  $usrUserCat      = (string) $userData['userCatCount'];
  $usrUserImgCount = (string) $userData['userImgCount'];
  ?>

  <div class="col-md-3 mb">

    <div class="card col-sm">
      <div class="card-header">
        <h5><?php echo Text::_('COM_JOOMGALLERY_INFORMATION'); ?></h5>
      </div>
      <div class="card-body">
        <table class="table table-responsive  w-auto table-sm">
          <tbody>
          <tr>
            <td>
              <?php echo Text::_('COM_JOOMGALLERY_USER_CATEGORIES'); ?>
            </td>
            <td class="text-center">
              <?php echo $usrUserCat; ?>
            </td>
          </tr>

          <tr>
            <td>
              <?php echo Text::_('COM_JOOMGALLERY_USER_IMAGES'); ?>
            </td>
            <td class="text-center ">
              <?php echo $usrUserImgCount; ?>
            </td>
          </tr>

          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php return;
}

function displayNoAccess($data)
{
  ?>
  <div>
    <?php if(!$data->isUserLoggedIn): ?>
      <div class="mb-2">
        <div class="alert alert-warning" role="alert">
          <span class="icon-key"></span>
          <?php echo Text::_('COM_JOOMGALLERY_USER_UPLOAD_PLEASE_LOGIN'); ?>
        </div>
      </div>

    <?php else: ?>

<!--      --><?php //if(!$data->isUserHasCategory): ?>
<!--        <div class="alert alert-warning" role="alert">-->
<!--          <span class="icon-images"></span>-->
<!--          --><?php //echo Text::_('COM_JOOMGALLERY_USER_UPLOAD_MISSING_CATEGORY'); ?>
<!--          <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--><?php //echo Text::_('COM_JOOMGALLERY_USER_UPLOAD_CHECK_W_ADMIN'); ?>
<!--        </div>-->
<!--      --><?php //endif; ?>
      <?php if(!$data->isUserCoreManager): ?>
        <div class="alert alert-warning" role="alert">
          <span class="icon-lamp"></span>
          <?php echo Text::_('COM_JOOMGALLERY_USER_UPLOAD_MISSING_RIGHTS'); ?>
          <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo Text::_('COM_JOOMGALLERY_USER_UPLOAD_CHECK_W_ADMIN'); ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php return;
}

function displayUserButtons ($returnURL)
{

//  $panelView      = Route::_('index.php?option=com_joomgallery&view=userpanel');
$uploadView      = Route::_('index.php?option=com_joomgallery&view=userupload');
$imagesView      = Route::_('index.php?option=com_joomgallery&view=userimages');
$categoriesView  = Route::_('index.php?option=com_joomgallery&view=usercategories');
$newCategoryView = Route::_('index.php?option=com_joomgallery&view=usercategory&layout=editCat&return='.$returnURL);

?>
<div>

  <div class="form-group">

    <div class="mb-4">
      <a class="btn btn-info" href="<?php echo $imagesView; ?>" role="button">
        <span class="icon-images"></span>
        <?php echo Text::_('COM_JOOMGALLERY_USER_IMAGES'); ?>
      </a>

      <a class="btn btn-info" href="<?php echo $categoriesView; ?>" role="button">
        <span class="icon-folder"></span>
        <?php echo Text::_('COM_JOOMGALLERY_USER_CATEGORIES'); ?>
      </a>

      <a class="btn btn-success" href="<?php echo $newCategoryView; ?>" role="button">
        <span class="icon-plus"></span>
        <?php echo Text::_('COM_JOOMGALLERY_USER_NEW_CATEGORY'); ?>
      </a>

      <a class="btn btn-primary" href="<?php echo $uploadView; ?>" role="button">
        <span class="icon-upload"></span>
        <?php echo Text::_('COM_JOOMGALLERY_USER_UPLOAD'); ?>
      </a>
    </div>

  </div>

  <?php return;
  }

  function displayLatestCategoryList($data)
  {
    $categories = $data->latestCategories;

    $panelView = Route::_('index.php?option=com_joomgallery&view=userpanel');

    $tokenLink = '&'.Session::getFormToken().'='. 1;
    // return to userpanel;
    $returnURL = base64_encode('index.php?option=com_joomgallery&view=userpanel');

    // index.php?option=com_modules&task=module.orderPosition&' . $token . '"';

    $baseLink_CategoryEdit      = 'index.php?option=com_joomgallery&task=usercategory.edit&id=';
    $baseLink_CategoryPublish   = 'index.php?option=com_joomgallery&task=usercategory.publish&id=';
    $baseLink_CategoryUnpublish = 'index.php?option=com_joomgallery&task=usercategory.unpublish&id=';
    $baseLink_ImagesFilter      = 'index.php?option=com_joomgallery&view=userimages&filter_category=';

    // return to userpanel;
    $returnURL = base64_encode('index.php?option=com_joomgallery&view=userpanel');

    ?>
    <div class="mb">

      <div class="card">
        <div class="card-header">
          <h4>
            <?php echo Text::_('COM_JOOMGALLERY_LATEST_CATEGORIES').' ('.count($categories).')'; ?>
          </h4>
        </div>

        <div class="card-body">

          <form class="jg-categories"
                action="<?php echo $panelView; ?>"
                method="post" name="adminForm" id="adminForm"
                novalidate aria-label="<?php echo Text::_('COM_JOOMGALLERY_USER_CATEGORIES', true); ?>">

            <?php if(empty($categories)) : ?>

              <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span
                  class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_JOOMGALLERY_USER_NO_CATEGORIES_ASSIGNED'); ?>
              </div>

            <?php else : ?>

              <div class="clearfix"></div>

              <div class="table-responsive">
                <table class="table table-striped itemList" id="categoryList">
                  <thead>
                  <tr>
                    <th scope="col" style="min-width:180px">
                      <?php echo Text::_('JGLOBAL_TITLE'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('COM_JOOMGALLERY_IMAGES'); ?>
                    </th>

                    <th scope="col" style="min-width:180px" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('COM_JOOMGALLERY_PARENT_CATEGORY'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('COM_JOOMGALLERY_ACTIONS'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('JPUBLISHED'); ?>
                    </th>
                  </tr>
                  </thead>
                  <tbody>

                  <?php foreach($categories as $i => $item) :
                    // Access check
                    $canEdit = $data->getAcl()->checkACL('edit', 'com_joomgallery.category', $item->id);
                    $canDelete = $data->getAcl()->checkACL('delete', 'com_joomgallery.category', $item->id);
                    $canChange = $data->getAcl()->checkACL('editstate', 'com_joomgallery.category', $item->id);
                    $canCheckin = $canChange || $item->checked_out == $data->getCurrentUser->id;
                    $disabled = ($item->checked_out > 0) ? 'disabled' : '';
                    $statePublished = ((int) $item->published) ? 'unpublish' : 'publish';

                    // $catRoute = Route::_($baseLink_CategoryEdit.(int) $item->id);

                    $editCategoryLink      = Route::_($baseLink_CategoryEdit.(int) $item->id.$tokenLink.'&return='.$returnURL);
                    $publishCategoryLink   = Route::_($baseLink_CategoryPublish.(int) $item->id.$tokenLink.'&return='.$returnURL);
                    $unpublishCategoryLink = Route::_($baseLink_CategoryUnpublish.(int) $item->id.$tokenLink.'&return='.$returnURL);
//                      $publishCategoryLink = Route::_($baseLink_CategoryPublish . (int) $item->id . $tokenLink);
//                      $unpublishCategoryLink = Route::_($baseLink_CategoryUnpublish . (int) $item->id . $tokenLink);

                    // user may not delete his root gallery
                    if((!empty($item->id)) && $item->parent_id == 1)
                    {
                      $canDelete = false;
                    }
                    ?>

                    <tr class="row<?php echo $i % 2; ?>">

                      <th scope="row" class="has-context title-cell">
                        <div id="divCheckbox" style="display: none;">
                          <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                        </div>

                        <?php if($canCheckin && $item->checked_out > 0) : ?>
                          <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>"
                                  data-item-id="cb<?php echo $i; ?>"
                                  data-item-task="usercategory.checkin" <?php echo $disabled; ?>>
                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'category.', false); ?>
                          </button>
                        <?php endif; ?>
                        <a href="<?php echo $editCategoryLink; ?>">
                          <?php echo $data->escape($item->title); ?>
                          <?php
                          if($data->isDebugSite)
                          {
                            echo '&nbsp;('.$data->escape($item->id).')';
                          }
                          ?>
                        </a>
                      </th>

                      <td class="d-none d-lg-table-cell text-center">
                        <a class="badge bg-info"
                           title="ToDo: Text: Click to view images list of category"
                           href="<?php echo $baseLink_ImagesFilter.(int) $item->id; ?>">
                          <?php echo (int) $item->img_count; ?>
                        </a>
                      </td>

                      <td class="d-none d-lg-table-cell text-center">
                        <?php echo ($item->parent_title == 'Root') ? '--' : $data->escape($item->parent_title); ?>
                        <?php
                        if($data->isDebugSite)
                        {
                          echo '&nbsp;('.$data->escape($item->parent_id).')';
                        }
                        ?>

                      </td>

                      <td class="d-none d-lg-table-cell text-center">
                        <?php if($canEdit || $canDelete): ?>
                          <?php if($canEdit): ?>
                            <?php
                            $route = Route::_($baseLink_CategoryEdit.(int) $item->id);
                            ?>
                            <a href="<?php echo $editCategoryLink; ?>">
                              <span class="icon-edit" aria-hidden="true"></span>
                            </a>
                          <?php endif; ?>

                          <?php if($canDelete): ?>
                            <button class="js-grid-item-delete tbody-icon <?php echo $disabled; ?>"
                                    data-item-confirm="<?php echo Text::_('JGLOBAL_CONFIRM_DELETE'); ?>"
                                    data-item-id="cb<?php echo $i; ?>"
                                    data-item-task="usercategory.remove" <?php echo $disabled; ?>>
                              <span class="icon-trash" aria-hidden="true"></span>
                            </button>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>

                      <td class="d-none d-lg-table-cell text-center">
                        <?php if($canChange): ?>
                          <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>"
                                  data-item-id="cb<?php echo $i; ?>"
                                  data-item-task="usercategory.<?php echo $statePublished; ?>"
                            <?php echo $disabled; ?>
                          >
                              <span class="icon-<?php echo (int) $item->published ? 'check' : 'cancel'; ?>"
                                    aria-hidden="true"></span>
                          </button>
                          <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>"
                                  data-url="<?php echo ((int) $item->published) ? $publishCategoryLink : $unpublishCategoryLink; ?>"
                          >
                              <span class="icon-<?php echo (int) $item->published ? 'check' : 'cancel'; ?>"
                                    aria-hidden="true"></span>
                          </button>

                          <!--                            <a class="btn js-grid-item-action tbody-icon --><?php //echo $disabled; ?><!--"-->
                          <!--                              href="--><?php //echo ((int) $item->published) ? $unpublishCategoryLink : $publishCategoryLink; ?><!--"-->
                          <!--                            >-->
                          <!--                              <span class="icon---><?php //echo (int) $item->published ? 'check' : 'cancel'; ?><!--"-->
                          <!--                                    aria-hidden="true"></span>-->
                          <!--                            </a>-->

                          <a href="<?php echo $publishCategoryLink; ?>">
                            <span class="icon-check" aria-hidden="true"></span>
                          </a>

                          <a href="<?php echo $unpublishCategoryLink; ?>">
                            <span class="icon-cancel" aria-hidden="true"></span>
                          </a>

                        <?php else : ?>
                          <i class="icon-<?php echo (int) $item->published ? 'check' : 'cancel'; ?>"></i>
                        <?php endif; ?>
                      </td>

                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <?php echo HTMLHelper::_('form.token'); ?>
          </form>
        </div>
      </div>
    </div>

    <?php return;
  }

  function displayLatestImagesList($data)
  {
    $images = $data->latestImages;

    $panelView = Route::_('index.php?option=com_joomgallery&view=userpanel');

    // index.php?option=com_modules&task=module.orderPosition&' . $token . '"';
    $token = Session::getFormToken().'='. 1;
    // return to userpanel;
    $returnURL = base64_encode('index.php?option=com_joomgallery&view=userpanel');

    $baseLink_ImageEdit    = 'index.php?option=com_joomgallery&view=userimage&layout=editImg&id=';
    $baseLink_ImagesFilter = 'index.php?option=com_joomgallery&view=userimages&filter_category=';

    $panelView = Route::_('index.php?option=com_joomgallery&view=userpanel');


    ?>
    <div class="mb">

      <div class="card">
        <div class="card-header">
          <h4>
            <?php echo Text::_('COM_JOOMGALLERY_LATEST_IMAGES').' ('.count($images).')'; ?>
          </h4>
        </div>
        <div class="card-body">

          <form class="jg-images"
                action="<?php echo $panelView; ?>"
                method="post" name="adminForm" id="adminForm"
                novalidate aria-label="<?php echo Text::_('COM_JOOMGALLERY_USER_PANEL', true); ?>">

            <?php if(empty($images)) : ?>

              <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span
                  class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_JOOMGALLERY_USER_NO_IMAGES_ASSIGNED'); ?>
              </div>

            <?php else : ?>

              <div class="clearfix"></div>

              <div class="table-responsive">
                <table class="table table-striped itemList" id="imageList">
                  <thead>
                  <tr>
                    <th></th>

                    <th scope="col" style="min-width:180px">
                      <?php echo Text::_('JGLOBAL_TITLE'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('JGLOBAL_HITS'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('COM_JOOMGALLERY_DOWNLOADS'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('JCATEGORY'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('COM_JOOMGALLERY_ACTIONS'); ?>
                    </th>

                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('JPUBLISHED'); ?>
                    </th>
                  </tr>
                  </thead>

                  <tbody>
                  <?php foreach($images as $i => $item) :
                    // Access check
                    $canEdit = $data->getAcl()->checkACL('edit', 'com_joomgallery.image', $item->id, $item->catid, true);
                    $canDelete = $data->getAcl()->checkACL('delete', 'com_joomgallery.image', $item->id, $item->catid, true);
                    $canChange = $data->getAcl()->checkACL('editstate', 'com_joomgallery.image', $item->id, $item->catid, true);
                    $canCheckin = $canChange || $item->checked_out == $data->getCurrentUser->id;
                    $disabled = ($item->checked_out > 0) ? 'disabled' : '';
                    $statePublished = ((int) $item->published) ? 'unpublish' : 'publish';

                    $editImageLink = Route::_($baseLink_ImageEdit.(int) $item->id.'&return='.$returnURL);
                    ?>

                    <tr class="row<?php echo $i % 2; ?>">

                      <td class="small d-none d-md-table-cell">
                        <div id="divCheckbox" style="display: none;">
                          <?php
                          $test = HTMLHelper::_('grid.id', $i, $item->id, false, 'iid', 'cb', $item->title);
                          echo HTMLHelper::_('grid.id', $i, $item->id, false, 'iid', 'cb', $item->title); ?>
                        </div>

                        <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($item, 'thumbnail'); ?>"
                             alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                      </td>

                      <th scope="row" class="has-context title-cell">
                        <?php if($canCheckin && $item->checked_out > 0) : ?>
                          <button class="js-grid-item-action tbody-icon" data-item-id="cb<?php echo $i; ?>"
                                  data-item-task="userimage.checkin">
                            <span class="icon-checkedout" aria-hidden="true"></span>
                          </button>
                        <?php endif; ?>
                        <?php
                        $route = Route::_($baseLink_ImageEdit.(int) $item->id);
                        ?>
                        <a href="<?php echo $route; ?>">
                          <?php echo $data->escape($item->title); ?>
                          <?php
                          if($data->isDebugSite)
                          {
                            echo '&nbsp;('.$data->escape($item->id).')';
                          }
                          ?>
                        </a>
                      </th>

                      <td class="d-none d-lg-table-cell text-center">
                          <span class="badge bg-info">
                            <?php echo (int) $item->hits; ?>
                          </span>
                      </td>
                      <td class="d-none d-lg-table-cell text-center">
                          <span class="badge bg-info">
                            <?php echo (int) $item->downloads; ?>
                          </span>
                      </td>

                      <td class="d-none d-lg-table-cell text-center">
                        <a title="ToDo: Text: Click to view images list of category"
                           href="<?php echo $baseLink_ImagesFilter.(int) $item->catid; ?>">
                          <?php echo $data->escape($item->cattitle); ?>
                        </a>
                      </td>

                      <td class="d-none d-lg-table-cell text-center">

                        <?php if($canEdit || $canDelete): ?>
                          <?php if($canEdit): ?>
                            <a href="<?php echo $editImageLink; ?>">
                              <span class="icon-edit" aria-hidden="true"></span>
                            </a>
                          <?php endif; ?>

                          <?php if($canDelete): ?>
                            <button class="js-grid-item-delete tbody-icon <?php echo $disabled; ?>"
                                    data-item-confirm="<?php echo Text::_('JGLOBAL_CONFIRM_DELETE'); ?>"
                                    data-item-id="cb<?php echo $i; ?>"
                                    data-item-task="Userimage.remove" <?php echo $disabled; ?>>
                              <span class="icon-trash" aria-hidden="true"></span>
                            </button>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>

                      <td class="d-none d-lg-table-cell text-center">
                        <?php if($canChange): ?>
                          <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>"
                                  data-item-id="cb<?php echo $i; ?>"
                                  data-item-task="Userimage.<?php echo $statePublished; ?>" <?php echo $disabled; ?>>
                              <span class="icon-<?php echo (int) $item->published ? 'check' : 'cancel'; ?>"
                                    aria-hidden="true"></span>
                          </button>
                        <?php else : ?>
                          <i class="icon-<?php echo (int) $item->published ? 'check' : 'cancel'; ?>"></i>
                        <?php endif; ?>
                      </td>

                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

            <?php endif; ?>

            <?php echo HTMLHelper::_('form.token'); ?>
          </form>

        </div>
      </div>
    </div>

    <?php return;
  }

  function displayUserManageableImages($data, $returnURL)
  {
    // Access check
    $listOrder = $data->state->get('list.ordering');
    $listDirn  = $data->state->get('list.direction');
    $canOrder  = $data->getAcl()->checkACL('editstate', 'com_joomgallery.image', 0, 1, true);
    $saveOrder = ($listOrder == 'a.ordering' && strtolower($listDirn) == 'asc');

    $saveOrderingUrl = '';
    if($saveOrder && !empty($data->items))
    {
      $saveOrderingUrl = Route::_('index.php?option=com_joomgallery&task=userpanel.saveOrderAjax&tmpl=component&'.Session::getFormToken().'=1');
      HTMLHelper::_('draggablelist.draggable');
    }

    $baseLink_ImageEdit    = 'index.php?option=com_joomgallery&view=userimage&layout=editImg&id=';
    $baseLink_ImagesFilter = 'index.php?option=com_joomgallery&view=userimages&filter_category=';

    ?>
    <div class="form-group">

      <div class="card ">
        <div class="card-body">
          <h5 class="card-title"><?php echo Text::_('COM_JOOMGALLERY_USER_PANEL_USER_IMAGES'); ?></h5>

          <?php if(!empty($data->filterForm)) : ?>
            <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $data)); ?>
          <?php endif; ?>

          <?php if(empty($data->items)) : ?>
            <div class="alert alert-info">
              <span class="icon-info-circle" aria-hidden="true"></span><span
                class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
              <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>

          <?php else : ?>

            <div class="clearfix"></div>

            <div class="table-responsive">
              <table class="table table-striped itemList" id="imageList">
                <caption class="visually-hidden">
                  <?php echo Text::_('COM_JOOMGALLERY_IMAGES_TABLE_CAPTION'); ?>,
                  <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                  <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                </caption>
                <thead>
                <tr>
                  <?php if($canOrder && $saveOrder && isset($data->items[0]->ordering)): ?>
                    <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                      <?php echo HTMLHelper::_('grid.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                    </th>
                  <?php else : ?>
                    <th scope="col" class="w-1 d-md-table-cell"></th>
                  <?php endif; ?>

                  <th></th>

                  <th scope="col" style="min-width:180px">
                    <?php echo HTMLHelper::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort', 'COM_JOOMGALLERY_DOWNLOADS', 'a.downloads', $listDirn, $listOrder); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort', 'JCATEGORY', 'a.catid', $listDirn, $listOrder); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo Text::_('COM_JOOMGALLERY_ACTIONS'); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort', 'JPUBLISHED', 'a.published', $listDirn, $listOrder); ?>
                  </th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                  <td colspan="<?php echo isset($data->items[0]) ? count(get_object_vars($data->items[0])) : 10; ?>">
                    <?php echo $data->pagination->getListFooter(); ?>
                  </td>
                </tr>
                </tfoot>
                <tbody <?php if($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" <?php endif; ?>>
                <?php foreach($data->items as $i => $item) :
                  // Access check
                  $ordering = ($listOrder == 'a.ordering');
                  $canEdit = $data->getAcl()->checkACL('edit', 'com_joomgallery.image', $item->id, $item->catid, true);
                  $canDelete = $data->getAcl()->checkACL('delete', 'com_joomgallery.image', $item->id, $item->catid, true);
                  $canChange = $data->getAcl()->checkACL('editstate', 'com_joomgallery.image', $item->id, $item->catid, true);
                  $canCheckin = $canChange || $item->checked_out == $data->getCurrentUser->id;
                  $disabled = ($item->checked_out > 0) ? 'disabled' : '';
                  $statePublished = ((int) $item->published) ? 'unpublish' : 'publish';

                  ?>

                  <tr class="row<?php echo $i % 2; ?>">

                    <?php if(isset($data->items[0]->ordering)) : ?>
                      <td class="text-center d-none d-md-table-cell sort-cell">
                        <?php
                        $iconClass = '';
                        if(!$canChange)
                        {
                          $iconClass = ' inactive';
                        }
                        elseif(!$saveOrder)
                        {
                          $iconClass = ' inactive" title="'.Text::_('JORDERINGDISABLED');
                        }
                        ?>
                        <?php if($canChange && $saveOrder) : ?>
                          <span class="sortable-handler<?php echo $iconClass ?>">
                          <span class="icon-ellipsis-v" aria-hidden="true"></span>
                        </span>
                          <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>"
                                 class="width-20 text-area-order hidden">
                        <?php endif; ?>

                        <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                      </td>
                    <?php endif; ?>

                    <td class="small d-none d-md-table-cell">
                      <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($item, 'thumbnail'); ?>"
                           alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                    </td>

                    <th scope="row" class="has-context title-cell">
                      <?php if($canCheckin && $item->checked_out > 0) : ?>
                        <button class="js-grid-item-action tbody-icon" data-item-id="cb<?php echo $i; ?>"
                                data-item-task="userimage.checkin">
                          <span class="icon-checkedout" aria-hidden="true"></span>
                        </button>
                      <?php endif; ?>
                      <?php
                      $route = Route::_($baseLink_ImageEdit.(int) $item->id);
                      ?>
                      <a href="<?php echo $route; ?>">
                        <?php echo $data->escape($item->title); ?>
                        <?php
                        if($data->isDebugSite)
                        {
                          echo '&nbsp;('.$data->escape($item->id).')';
                        }
                        ?>
                      </a>
                    </th>

                    <td class="d-none d-lg-table-cell text-center">
                        <span class="badge bg-info">
                          <?php echo (int) $item->hits; ?>
                        </span>
                    </td>
                    <td class="d-none d-lg-table-cell text-center">
                        <span class="badge bg-info">
                          <?php echo (int) $item->downloads; ?>
                        </span>
                    </td>

                    <td class="d-none d-lg-table-cell text-center">
                      <a title="ToDo: Text: Click to view images list of category"
                         href="<?php echo $baseLink_ImagesFilter.(int) $item->catid; ?>">
                        <?php echo $data->escape($item->cattitle); ?>
                      </a>
                    </td>

                    <td class="d-none d-lg-table-cell text-center">

                      <?php if($canEdit || $canDelete): ?>
                        <?php if($canEdit): ?>
                          <?php
                          $linkWithReturn = $baseLink_ImageEdit.(int) $item->id.'&return='.$returnURL;
                          $route          = Route::_($linkWithReturn);
                          ?>
                          <a href="<?php echo $route; ?>">
                            <span class="icon-edit" aria-hidden="true"></span>
                          </a>
                        <?php endif; ?>

                        <?php if($canDelete): ?>
                          <button class="js-grid-item-delete tbody-icon <?php echo $disabled; ?>"
                                  data-item-confirm="<?php echo Text::_('JGLOBAL_CONFIRM_DELETE'); ?>"
                                  data-item-id="cb<?php echo $i; ?>"
                                  data-item-task="Userimage.remove" <?php echo $disabled; ?>>
                            <span class="icon-trash" aria-hidden="true"></span>
                          </button>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>

                    <td class="d-none d-lg-table-cell text-center">
                      <?php if($canChange): ?>
                        <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>"
                                data-item-id="cb<?php echo $i; ?>"
                                data-item-task="Userimage.<?php echo $statePublished; ?>" <?php echo $disabled; ?>>
                            <span class="icon-<?php echo (int) $item->published ? 'check' : 'cancel'; ?>"
                                  aria-hidden="true"></span>
                        </button>
                      <?php else : ?>
                        <i class="icon-<?php echo (int) $item->published ? 'check' : 'cancel'; ?>"></i>
                      <?php endif; ?>
                    </td>

                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          <?php endif; ?>

        </div>
      </div>

    </div>
    <?php return;
  }


  ?>
