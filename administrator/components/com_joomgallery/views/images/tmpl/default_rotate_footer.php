<?php defined('_JEXEC') or die; ?>
<button type="button" class="btn" onclick="document.getElementById('rotateimagetypes').value=1;jQuery('#rotateimagetypes').trigger('liszt:updated');document.getElementById('rotateimageangle').value=90;jQuery('#rotateimageangle').trigger('liszt:updated');" data-dismiss="modal">
  <?php echo JText::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('rotate');">
  <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
