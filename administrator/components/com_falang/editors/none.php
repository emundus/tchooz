<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2024. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<script type="text/javascript">
    function copyToClipboard(fieldname, action) {
        try {
            innerHTML = "";
            if (action == "copy") {
                srcEl = document.getElementById("original_value_" + fieldname);
                setTranslation(fieldname, srcEl.innerHTML);
            }
            if (action == "translate") {
                srcEl = document.getElementById("original_value_" + fieldname);
                translateService(fieldname, srcEl.innerHTML);
            }
            if (action == "clear") {
                setTranslation(fieldname, "");
            }
        } catch (e) {
            console.log(e.message);
            alert("<?php echo Text::_('CLIPBOARD_NOSUPPORT');?>");
        }
    }

    /*
    * from : 5.11
    * Set the translation in field work with editor and textarea mode
    * */
    function setTranslation(fieldname, value) {
        srcEl = document.getElementById("refField_" + fieldname);
        //console.log(srcEl);
        //both need to be done in case we are
        if (srcEl != null) {
            try {
                srcEl.value = value.trim();//set the text area
            } catch (e) {
                //nothing to do
            }
        }
    }
</script>