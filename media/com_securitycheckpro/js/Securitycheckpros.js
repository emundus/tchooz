 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

    function filter_vulnerable_extension(product) {
        var url = 'index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&format=raw&task=filter_vulnerable_extension&product=';
		url = url.concat(product);		
        jQuery.ajax({
            url: url,                            
            method: 'GET',
            error: function(request, status, error) {
                alert(request.responseText);
            },
            success: function(response){                                
                jQuery("#response_result").text("");
                jQuery("#response_result").append(response);                
                jQuery("#modal_vuln_extension").modal('show');                            
            }
        });
    } 
