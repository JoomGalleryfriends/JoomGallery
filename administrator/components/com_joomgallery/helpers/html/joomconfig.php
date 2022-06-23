<?php
/******************************************************************************\
**   JoomGallery 3                                                            **
**   By: JoomGallery::ProjectTeam                                             **
**   Copyright (C) 2008 - 2021  JoomGallery::ProjectTeam                      **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                  **
**   Released under GNU GPL Public License                                    **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look             **
**   at administrator/components/com_joomgallery/LICENSE.TXT                  **
\******************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * Utility class for creating HTML Grids
 *
 * @static
 * @package JoomGallery
 * @since   1.5.5
 */
class JHTMLJoomConfig
{
  /**
   * Displays the start of one table
   *
   * @param $id string the name of the id assigned to the div
   */
  public static function start($id = 'page')
  {
?>
  <div id="<?php echo $id; ?>">
    <table class="adminlist table table-bordered">
<?php
  }

  /**
   * Displays the end of one table
   */
  public static function end()
  {
?>
    </table>
  </div>
<?php
  }

  /**
   * Displays a row (colspan="3") in the config table for additional informations.
   * The text will not be translated, so please use JText::_() afore.
   *
   * @param $text string the text which will be displayed in the row
   */
  public static function intro($text = '&nbsp;')
  {
?>
    <tr>
      <td colspan="3"><div class="alert alert-info"><?php echo $text; ?></div></td>
    </tr>
<?php
  }

  /**
   * Displays a selectable list of predefined configurations
   *
   * @return  string  The HTML output created
   * @since   3.4
   *
   */
  public static function reset()
  {
    $options    = array();
    $options[]  = JHtml::_('select.option', 0, JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_OPTION_INSTALL'));
    $options[]  = JHtml::_('select.option', 1, JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_OPTION_MINI'));
    $options[]  = JHtml::_('select.option', 2, JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_OPTION_MIDDLE'));
    $options[]  = JHtml::_('select.option', 3, JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_OPTION_FULL'));

    $path = JPATH_ADMINISTRATOR . '/components/com_joomgallery/sql/setdefault.user.mysql.utf8.sql';

    if(file_exists($path))
    {
      $options[] = JHtml::_('select.option', 99, JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_OPTION_USERDEFINED'));
    }

    return JHtml::_( 'select.genericlist', $options, 'reset_to');
  }

  /**
   * Displays the title, the current setting and the description of
   * one single option of the configuration manager in a table row
   *
   * @param $key      string      the identifier of the configuration option, e.g. 'jg_pathimages'
   * @param $type     string      'text' => textfield, 'yesno' => yes/no selectbox, 'custom' => custom selectbox or textfield
   * @param $name     string      language constant for the title and the description (+_LONG) of the option, will be translated
   * @param $info     string/int  current setting of the option, if $type = 'custom', we will assume that it holds the complete HTML string
   * @param $display  boolean     if set to false, we won't display this option, defaults to true
   * @param $attribs  string      additional tag attributes (e.g. size = "50"), for now only effective for type 'text'
   * @param $text     string      Alternative description text, if null the default language constant will be used for each option
   */
  public static function row($key, $type, $name, $info, $display = true, $attribs = '', $text = null)
  {
    if(!$display)
    {
      return;
    }
?>
    <tr align="center" valign="middle">
      <td align="left" valign="top"><strong><?php echo JText::_($name); ?></strong></td>
      <td align="left" valign="top"><?php
    switch($type) {
      case 'text':
        ?><input type="text" name="<?php echo $key; ?>" value="<?php echo $info; ?>" <?php echo $attribs; ?>/><?php
        break;
      case 'yesno':
        static $yesno;
        if(!isset($yesno)){
          $yesno = array();
          $yesno[] = JHTML::_('select.option', '0', JText::_('JNO'));
          $yesno[] = JHTML::_('select.option', '1', JText::_('JYES'));
        }
        echo JHTML::_('select.genericlist', $yesno, $key, 'class="inputbox" size="2"', 'value', 'text', $info);
        break;
      case 'custom':
        echo $info;
        break;
      default:
        break;
    } ?></td>
      <td align="left" valign="top"><?php echo $text ? $text : JText::_($name.'_LONG'); ?></td>
    </tr>
<?php
  }

  /**
   * Displays a row (colspan="3") with the ability to test the image manipulation settings.
   *
   * @param   $title_msg     string      title and message of this section
   * @param   $info          object      object with infos about the created test images
   * @return  string  The HTML output created
   * @since   3.4
   */
  public static function configTest($title_msg, $info)
  {
    ?>
    <tr class="jg_configtest">
      <td colspan="3">
        <div class="alert alert-info"><?php echo $title_msg; ?></div>

        <div class="well uploadForm">
          <h4><?php echo JText::_('COM_JOOMGALLERY_IMAGE_UPLOAD'); ?></h4>
          <div class="row-fluid">
            <div class="span8">
              <div class="control-group">
                <label id="imageupload-lbl" class="control-label" for="imageupload"><?php echo JText::_('COM_JOOMGALLERY_COMMON_PLEASE_SELECT_IMAGE'); ?></label>
                <div class="controls">
                  <input id="imageupload" class="inputbox validate-joomfiles" type="file" name="imageupload" value="">
                </div>
              </div>
            </div>
            <div class="span4">
              <div class="controls">
                <button id="button" class="btn btn-primary" onclick="submitUpload(event)"><i class="icon-upload icon-white"></i> <?php echo JText::_('COM_JOOMGALLERY_ACTION_UPLOAD'); ?></button>
              </div>
            </div>
          </div>
        </div>

        <?php
          // check which images are already generated
          $thumb_exist     = false;
          $detail_exist    = false;
          $compare_detail  = false;
          $compare_thumb   = false;
          $compare         = false;
          if(!empty($info->detailL) || !empty($info->detailR))
          {
            $detail_exist = true;
          }
          if(!empty($info->thumbL) || !empty($info->thumbR))
          {
            $thumb_exist = true;
          }
          if($detail_exist && !empty($info->detailL) && !empty($info->detailR) )
          {
            $compare_detail = true;
          }
          if($thumb_exist && !empty($info->thumbL) && !empty($info->thumbR) )
          {
            $compare_thumb = true;
          }
          if($compare_detail || $compare_thumb)
          {
            $compare = true;
          }
        ?>

        <div class="accordion">
          <div class="accordion-group">
            <div class="accordion-heading">
              <p id="origIMG_title" class="accordion-title">
              <?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_IMGINFO'); ?>
              </p>
            </div>
            <div id="origIMG" class="accordion-body">
              <div class="accordion-inner">
                <?php if(!empty($info->orig)): ?>
                  <div class="row-fluid">
                    <div class="span4">
                      <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL');?>:</strong> <?php echo round(($info->orig->filesize) / 1024, 0);?> kB</p>
                      <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL');?>:</strong> <?php echo $info->orig->filetype;?></p>
                      <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL');?>:</strong> <?php echo $info->orig->dimension;?></p>
                      <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL');?>:</strong> <?php echo $info->orig->frames;?></p>
                      <br />
                      <button id="button" class="btn btn-danger" onclick="submitDelete(event)"><?php echo JText::_('COM_JOOMGALLERY_MAIMAN_OPTION_REMOVE_IMAGES'); ?></button>
                    </div>
                    <div class="span8 center">
                      <a href="<?php echo JUri::root().$info->orig->href;?>" target="popup" rel="noopener noreferrer" onclick="window.open('<?php echo JUri::root().$info->orig->href;?>','popup','width=600,height=600'); return false;">
                        <img src="<?php echo JUri::root().$info->orig->href;?>" style="max-width: 200px;">
                      </a>
                    </div>
                  </div>
                <?php else: ?>
                  <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_NOIMG'); ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div id="testsettings" class="accordion">
          <div class="accordion-group">
            <div class="accordion-heading">
              <p id="detailIMG_title" class="accordion-title">
                <?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DETAILINFO'); ?>
              </p>
            </div>
            <div id="detailIMG" class="accordion-body">
              <div class="accordion-inner">
                <table class="adminlist table table-bordered">
                  <tbody>
                    <tr>
                      <td valign="top" align="left">
                        <button id="button" class="btn" type="detail" side="L" onclick="submitGenerate(event)"><?php echo JText::_('COM_JOOMGALLERY_COMMON_TOOLBAR_RECREATE'); ?></button>
                        <br /><br />
                        <?php if(!empty($info->detailL)): ?>
                          <div class="row-fluid">
                            <div class="span4">
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL');?>:</strong> <?php echo round(($info->detailL->filesize) / 1024, 0);?> kB</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL');?>:</strong> <?php echo $info->detailL->filetype;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL');?>:</strong> <?php echo $info->detailL->dimension;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL');?>:</strong> <?php echo $info->detailL->frames;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL');?>:</strong> <?php echo $info->detailL->quality;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL');?>:</strong> <?php echo round($info->detailL->processing_time,3);?> sec</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL');?>:</strong> <?php echo round(($info->detailL->used_memory) / pow(1000,2),2);?> MB</p>
                            </div>
                            <div class="span8 center">
                              <a href="<?php echo JUri::root().$info->detailL->href;?>" target="popup" rel="noopener noreferrer" onclick="window.open('<?php echo JUri::root().$info->detailL->href;?>','popup','width=600,height=600'); return false;">
                                <img src="<?php echo JUri::root().$info->detailL->href;?>" style="max-width: 200px;">
                              </a>
                            </div>
                          </div>
                        <?php else: ?>
                          <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_IMGMISSING'); ?></p>
                        <?php endif; ?>
                      </td>
                      <td valign="top" align="left">
                        <button id="button" class="btn" type="detail" side="R" onclick="submitGenerate(event)"><?php echo JText::_('COM_JOOMGALLERY_COMMON_TOOLBAR_RECREATE'); ?></button>
                        <br /><br />
                        <?php if(!empty($info->detailR)): ?>
                          <div class="row-fluid">
                            <div class="span4">
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL');?>:</strong> <?php echo round(($info->detailR->filesize) / 1024, 0);?> kB</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL');?>:</strong> <?php echo $info->detailR->filetype;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL');?>:</strong> <?php echo $info->detailR->dimension;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL');?>:</strong> <?php echo $info->detailR->frames;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL');?>:</strong> <?php echo $info->detailR->quality;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL');?>:</strong> <?php echo round($info->detailR->processing_time,3);?> sec</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL');?>:</strong> <?php echo round(($info->detailR->used_memory) / pow(1000,2),2);?> MB</p>
                            </div>
                            <div class="span8 center">
                              <a href="<?php echo JUri::root().$info->detailR->href;?>" target="popup" rel="noopener noreferrer" onclick="window.open('<?php echo JUri::root().$info->detailR->href;?>','popup','width=600,height=600'); return false;">
                               <img src="<?php echo JUri::root().$info->detailR->href;?>" style="max-width: 200px;">
                              </a>
                            </div>
                          </div>
                        <?php else: ?>
                          <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_IMGMISSING'); ?></p>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="3" class="center">
                      <button id="btnModal_detail" class="btn btn-primary" onclick="openModal(event)" <?php if($compare_detail) {echo '';} else {echo 'disabled';}?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_COMPAREBOX_BTN'); ?></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="accordion-group">
            <div class="accordion-heading">
              <p id="thumbIMG_title" class="accordion-title">
                <?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_THUMBINFO'); ?>
              </p>
            </div>
            <div id="thumbIMG" class="accordion-body">
              <div class="accordion-inner">
                <table class="adminlist table table-bordered">
                  <tbody>
                    <tr>
                      <td valign="top" align="left">
                        <button id="button" class="btn" type="thumb" side="L" onclick="submitGenerate(event)"><?php echo JText::_('COM_JOOMGALLERY_COMMON_TOOLBAR_RECREATE'); ?></button>
                        <br /><br />
                        <?php if(!empty($info->thumbL)): ?>
                          <div class="row-fluid">
                            <div class="span4">
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL');?>:</strong> <?php echo round(($info->thumbL->filesize) / 1024, 0);?> kB</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL');?>:</strong> <?php echo $info->thumbL->filetype;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL');?>:</strong> <?php echo $info->thumbL->dimension;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL');?>:</strong> <?php echo $info->thumbL->frames;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL');?>:</strong> <?php echo $info->thumbL->quality;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL');?>:</strong> <?php echo round($info->thumbL->processing_time,3);?> sec</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL');?>:</strong> <?php echo round(($info->thumbL->used_memory) / pow(1000,2),2);?> MB</p>
                            </div>
                            <div class="span8 center">
                              <a href="<?php echo JUri::root().$info->thumbL->href;?>" target="popup" rel="noopener noreferrer" onclick="window.open('<?php echo JUri::root().$info->thumbL->href;?>','popup','width=600,height=600'); return false;">
                               <img src="<?php echo JUri::root().$info->thumbL->href;?>" style="max-width: 200px;">
                              </a>
                            </div>
                          </div>
                        <?php else: ?>
                          <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_IMGMISSING'); ?></p>
                        <?php endif; ?>
                      </td>
                      <td valign="top" align="left">
                        <button id="button" class="btn" type="thumb" side="R" onclick="submitGenerate(event)"><?php echo JText::_('COM_JOOMGALLERY_COMMON_TOOLBAR_RECREATE'); ?></button>
                        <br /><br />
                        <?php if(!empty($info->thumbR)): ?>
                          <div class="row-fluid">
                            <div class="span4">
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILESIZE_LBL');?>:</strong> <?php echo round(($info->thumbR->filesize) / 1024, 0);?> kB</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FILETYPE_LBL');?>:</strong> <?php echo $info->thumbR->filetype;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_DIMENSION_LBL');?>:</strong> <?php echo $info->thumbR->dimension;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_FRAMES_LBL');?>:</strong> <?php echo $info->thumbR->frames;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_QUALITY_LBL');?>:</strong> <?php echo $info->thumbR->quality;?></p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENTIME_LBL');?>:</strong> <?php echo round($info->thumbR->processing_time,3);?> sec</p>
                              <p><strong <?php echo JHTML::_('joomgallery.tip', JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_TXT'), JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL'), true); ?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_GENMEMORY_LBL');?>:</strong> <?php echo round(($info->thumbR->used_memory) / pow(1000,2),2);?> MB</p>
                            </div>
                            <div class="span8 center">
                              <a href="<?php echo JUri::root().$info->thumbR->href;?>" target="popup" rel="noopener noreferrer" onclick="window.open('<?php echo JUri::root().$info->thumbR->href;?>','popup','width=600,height=600'); return false;">
                               <img src="<?php echo JUri::root().$info->thumbR->href;?>" style="max-width: 200px;">
                              </a>
                            </div>
                          </div>
                        <?php else: ?>
                          <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_IMGMISSING'); ?></p>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="3" class="center">
                      <button id="btnModal_thumb" class="btn btn-primary" onclick="openModal(event)" <?php if($compare_thumb) {echo '';} else {echo 'disabled';}?>><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_COMPAREBOX_BTN'); ?></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <?php if($detail_exist || $thumb_exist): ?>
          <div class="modal hide fade jviewport-width80 in" id="modalCompare" tabindex="-1" aria-labelledby="modalCompare_title" aria-hidden="true">
            <div class="modal-header">
              <div class="center resize-form">
                <input id="modalImg_width" type="number" min="1" value="400">
                <span> px </span>
                <button id="modalImg_btn" class="btn btn-primary" onclick="setIMGwidth(event);"><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_COMPAREBOX_APPLY'); ?></button>
              </div>
              <button type="button" class="close novalidate" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
              <h3 id="modalCompare_title" thumb_img="<?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_COMPAREBOX_THUMBTITLE'); ?>" detail_img="<?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_COMPAREBOX_DETAILTITLE'); ?>">modal_title</h3>
            </div>
            <div class="modal-body center">
              <?php if($detail_exist): ?>
                <div id="modalImg_detail" class="<?php if($compare) {echo 'image-slider ';}?>hidden">
                  <?php if($compare_detail): ?>
                    <div id="modalImg_detailL">
                      <img class="modalImg" width="400" src="<?php echo JUri::root().$info->detailL->href;?>" />
                    </div>
                  <?php endif; ?>
                    <img id="modalImg_detailR" class="modalImg" width="400" src="<?php if(!$compare_detail && !empty($info->detailL)) {echo JUri::root().$info->detailL->href;} else {echo JUri::root().$info->detailR->href;}?>" />
                </div>
              <?php endif; ?>

              <?php if($thumb_exist): ?>
                <div id="modalImg_thumb" class="<?php if($compare) {echo 'image-slider ';}?>hidden">
                  <?php if($compare_thumb): ?>
                    <div id="modalImg_thumbL">
                      <img class="modalImg" width="400" src="<?php echo JUri::root().$info->thumbL->href;?>" />
                    </div>
                  <?php endif; ?>
                    <img id="modalImg_thumbR" class="modalImg" width="400" src="<?php if(!$compare_thumb && !empty($info->thumbL)) {echo JUri::root().$info->thumbL->href;} else {echo JUri::root().$info->thumbR->href;}?>" />
                </div>
              <?php endif; ?>

              <?php if($compare): ?>
                <p class="img_caption"><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_COMPAREBOX_CAPTION'); ?></p>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <div class="pull-right">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </td>
      <input id="input_gentype" type="hidden" name="gen_type" value="">
      <input id="input_genside" type="hidden" name="gen_side" value="">
      <script>
        var submitUpload = function(e)
        {
          e.preventDefault();
          file = document.getElementById("imageupload").value;
          if(file != '')
          {
            document.querySelector("input[name='task']").value = "upload";
            document.getElementById("adminForm").setAttribute("enctype", "multipart/form-data");
            document.getElementById("adminForm").submit();
          } else
          {
            alert("<?php echo JText::_('COM_JOOMGALLERY_COMMON_ALERT_YOU_MUST_SELECT_ONE_IMAGE'); ?>");
          }
        }
        var submitGenerate = function(e)
        {
          e.preventDefault();
          if(<?php if(!empty($info) && property_exists($info,'orig')) {echo 'true';} else {echo 'false';}?>)
          {
            element = e.target;
            type = element.getAttribute('type');
            side = element.getAttribute('side');

            document.getElementById("input_gentype").value = type;
            document.getElementById("input_genside").value = side;
            document.querySelector("input[name='task']").value = "recreate";
            document.getElementById("adminForm").submit();
          }
          else
          {
            alert("<?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_IP_TEST_NOIMG'); ?>");
          }
        }
        var submitDelete = function(e)
        {
          e.preventDefault();

          document.querySelector("input[name='task']").value = "delete";
          document.getElementById("adminForm").submit();
        }
        var openModal = function(e)
        {
          e.preventDefault();
          element = e.target;
          id = element.id;

          if(element.id == "btnModal_detail")
          {
            modal_detail = document.getElementById("modalImg_detail");
            modal_thumb = document.getElementById("modalImg_thumb");
            if(modal_detail)
            {
              modal_detail.classList.remove("hidden");
            }
            if(modal_thumb)
            {
              modal_thumb.classList.add("hidden");
            }

            title_val = document.getElementById("modalCompare_title").getAttribute("detail_img");
            document.getElementById("modalCompare_title").innerHTML = title_val;
          }

          if(element.id == "btnModal_thumb")
          {
            modal_detail = document.getElementById("modalImg_detail");
            modal_thumb = document.getElementById("modalImg_thumb");
            if(modal_thumb)
            {
              modal_thumb.classList.remove("hidden");
            }
            if(modal_detail)
            {
              modal_detail.classList.add("hidden");
            }

            title_val = document.getElementById("modalCompare_title").getAttribute("thumb_img");
            document.getElementById("modalCompare_title").innerHTML = title_val;
          }

          jQuery('#modalCompare').modal('show');
        }
        var setIMGwidth = function(e)
        {
          e.preventDefault();
          var imgs = document.getElementsByClassName("modalImg");
          var width = document.getElementById("modalImg_width").value;

          for (var i = 0; i < imgs.length; ++i)
          {
            imgs[i].setAttribute("width",width);
          }
        }
      </script>
    </tr>
    <?php
  }
}
