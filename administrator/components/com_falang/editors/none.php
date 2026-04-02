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
                setTranslation("refField_" + fieldname , srcEl.innerHTML);
            }
            if (action == "translate") {
                srcEl = document.getElementById("original_value_" + fieldname);
                translateService("refField_" + fieldname , srcEl.innerHTML);
            }
            if (action == "clear") {
                setTranslation("refField_" + fieldname, "");
            }
        } catch (e) {
            console.log(e.message);
            alert("<?php echo Text::_('CLIPBOARD_NOSUPPORT');?>");
        }
    }

    /*
    * from : 5.11
    * Set the translation in field work with editor and textarea mode
    * @udate 6.5 fix htmlentities use the textarea system no need of extra function or jquery
    *            https://stackoverflow.com/questions/1147359/how-to-decode-html-entities-using-jquery
    *
    * */
    function setTranslation(fieldname, value) {

        //get the value with html entities decoded
        var textArea = document.createElement('textarea');
        textArea.innerHTML = value.trim();
        value = textArea.value;
        textArea.remove();

        srcEl = document.getElementById(fieldname);
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