 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

    var cont_otp = 0;
    
    jQuery(document).ready(function()
    {    
            
        // Add timer to close system messages
        window.setTimeout(function ()
        {
            jQuery("#system-message-container").fadeTo(500, 0).slideUp(500, function ()
            {
                jQuery(this).remove();
            });
        }, 3000);
        
    });   
	
	function configure_toast($text,$auto){	
		jQuery('#toast-body').html($text);
		jQuery('#toast-auto').html($auto);		
		jQuery('#toast').toast('show');		
	}	
	
    function muestra_progreso_purge()
    {
        jQuery("#div_boton_subida").hide();
        jQuery("#div_loading").show();        
    }
    
    function hideElement(Id)
    {
        document.getElementById(Id).style.display = "none";
    }
    
    function view_modal_log()
    {    
        jQuery("#view_logfile").modal('show');
    }    
    
    var cont_initialize = 0;
    var etiqueta_initialize = '';
    var url_initialize = '';
    var request_initialize = '';
    var request_clean_tmp_dir = '';
    var clean_tmp_dir_result = '';
    var ended_string_initialize = Joomla.getOptions("securitycheckpro.Common.endedstringinitializeText");
	var loadinggif = Joomla.getOptions("securitycheckpro.Common.loadinggif");
	var filemanager_warning_message = Joomla.getOptions("securitycheckpro.Common.filemanagerwarningmessageText");
	var process_completed = Joomla.getOptions("securitycheckpro.Common.processcompletedText");
	var completed_error = Joomla.getOptions("securitycheckpro.Common.completederrorText");
	        
    function clear_data_button()
    {
        if (cont_initialize == 0)
        {                            
            document.getElementById('loading-container').innerHTML = loadinggif;
            document.getElementById('warning_message').innerHTML = filemanager_warning_message;
        } else if ( cont_initialize == 1 )
        {
            url_initialize = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones_clear_data';
            etiqueta_initialize = 'current_task';
        } else
        {
            url_initialize = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstadoClearData';
            etiqueta_initialize = 'warning_message';
        }
        
        jQuery.ajax({
            url: url_initialize,                            
            method: 'GET',
            success: function(response){                
                request_initialize = response;                        
            }
        });
            
        cont_initialize = cont_initialize + 1;
        
        if (request_initialize == ended_string_initialize)
        {            
            hideElement('loading-container');
            hideElement('warning_message');
            document.getElementById('completed_message').innerHTML = process_completed;
            document.getElementById('buttonclose').style.display = "block";        
            cont_initialize = 0;
        } else
        {
            var t = setTimeout("clear_data_button()",1000);                        
        }
                                                
    }    
    
    function clean_tmp_dir()
    {
        if (cont_initialize == 0)
        {                            
            document.getElementById('tmpdir-container').innerHTML = loadinggif;
            document.getElementById('warning_message_tmpdir').innerHTML = filemanager_warning_message;
        } else if ( cont_initialize == 1 )
        {
            url_initialize = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones_clean_tmp_dir';
            etiqueta_initialize = 'current_task';
        } else
        {
            url_initialize = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstadocleantmpdir';
            etiqueta_initialize = 'warning_message_tmpdir';
        }
        
        jQuery.ajax({
            url: url_initialize,                            
            method: 'GET',
            success: function(response_clean_tmp){                
                request_clean_tmp_dir = response_clean_tmp;                        
            }
        });
            
        cont_initialize = cont_initialize + 1;
                
        if (request_clean_tmp_dir == ended_string_initialize)
        {            
            hideElement('tmpdir-container');
            hideElement('warning_message_tmpdir');
            jQuery.ajax({
                url: 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getcleantmpdirmessage',                            
                method: 'GET',
                success: function(response_clean_tmp_message){                
                    clean_tmp_dir_result = response_clean_tmp_message;    
                    
                    if (clean_tmp_dir_result !== "")
                    {
                        document.getElementById('completed_message_tmpdir').className += " color_rojo";
                        document.getElementById('completed_message_tmpdir').innerHTML = completed_error;
                        document.getElementById('container_result_area').value = clean_tmp_dir_result;
                        document.getElementById('container_result').style.display = "block";    
                    } else 
                    {
                        document.getElementById('completed_message_tmpdir').className += " color_verde";
                        document.getElementById('completed_message_tmpdir').innerHTML = process_completed;                
                    }
                }
            });    
            
            document.getElementById('buttonclose_tmpdir').style.display = "block";
            cont_initialize = 0;
        } else
        {
            var t = setTimeout("clean_tmp_dir()",1000);                        
        }
                                                
    }    
	
	var passed = Joomla.getOptions("securitycheckpro.Common.passedText");
	var failed = Joomla.getOptions("securitycheckpro.Common.failedText");
	var otpstatus = Joomla.getOptions("securitycheckpro.Common.otpstatusText");
	var moreinfo = Joomla.getOptions("securitycheckpro.Common.moreinfoText");
		    
    function get_otp_status()
    {        
         
        var twofactor_status = Joomla.getOptions("securitycheckpro.Common.twofactorstatus");
        var otp_enabled = Joomla.getOptions("securitycheckpro.Common.otpenabled");
		var type = "error";
		var text_message = failed;
        
        if (otp_enabled == 1)
        {
            if (twofactor_status >= 2)
            {
                type = "success";
                text_message = passed;
            }
        }      
        
        show_otp_status(text_message,type,twofactor_status,otp_enabled);
    }
    
    function show_otp_status(otp_text,otp_type,twofactor_status,otp_enabled)
    {
        swal({
          title: otpstatus,
          text: otp_text,
          type:    otp_type,
          showCancelButton: true,
          cancelButtonClass: "btn-success",
          cancelButtonText: moreinfo
        },
        function(isConfirm)
        {
            if (isConfirm)
            {                
            } else
            {
                var url = "https://scpdocs.securitycheckextensions.com/troubleshooting/otp";
                window.open(url);
            }
        });
        
        // Contenido extra que será mostrado en el pop-up con el resultado
        var extra_content= Joomla.getOptions("securitycheckpro.Common.extracontent");
        
        if (extra_content && (cont_otp < 1))
        {            
            jQuery( ".form-group" ).after( extra_content );                                                    
            cont_otp++;
        }
        
        if (otp_enabled == 0)
        {
            var otp_enabled_content = Joomla.getOptions("securitycheckpro.Common.otpenabledcontent");
            if (cont_otp < 2)
            {
                jQuery( ".form-group" ).after( otp_enabled_content );    
                cont_otp++;
            }
        } 
        
        if (twofactor_status == 0)
        {
            var status_content = Joomla.getOptions("securitycheckpro.Common.no2faenabled");
            if (cont_otp < 2)
            {
                jQuery( ".form-group" ).after( status_content );    
                cont_otp++;
            }
        } else if (twofactor_status == 1)
        {
            var status_content = Joomla.getOptions("securitycheckpro.Common.no2fauserenabled");
            if (cont_otp < 2)
            {
                jQuery( ".form-group" ).after( status_content );    
                cont_otp++;
            }
        }                
        
    }