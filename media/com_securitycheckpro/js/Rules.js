 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";
    jQuery(document).ready(function() {    
        
        jQuery( "#filter_acl_search_button" ).click(function() {
            document.getElementById('filter_acl_search').value='';
            jQuery("#adminForm").submit();
        });
        
    });    
