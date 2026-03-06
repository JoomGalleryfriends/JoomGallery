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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useStyle('com_joomgallery.aiinterface')
   ->useScript('com_joomgallery.aiinterface');

$filter_options = ['formSelector' => '#tagsForm', 'filterButton' => false, 'filtersHidden' => true];
$form_url = 'index.php?option=com_joomgallery&view=tags&layout=aiinterface&tmpl=component';

// Initialize AIinterface
$opts = ['prefix' => 'jgai', 'host' => 'http://localhost/api/v1', 'token' => 'jfhrujr:jurur', 'client_name' => 'JG-General'];
$this->document->addScriptOptions('com_joomgallery.aiinterface', $opts);

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
<div class="jg jg-tags-aiinterface">
  <div class="top-controls">
    <div class="interface-btns">
      <h2 class="mb-4">JoomGallery AI Interface: Keywording</h2>
      <button class="btn btn-outline-primary">My Account</button>
      <button class="btn btn-outline-primary">By new tokens</button>
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
      <div class="dropdown input-group mb-3 jgai-model">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          gemma3:4b (Ollama/Local)
        </button>
        <ul class="dropdown-menu" id="jgai-models-dowpdown">
          <li><a class="dropdown-item" href="#" data-value="gemma3:4b" aria-selected="true">gemma3:4b (Ollama/Local)</a></li>
          <li><a class="dropdown-item" href="#" data-value="gpt-4.1" aria-selected="false">gpt-4.1 (OpenAI)</a></li>
        </ul>
      </div>
      <div class="dropdown input-group mb-3 jgai-mode">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          Performance
        </button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="#" data-value="performance" aria-selected="true">Performance</a></li>
          <li><a class="dropdown-item" href="#" data-value="advanced" aria-selected="false">Advanced</a></li>
        </ul>
      </div>
      <div class="checkbox input-group mb-5 jgai-privacy">
        <input type="checkbox" id="jgai-privacy-box" name="jgai-privacy-box" value="agree">
        <label for="jgai-privacy-box"> I agree with the privacy terms *</label><br>
      </div>
      <div class="privacy jgai-privacy">
        <p>* Depending on the selected KI model, different privacy terms will apply.</p>
        <a href="https://www.google.ch" target="_blank">Click here for more info.</a>
      </div>
    </div>
    <div class="prompt-settings col-8 row">
      <h4 class="title">AI prompt inputs</h4>
      <div class="manual-keywords col-6">
        <div class="input-group mb-3">
          <input type="text" id="jgai-manual-keywords" class="form-control" aria-describedby="jgai-manual-keywords-btn">
          <button class="btn btn-outline-secondary" type="button" id="jgai-manual-keywords-btn">⮠</button>
        </div>
        <div class="grid">
          Here comes a grid with buttons with the entered manual keywords.
        </div>
      </div>
      <div class="prompt-inputs col-6">
        <div class="mb-3">
          <textarea class="form-control" placeholder="Add a description of your images here to help the AI creating better keywords" id="jgai-propmt-description"></textarea>
        </div>

        <div class="mb-3">
          <label for="jgai-nmb-keywords" class="form-label">Generate number of keywords</label>
          <input type="number" class="form-control" id="jgai-nmb-keywords" value="5">
        </div>

        <button class="btn btn-primary" type="button" id="jgai-keywords-generate">Generate Keywords</button>
      </div>
    </div>
  </div>

  <hr>

  <div class="images-panel">
    <?php foreach($this->images as $j => $img) : ?>
      <?php
        $tag_titles = explode(',', $img->tag_titles);
        $tag_ids = explode(',', $img->tag_ids);
      ?>
      <div class="image-panel" <?php if($j>0) { echo 'style="display: none;"'; } ?>>
        <div class="images">
          <img class="image" src="<?php echo JoomHelper::getImg($img->id, 'detail'); ?>" alt="<?php echo $img->title; ?>">
          <div class="navigation-btn">
            <button class="btn btn-outline-primary" id="jgai-prev-image-btn"><span class="icon-arrow-left-4"></span> Previous image</button>
            <button class="btn btn-outline-primary" id="jgai-next-image-btn"><span class="icon-arrow-right-4"></span>Next image</button>
          </div>
        </div>
        <div class="keywords">
          <h4 class="title">Current keywords of this image</h4>
          <div class="grid">
            <?php foreach($tag_ids as $t => $tag_id) : ?>
              <div class="input-group grid-item">
                <input type="text" id="jgai-keyword-<?php echo $img->id; ?>-<?php echo $tag_id; ?>" class="form-control" aria-describedby="jgai-keyword-<?php echo $img->id; ?>-<?php echo $tag_id; ?>-btn" value="<?php echo $tag_titles[$t]; ?>" disabled>
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
                  <button id="jgai-keywords-list-<?php echo $this->escape($item->id); ?>" class="btn btn-outline-secondary grid-item"><?php echo $this->escape($item->title); ?></button>
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
