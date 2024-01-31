 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

    jQuery(document).ready(function() {        
        jQuery( "#read_file_button" ).click(function() {
            Joomla.submitbutton('read_file');
        });
    });  