<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin');
?>

<div class="jg jg-migration step2">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step1'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 1); ?></a>
      <a href="#" class="btn btn-outline-primary active" aria-current="page"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 2); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step3'); ?>" disabled class="btn btn-outline-primary disabled"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 3); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step4'); ?>" disabled class="btn btn-outline-primary disabled"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 4); ?></a>
    </div>
  </div>

  <h2><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 2); ?>: <?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP2_TITLE'); ?></h2>
  <br />

  <?php if(!empty($this->error)): ?>
    <div class="alert alert-warning" role="alert">
      <?php foreach($this->error as $error) : ?>      
        <p><?php echo $error; ?></p>
      <?php endforeach; ?>
    </div>
    <?php return; ?>
  <?php endif; ?>

  <div class="alert alert-primary" role="alert">
    <h3><?php echo $this->script->title; ?></h3>
    <?php echo $this->script->description; ?>
  </div>

  <br />

  <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.migrate'); ?>" method="post" enctype="multipart/form-data" 
        name="adminForm" id="migration-form" class="form-validate" aria-label="COM_JOOMGALLERY_MIGRATION_STEP2_TITLE">

      <?php if(empty($this->error)) : ?>
        <?php // Loop through all available check-categories ?>
        <?php foreach ($this->precheck as $cat) : ?>
          <div class="card">
            <div class="card-body"> 
              <?php if($cat->title): ?>
                <div class="card-title">
                  <h3><?php echo Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_CHECK_TITLE') . ': ' . $cat->title; ?></h3>
                  <?php if($cat->desc): ?>
                    <span><?php echo $cat->desc; ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="card-text">
                <table class="table">
                  <caption class="visually-hidden"><?php echo $cat->title; ?></caption>
                  <thead>
                    <tr>
                      <th class="w-70" scope="col"><?php echo $cat->colTitle; ?></th>
                      <th scope="w-20"><?php echo Text::_('JSTATUS'); ?></th>
                      <th class="w-10" scope="col"><?php echo Text::_('JTOOLBAR_HELP'); ?></th>
                    </tr>
                  </thead>
                  <tbody>

                      <?php // Loop through all available check-categories ?>
                      <?php foreach ($cat->checks as $check) : ?>
                        <?php
                          if($check->result)
                          {
                            if($check->warning)
                            {
                              // Check successful, but marked as warning
                              $badgeClass = 'warning';
                              $badgeText  = Text::_('COM_JOOMGALLERY_WARNING');
                            }
                            else
                            {
                              // Check successful
                              $badgeClass = 'success';
                              $badgeText  = Text::_('COM_JOOMGALLERY_SUCCESSFUL');
                            }
                          }
                          else
                          {
                            // Check failed
                            $badgeClass = 'danger';
                            $badgeText  = Text::_('COM_JOOMGALLERY_FAILED');
                          }                          
                        ?>
                        <tr>
                          <td>
                            <strong><?php echo $check->title; ?></strong><br />
                            <small><span class="text-break"><?php echo $check->desc; ?></span></small>
                          </td>
                          <td><span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span></td>
                          <td>
                            <button class="btn btn-outline-secondary<?php if(empty($check->help)) { echo ' disabled';};?>" <?php if(empty($check->help)) { echo 'disabled';};?>
                                    data-title="<?php echo $check->title; ?>" data-text="<?php echo $check->help; ?>" onclick="openModal(event, this)">
                              <span class="icon-question" aria-hidden="true"></span>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <br />
        <?php endforeach; ?>

        <input type="submit" class="btn btn-primary <?php echo $this->success ? '' : 'disabled'; ?>" value="<?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP2_BTN_TXT'); ?>"/>
      <?php endif; ?>

      <input type="hidden" name="task" value="migration.migrate"/>
      <input type="hidden" name="precheck" value="<?php echo $this->success ? '1' : '0'; ?>"/>
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php echo HTMLHelper::_('form.token'); ?>
  </form>

  <?php
  // Help modal box
  $options = array('modal-dialog-scrollable' => true,
                    'title'  => 'Test Title',
                    'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_('JCLOSE').'</button>',
                  );

  echo HTMLHelper::_('bootstrap.renderModal', 'help-modal-box', $options, '<div id="modal-body">Content set by ajax.</div>');
  ?>
</div>

<script>
  /**
   * Checks if a given string is a valid URL.
   *
   * @param   {string}   string -  The string to check.
   * @return  {boolean}            Returns true if the string is a valid URL, false otherwise.
   */
  function isValidURL(string) {
    try {
      new URL(string);
      return true;
    } catch (e) {
      return false;
    }
  }

  /**
   * Adds the query variable 'tmpl=component' to a given URL.
   *
   * @param   {string}  url - The original URL.
   * @return  {string}      - The updated URL with 'tmpl=component' added.
   */
  function addQueryVariable(url) {
    try {
      let urlObject = new URL(url);
      urlObject.searchParams.set('tmpl', 'component');
      return urlObject.toString();
    } catch (error) {
      return url;
    }
  }

  function openModal(event, element)
  {
    event.preventDefault();
    let modal      = document.getElementById('help-modal-box');
    let modalTitle = modal.querySelector('.modal-title');
    let modalBody  = modal.querySelector('.modal-body');

    let helpForum  = '<br/><br/><div class="alert alert-primary" role="alert"><?php echo Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COMMON_CHECK_HELP'); ?></div>';
    let dataText   = element.getAttribute('data-text');

    if(isValidURL(dataText)) {
      dataText = '<iframe src="' + addQueryVariable(dataText) + '" style="height: 50vh;"></iframe>';
    }

    modalTitle.innerHTML = element.getAttribute('data-title');
    modalBody.innerHTML  = dataText+helpForum;

    let bsmodal = new bootstrap.Modal(modal, {keyboard: false});
    bsmodal.show();
  };
</script>
