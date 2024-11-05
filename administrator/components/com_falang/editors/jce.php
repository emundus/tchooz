<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<script type="text/javascript">
    function  copyToClipboard(fieldname, action) {
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
            alert("<?php echo preg_replace('#<br\s*/>#', '\n', Text::_('CLIPBOARD_NOSUPPORT', true));?>");
        }
    }

    function getRefField(value) {
        try {
            if (document.getElementById) {
                if (typeof (tinyMCE) == "object") {
                    editor = tinyMCE.editors["refField_" + value];
                    if (editor) {
                        return editor.getContent();
                    }
                    return "";
                } else {
                    return "";
                }
            }
        } catch (e) {
            alert("<?php echo preg_replace('#<br\s*/>#', '\n', Text::_('NO_PREVIEW', true));?>");
            return "";
        }
    }

    /*
    * from : 5.11
    * Set the translation in field work with editor and textarea mode
    * @update 5.12 fix copy / translation test not set correctly
    * */
    function setTranslation(fieldname, value) {
        //check if we want ot add the translation to the editor
        if ( typeof(tinyMCE)=="object") {
            editor = tinyMCE.editors["refField_" + fieldname];
        }
        if (typeof editor !== "undefined") {
            try {
                editor.execCommand("mceSetContent", false, value.trim());
            } catch (e) {
                //nothing to do
            }
        } else {
            //not the editor input // textarea
            srcEl = document.getElementById("refField_" + fieldname);
            srcEl.value = value.trim();
        }
    }
</script>