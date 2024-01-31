 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

	var contenido = Joomla.getOptions("securitycheckpro.Onlinechecks.contenido");

    jQuery(document).ready(function() {    
    
        jQuery( "#filter_onlinechecks_search_button" ).click(function() {
            document.getElementById('filter_onlinechecks_search').value='';
            this.form.submit();
        });
                    
        // Chequeamos cuando se pulsa el botón 'close' del modal 'initialize data' para actualizar la página
        $(function() {
            $("#buttonclose").click(function() {
                setTimeout(function () {window.location.reload()},1000);                
            });
        });            
        
        if (contenido != "vacio") {    
            jQuery("#view_file").modal('show');
			contenido = "vacio";
        } 
    });        

