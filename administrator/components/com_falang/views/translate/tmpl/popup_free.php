<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2017. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

?>
<script>
jQuery(function() {
    setHeight();
    setInterval(setHeight, 5000);
});
function setHeight() {
    var iframe = parent.document.getElementById('falang-frame');
    if (iframe) {
        iframe.style.height = (jQuery(document).height()+30) + 'px';
    }
}
</script>

<p style="text-align: center;font-size: 20px;">
   <?php echo JText::_('COM_FALANG_EDIT_ON_PAID_VERSION_ONLY')?>
</p>
