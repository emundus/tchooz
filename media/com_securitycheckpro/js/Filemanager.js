 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

	var active_task = Joomla.getOptions("securitycheckpro.Filemanager.activetaskText");
	var task_failure = Joomla.getOptions("securitycheckpro.Filemanager.taskfailureText");
	var repair_log_view_header = Joomla.getOptions("securitycheckpro.Filemanager.repairviewlogheader");
	var process_completed = Joomla.getOptions("securitycheckpro.Filemanager.processcompletedText");
	var launch_new_task = Joomla.getOptions("securitycheckpro.Filemanager.launchnewtask");
	var div_view_log_button = Joomla.getOptions("securitycheckpro.Filemanager.divviewlogbutton");
	var cont = 0;
    var etiqueta = '';
    var url = '';
    var percent = 0;
    var ended_string2 = Joomla.getOptions("securitycheckpro.Filemanager.end");
    var in_progress_string = Joomla.getOptions("securitycheckpro.Filemanager.inprogress");
    var error_string = Joomla.getOptions("securitycheckpro.Filemanager.error");
	var updating_stats = Joomla.getOptions("securitycheckpro.Filemanager.updatingstats");
    var now = '';
    var respuesta_reparar = '';
	var url_to_redirect = Joomla.getOptions("securitycheckpro.Filemanager.urltoredirect");
	var failure = Joomla.getOptions("securitycheckpro.Filemanager.failureText");
	var error_button = Joomla.getOptions("securitycheckpro.Filemanager.errorbutton");
	var repair_launched =  Joomla.getOptions("securitycheckpro.Filemanager.repairlaunched");
    
    function get_percent() {
        url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent';
        jQuery.ajax({
            url: url,                            
            method: 'GET',
            success: function(responseText){                    
                if ( responseText < 100 ) {
                    document.getElementById('task_status').innerHTML = in_progress_string;
                    document.getElementById('warning_message2').innerHTML = '';
                    document.getElementById('error_message').className = 'alert alert-info';
                    document.getElementById('error_message').innerHTML = active_task;                    
                    hideElement('button_start_scan');
                    cont = 3;                    
                    boton_filenamager();
                }                    
            }
        });
    }
    
    function estado_timediff() {        
        url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstado_Timediff';
        jQuery.ajax({
            url: url,                            
            method: 'GET',
            dataType: 'json',
            success: function(response){                
                var json = Object.keys(response).map(function(k) {return response[k] });
                var estado = json[0];
                var timediff = json[1];
                                            
                if ( ((estado != 'ENDED') && (estado != error_string)) && (timediff < 3) ) {
                    get_percent();
                } else if ( ((estado != 'ENDED') && (estado != error_string)) && (timediff > 3) ) {                    
                    hideElement('button_start_scan');
                    hideElement('task_status');
                    document.getElementById('task_error').style.display = "block";                    
                    document.getElementById('error_message').className = 'alert alert-error';
                    document.getElementById('error_message').innerHTML = task_failure;            
                }                        
            },
            error: function(xhr, status) {                
            }
        });
    }
    
        
    function showLog() {
        document.getElementById('completed_message2').innerHTML = '';
        document.getElementById('div_view_log_button').innerHTML = '';
        document.getElementById('log-container_header').innerHTML = repair_log_view_header;
        document.getElementById('log-text').style.display = "block";
    }
    
    jQuery(document).ready(function() {    
    
        jQuery( "#button_start_scan" ).click(function() {
            hideElement('button_start_scan');
            hideElement('container_resultado'); 
            hideElement('container_repair'); 
            hideElement('completed_message2'); 
            boton_filenamager();
        });
        
        jQuery( "#view_modal_log_button" ).click(function() {
            view_modal_log();
        });
        
        jQuery( "#filter_filemanager_search_clear_button" ).click(function() {
            document.getElementById('filter_filemanager_search').value=''; 
            jQuery("#adminForm").submit();
        });
        
        jQuery( "#add_exception_button" ).click(function() {
            Joomla.submitbutton('addfile_exception');
        });
        
        jQuery( "#repair_button" ).click(function() {
            Joomla.submitbutton('repair');
        });
        
        jQuery( "#delete_exception_button" ).click(function() {
            Joomla.submitbutton('deletefile_exception');
        });
        
        hideElement('container_repair');
        
        if ( repair_launched ) {
            hideElement('container_resultado');
            document.getElementById('container_repair').style.display = "block";
            document.getElementById('completed_message2').innerHTML = process_completed;
            document.getElementById('log-container_remember_text').innerHTML = launch_new_task;
            document.getElementById('div_view_log_button').innerHTML = div_view_log_button;
            hideElement('log-text');                        
        }        
        hideElement('backup-progress');
        estado_timediff();
                
        // Chequeamos cuando se pulsa el botón 'close' del modal 'initialize data' para actualizar la página
        $(function() {
            $("#buttonclose").click(function() {
                setTimeout(function () {window.location.reload()},1000);                
            });
        });        
    });            
        
    function date_time(id) {
        url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=currentDateTime';
		jQuery.ajax({
			url: url,                            
			method: 'GET',
			success: function(responseText){	
				document.getElementById(id).innerHTML = responseText;
			},
			error: function(responseText) { 
				
			}
		});	        
    }
    
    function boton_filenamager() {
        if ( cont == 0 ){
            document.getElementById('backup-progress').style.display = "flex";
            document.getElementById('warning_message2').innerHTML = '';            
            date_time('start_time');                                
            percent = 0;
        } else if ( cont == 1 ){            
            document.getElementById('task_status').innerHTML = in_progress_string;
            url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones';
            jQuery.ajax({
                url: url,                            
                method: 'GET',
                success: function(responseText){                                                    
                }
            });                            
        } else {
            url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent';
            jQuery.ajax({
                url: url,                            
                method: 'GET',
                success: function(responseText){
                    percent = responseText;                    
                    document.getElementById('bar').style.width = percent + "%";
                    if (percent == 100) {                        
                        date_time('end_time');
                        hideElement('error_message');
                        document.getElementById('task_status').innerHTML = ended_string2;
                        document.getElementById('bar').style.width = 100 + "%";
                        document.getElementById('completed_message2').innerHTML = process_completed;
                        document.getElementById('warning_message2').innerHTML = updating_stats;                                                
                        //setTimeout(function () {window.location.reload()},2000);                              
                        window.location.href = url_to_redirect;
                    }
                },
                error: function(responseText) {
                    document.getElementById('task_error').style.display = "block";
                    hideElement('backup-progress');
                    hideElement('task_status');    
                    document.getElementById('warning_message2').innerHTML = '';
                    document.getElementById('error_message').className = 'alert alert-error';
                    document.getElementById('error_message').innerHTML = failure;
                    document.getElementById('error_button').innerHTML = error_button;
                }
            });
        }
                        
        cont = cont + 1;
        
        if ( percent == 100) {
        
        } else if  ( (cont > 40) && (percent < 90) ) {
            var t = setTimeout("boton_filenamager()",75000);
        } else {                                
            var t = setTimeout("boton_filenamager()",1000);
        }
                                                    
    }
    
    function repair() {
        hideElement('container_resultado');
        document.getElementById('backup-progress').style.display = "block";
        document.getElementById('bar').style.width = 100 + "%";
        document.getElementById('completed_message2').innerHTML = process_completed;
        document.getElementById('warning_message2').innerHTML = updatingstats;    
    }