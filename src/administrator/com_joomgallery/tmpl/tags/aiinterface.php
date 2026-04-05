<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JSHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

// Configuration
$config = JoomHelper::getService('config');

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useStyle('com_joomgallery.aiinterface')
   ->useScript('bootstrap.dropdown')
   ->useScript('bootstrap.modal')
   ->useScript('com_joomgallery.aiinterface');

$filter_options = ['formSelector' => '#tagsForm', 'filterButton' => false, 'filtersHidden' => true];
$form_url = 'index.php?option=com_joomgallery&view=tags&layout=aiinterface&tmpl=component';

$host_url = $config->get('jg_aiint_host', 'http://localhost/api/v1');
$base_url = parse_url($host_url, PHP_URL_SCHEME) . '://' . parse_url($host_url, PHP_URL_HOST);
$lang = str_contains('de',Factory::getLanguage()->getTag()) ? 'de' : 'en';

// Initialize AIinterface
$opts = [ 'prefix' => 'jgai',
          'host' => $host_url,
          'token' => $config->get('jg_aiint_key', ''),
          'client_name' => 'JG-General',
          'autoload' => true,
          'configs' => [
            'forceTrailingSlash' => $config->get('jg_aiint_force_slash', 0),
            'version' => JoomHelper::getComponent()->version,
            'def_lang' => Factory::getLanguage()->getTag(),
            'session' => Session::getFormToken(),
            'base_url' => Uri::base(),
            'imagetype' => $config->get('jg_aiint_tags_imagetype', 'detail'),
            'resize' => $config->get('jg_aiint_tags_maxdim', 500),
            'max_parallel' => $config->get('jg_parallelprocesses', 1),
            'case_sensitivity' => $config->get('jg_aiint_tags_casesens', 1),
            'letter_case' => $config->get('jg_aiint_tags_caseupper', 1),
            'api_keys' => ConfigHelper::getProviderKeys($config),
          ]
        ];
$this->document->addScriptOptions('com_joomgallery.aiinterface', $opts);
JSHelper::registerText('com_joomgallery.aiinterface', 'COM_JOOMGALLERY_JS_AIINT_');

// Images
if(!isset($this->images) || empty($this->images))
{
  $img = (object) ['id' => 0, 'title' => 'No Image', 'alias' => 'no-image', 'tag_ids' => '', 'tag_titles' => ''];
  $this->images = [$img];
}

// Preserve images selection
if(isset($this->input_cid) && !empty($this->input_cid))
{
  $form_url = $form_url . '&cid=' . $this->input_cid;
}
?>

<?php // Tagging UI ?>
<div class="jg jg-tags-aiinterface">
  <div class="top-controls">
    <div class="interface-btns">
      <h2 class="mb-4">JoomGallery AI Interface: Keywording</h2>
      <button id="jgai-show-account-btn" class="btn btn-outline-primary">My Account</button>
      <a class="btn btn-outline-primary" target="_blank" href="<?php echo $base_url . '/' . $lang . '/buy-tokens/'; ?>">By new tokens</a>
    </div>

    <div class="token-balance card">
      <div class="card-body">
        <h4 class="card-title">Balance</h4>
        <p class="card-text"><span class="token-value" id="jgai-balance-value">117'000</span><br><span class="token-text">Tokens</span></p>
      </div>
    </div>
  </div>

  <hr>

  <div class="interface-controls row">
    <div class="model-selection col-4">
      <h4 class="title">Select an AI model</h4>
      <div class="input-group d-flex">
        <div class="dropdown mb-3 jgai-model">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            gemma3:4b (Ollama/Local)
          </button>
          <ul class="dropdown-menu" id="jgai-models-dropdown">
            <li><a class="dropdown-item" href="#" data-value="gemma3:4b" aria-selected="true">gemma3:4b (Ollama/Local)</a></li>
            <li><a class="dropdown-item" href="#" data-value="gpt-4.1" aria-selected="false">gpt-4.1 (OpenAI)</a></li>
          </ul>
        </div>
        <div class="dropdown mb-3 jgai-mode">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Performance
          </button>
          <ul class="dropdown-menu" id="jgai-modes-dropdown">
            <li><a class="dropdown-item" href="#" data-value="performance" aria-selected="true">Performance</a></li>
            <li><a class="dropdown-item" href="#" data-value="advanced" aria-selected="false">Advanced</a></li>
          </ul>
        </div>
        <div class="dropdown mb-3 jgai-language">
          <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Language
          </button>
          <ul class="dropdown-menu" id="jgai-langs-dropdown">
            <li><a class="dropdown-item" href="#" data-value="en" aria-selected="true">English</a></li>
            <li><a class="dropdown-item" href="#" data-value="de" aria-selected="false">Deutsch</a></li>
          </ul>
        </div>
      </div>
      <div class="checkbox input-group mb-5 jgai-privacy">
        <input type="checkbox" id="jgai-privacy-box" name="jgai-privacy-box" value="agree">
        <label for="jgai-privacy-box"> I agree with the privacy terms *</label><br>
      </div>
      <div class="privacy jgai-privacy">
        <p>* Depending on the selected KI model, different privacy terms will apply.</p>
        <a href="<?php echo $base_url . '/' . $lang . '/model-info/'; ?>" target="_blank">Click here for more info.</a>
      </div>
    </div>
    <div class="prompt-settings col-8 row">
      <h4 class="title">AI prompt inputs</h4>
      <div class="manual-keywords col-6">
        <div class="input-group mb-3">
          <input type="text" id="jgai-manual-keywords" class="form-control" aria-describedby="jgai-manual-keywords-btn">
          <button class="btn btn-outline-secondary" type="button" id="jgai-manual-keywords-btn">⮠</button>
        </div>
        <div class="grid"></div>
      </div>
      <div class="prompt-inputs col-6">
        <div class="mb-3">
          <textarea class="form-control" placeholder="Add a description of your images here to help the AI creating better keywords" id="jgai-propmt-description"></textarea>
        </div>

        <div class="mb-3">
          <label for="jgai-nmb-keywords" class="form-label">Generate number of keywords</label>
          <input type="number" class="form-control" id="jgai-nmb-keywords" value="5">
        </div>

        <button class="btn btn-primary" type="button" id="jgai-keywords-generate-btn">Generate Keywords</button>
      </div>
    </div>
  </div>

  <hr>

  <div class="images-panel">
    <div id="image-message-container" aria-live="polite"></div>
    <?php foreach($this->images as $j => $img) : ?>
      <?php
        $tag_titles = [];
        $tag_ids    = [];
        $first_img  = ($j == 0) ? true : false;
        $last_img   = ($j == count($this->images)-1) ? true : false;

        if(!empty($img->tag_ids))
        {
          $tag_titles = array_values(array_filter(array_map('trim', explode(',', $img->tag_titles))));
          $tag_ids    = array_values(array_filter(array_map('trim', explode(',', $img->tag_ids))));
        }

        // Calculate image dimensions
        $strategy = ['strategy' => 'max-dimension', 'value' => 400];
        [$width, $heigth] = JoomHelper::clcImgDimensions($img->id, 'detail', $strategy);
      ?>
      <div id="jgai-image-panel-<?php echo $j; ?>" class="image-panel" <?php if($j>0) { echo 'style="display: none;"'; } ?>>
        <div class="images">
          <img class="image" data-imgid="<?php echo $img->id;?>" src="<?php echo JoomHelper::getImg($img->id, 'detail'); ?>" width="<?php echo $width; ?>" height="<?php echo $heigth; ?>" alt="<?php echo $img->title; ?>">
          <div class="navigation-btn">
            <button class="btn btn-outline-primary" id="jgai-prev-image-btn" <?php if($first_img) { echo 'disabled';}?>><span class="icon-arrow-left-4"></span> Previous image</button>
            <button class="btn btn-outline-primary" id="jgai-next-image-btn" <?php if($last_img) { echo 'disabled';}?>><span class="icon-arrow-right-4"></span>Next image</button>
          </div>
        </div>
        <div class="keywords">
          <h4 class="title">Current keywords of this image</h4>
          <div class="grid">
            <?php foreach($tag_ids as $t => $tag_id) : ?>
              <div class="input-group grid-item">
                <input type="text" id="jgai-keyword-<?php echo $img->id; ?>-<?php echo $tag_id; ?>" class="form-control color-black" aria-describedby="jgai-keyword-<?php echo $img->id; ?>-<?php echo $tag_id; ?>-btn" value="<?php echo $tag_titles[$t]; ?>" disabled>
                <button class="btn btn-outline-secondary" type="button" id="jgai-keyword-<?php echo $img->id; ?>-<?php echo $tag_id; ?>-btn">X</button>
              </div>
            <?php endforeach; ?>
          </div>
          <div>
            <h5 class="title">Meaning of colors</h4>
            <p><span class="color-black">already existing</span>, <span class="color-orange">added manually</span>, <span class="color-red">automatically generated</span> Keywords</p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <hr>

  <div class="keywords-panels">
    <h4 class="title">My most used Keywords</h4>
    <form action="<?php echo Route::_($form_url); ?>" method="post"
      name="tagsForm" id="tagsForm">
      <div class="row">
        <div class="col-md-12">
          <div id="j-main-container" class="j-main-container">
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => $filter_options]); ?>
            <div class="grid">
                <?php foreach($this->items as $i => $item) : ?>
                  <button id="jgai-keywords-list-<?php echo $this->escape($item->id); ?>-btn" class="btn btn-outline-secondary grid-item"><?php echo $this->escape($item->title); ?></button>
                <?php endforeach; ?>
            </div>
            <div colspan="<?php echo isset($this->items[0]) ? \count(get_object_vars($this->items[0])) : 10; ?>">
              <?php echo $this->pagination->getListFooter(); ?>
            </div>
            <input type="hidden" name="task" value=""/>
            <input type="hidden" name="form_submited" value="1"/>
            <?php echo HTMLHelper::_('form.token'); ?>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php // Progress and Summary Modal ?>
<div id ="jgai-modal-generate" class="joomla-modal modal fade" role="dialog" tabindex="-1">
  <div class="modal-dialog level-2 modal-lg jviewport-width60">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="jgai-progress-title" class="modal-title"><?php echo Text::_('COM_JOOMGALLERY_PROGRESS'); ?></h3>
        <button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body jviewport-width60">
        <?php // Progress Bars ?>
        <div id="jgai-progress-section" class="container-fluid d-none">
          <div class="mb-4">
            <label class="form-label fw-bold">Base64 image preparation</label>
            <div class="progress" style="height: 24px;">
              <div id="jgai-progress-fetch-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                   role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                0 / <?php echo count($this->images); ?>
              </div>
            </div>
            <div class="small text-muted mt-1" id="jgai-progress-fetch-text">Pending...</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Keyword generation</label>
            <div class="progress" style="height: 24px;">
              <div id="jgai-progress-generate-bar" class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                  role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                0 / <?php echo count($this->images); ?>
              </div>
            </div>
            <div class="small text-muted mt-1" id="jgai-progress-generate-text">Pending...</div>
          </div>
        </div>
        <?php // Summary ?>
        <div id="jgai-summary-section" class="container-fluid d-none">
          <h3 class="mb-3">Generation: Summary</h3>
          <div class="row align-items-center mb-4">
            <div class="col-auto">
              <div id="jgai-summary-status"
                  class="rounded-circle d-flex align-items-center justify-content-center"
                  style="width:64px;height:64px;">
                <span id="jgai-summary-status-icon" class="text-white fs-3"></span>
              </div>
            </div>
            <div class="col">
              <div class="row mb-2">
                <div class="col-sm-4 fw-bold">Successful images</div>
                <div class="col-sm-8" id="jgai-summary-success"></div>
              </div>
              <div class="row mb-2">
                <div class="col-sm-4 fw-bold">Failed images</div>
                <div class="col-sm-8" id="jgai-summary-failed"></div>
              </div>
              <div class="row mb-2">
                <div class="col-sm-4 fw-bold">AI Model used</div>
                <div class="col-sm-8" id="jgai-summary-model"></div>
              </div>
              <div class="row mb-2">
                <div class="col-sm-4 fw-bold">Created Keywords</div>
                <div class="col-sm-8" id="jgai-summary-keywords"></div>
              </div>
            </div>
          </div>
          <div id="jgai-summary-failed-section" class="mt-3 d-none">
            <h5 class="text-danger">Failed images</h5>
            <ul id="jgai-summary-failed-list" class="list-group list-group-flush small"></ul>
          </div>
          <hr>
          <h3 class="mb-3">AI Interface: Summary</h3>
          <div class="row mb-2">
            <div class="col-sm-6 fw-bold">Tokens used (AI Model)</div>
            <div class="col-sm-6" id="jgai-summary-model-tokens"></div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-6 fw-bold">Tokens used (Interface)</div>
            <div class="col-sm-6" id="jgai-summary-service-tokens"></div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-6 fw-bold">Infractions</div>
            <div class="col-sm-6" id="jgai-summary-infractions"></div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-6 fw-bold">New Balance</div>
            <div class="col-sm-6" id="jgai-summary-balance"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?></button>
      </div>
    </div>
  </div>
</div>

<?php // User Account Modal ?>
<div id ="jgai-modal-account" class="joomla-modal modal fade" role="dialog" tabindex="-1">
  <div class="modal-dialog level-2 modal-lg jviewport-width60">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="jgai-account-title" class="modal-title"><?php echo Text::_('COM_JOOMGALLERY_USER_ACCOUNT'); ?></h3>
        <button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body jviewport-width60">
        <div id="jgai-progress-section" class="container-fluid">
          <h3 class="mb-3">Connected Interface</h3>
          <div class="row mb-2">
            <div class="col-sm-4 fw-bold">Host</div>
            <div class="col-sm-8" id="jgai-modal-account-host"></div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-4 fw-bold">Supported AI Models</div>
            <div class="col-sm-8" id="jgai-modal-account-models"></div>
          </div>
          <hr>
          <h3 class="mb-3">My Account</h3>
          <div class="row mb-2">
            <div class="col-sm-4 fw-bold">Registered E-Mail</div>
            <div class="col-sm-8" id="jgai-modal-account-mail"></div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-4 fw-bold">Token Balance</div>
            <div class="col-sm-8" id="jgai-modal-account-balance"></div>
          </div>
          <div class="row mb-2">
            <div class="col-sm-4 fw-bold">Infractions</div>
            <div class="col-sm-8" id="jgai-modal-account-infractions"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?></button>
      </div>
    </div>
  </div>
</div>
