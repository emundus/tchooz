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
                setTranslation("refField_"+fieldname, srcEl.innerHTML);
            }
            if (action == "translate") {
                srcEl = document.getElementById("original_value_" + fieldname);
                translateService("refField_"+fieldname, srcEl.innerHTML);
            }
            if (action == "clear") {
                setTranslation("refField_"+fieldname, "");
            }
        } catch (e) {
            console.log(e.message);
            alert("<?php echo preg_replace('#<br\s*/>#', '\n', Text::_('CLIPBOARD_NOSUPPORT'));?>");
        }
    }

   /*
   * from : 5.11
   * Set the translation in field work with editor and textarea mode
   * */
   function setTranslation(fieldname,value){
       srcEl = document.getElementById(fieldname);

       //both need to be done in case we are
       if (srcEl != null) {
           //don't work for editor in visual mode but work in text mode and for other field type
           srcEl.value = value.trim();//set the text area
           try {
               //save the content in visual mode
               if (!tinymce.get(fieldname).hidden){
                   //visual mode
                   tinymce.get(fieldname).setContent(value.trim()); //set the editor in visual mode
               }
           } catch(e){
               //nothing to do
           }
       }
   }

   
   function getRefField(value){
      try {
         if (document.getElementById) {
            if ( typeof(tinyMCE)=="object") {
            	editor = tinyMCE.editors["refField_"+value];
            	if (editor){
            		return editor.getContent();
            	}
            	return "";
            }
            else {
                return "";
            }
         }
      }
      catch(e){
         alert("<?php echo preg_replace( '#<br\s*/>#', '\n', Text::_('NO_PREVIEW'));?>");
         return "";
      }
   }

</script>