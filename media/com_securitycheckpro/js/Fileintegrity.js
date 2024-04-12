 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

	var active_task = Joomla.getOptions("securitycheckpro.Fileintegrity.activetaskText");
	var task_failure = Joomla.getOptions("securitycheckpro.Fileintegrity.taskfailureText");
	var repair_log_view_header = Joomla.getOptions("securitycheckpro.Fileintegrity.repairviewlogheader");
	var process_completed = Joomla.getOptions("securitycheckpro.Fileintegrity.processcompletedText");
	var cont = 0;
    var etiqueta = '';
    var url = '';
    var percent = 0;
    var ended_string2 = Joomla.getOptions("securitycheckpro.Fileintegrity.end");
    var in_progress_string = Joomla.getOptions("securitycheckpro.Fileintegrity.inprogress");
    var error_string = Joomla.getOptions("securitycheckpro.Fileintegrity.error");
	var updating_stats = Joomla.getOptions("securitycheckpro.Fileintegrity.updatingstats");
    var now = '';
    var url_to_redirect = Joomla.getOptions("securitycheckpro.Fileintegrity.urltoredirect");
	var failure = Joomla.getOptions("securitycheckpro.Fileintegrity.failureText");
	var error_button = Joomla.getOptions("securitycheckpro.Fileintegrity.errorbutton");
	
	
    function get_percent() {
        url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent_integrity';
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
                    runButton();
                }                    
            }
        });
    }
    
    function estado_integrity_timediff() {        
        url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstadoIntegrity_Timediff';
        jQuery.ajax({
            url: url,                            
            method: 'GET',
            dataType: 'json',
            success: function(response){                
                var json = Object.keys(response).map(function(k) {return response[k] });
                var estado_integrity = json[0];
                var timediff = json[1];
                                            
                if ( ((estado_integrity != 'ENDED') && (estado_integrity != error_string)) && (timediff < 3) ) {
                    get_percent();
                } else if ( ((estado_integrity != 'ENDED') && (estado_integrity != error_string)) && (timediff > 3) ) {                    
                    hideElement('button_start_scan');
                    hideElement('task_status');
                    document.getElementById('task_error').style.display = "block";                    
                    document.getElementById('error_message').className = 'alert alert-danger';
                    document.getElementById('error_message').innerHTML = task_failure;            
                }                        
            },
            error: function(xhr, status) {                
            }
        });
    }
	
	/* charts functions */
	
	function sliceSize(dataNum, dataTotal) {
	  return (dataNum / dataTotal) * 360;
	}

	function addSlice(id, sliceSize, pieElement, offset, sliceID, color) {
	  $(pieElement).append("<div class='slice "+ sliceID + "'><span></span></div>");
	  var offset = offset - 1;
	  var sizeRotation = -179 + sliceSize;

	  $(id + " ." + sliceID).css({
		"transform": "rotate(" + offset + "deg) translate3d(0,0,0)"
	  });

	  $(id + " ." + sliceID + " span").css({
		"transform"       : "rotate(" + sizeRotation + "deg) translate3d(0,0,0)",
		"background-color": color
	  });
	}

	function iterateSlices(id, sliceSize, pieElement, offset, dataCount, sliceCount, color) {
	  var
		maxSize = 179,
		sliceID = "s" + dataCount + "-" + sliceCount;

	  if( sliceSize <= maxSize ) {
		addSlice(id, sliceSize, pieElement, offset, sliceID, color);
	  } else {
		addSlice(id, maxSize, pieElement, offset, sliceID, color);
		iterateSlices(id, sliceSize-maxSize, pieElement, offset+maxSize, dataCount, sliceCount+1, color);
	  }
	}

	function createPie(id) {
	  var
		listData      = [],
		listTotal     = 0,
		offset        = 0,
		i             = 0,
		pieElement    = id + " .pie-chart__pie"
		dataElement   = id + " .pie-chart__legend"

		color         = [
		  "cornflowerblue",
		  "olivedrab",
		  "orange",
		  "tomato",
		  "crimson",
		  "purple",
		  "turquoise",
		  "forestgreen",
		  "navy"
		];

	  color = shuffle( color );

	  $(dataElement+" span").each(function() {
		listData.push(Number($(this).html()));
	  });

	  for(i = 0; i < listData.length; i++) {
		listTotal += listData[i];
	  }

	  for(i=0; i < listData.length; i++) {
		var size = sliceSize(listData[i], listTotal);
		iterateSlices(id, size, pieElement, offset, i, 0, color[i]);
		$(dataElement + " li:nth-child(" + (i + 1) + ")").css("border-color", color[i]);
		offset += size;
	  }
	}

	function shuffle(a) {
		var j, x, i;
		for (i = a.length; i; i--) {
			j = Math.floor(Math.random() * i);
			x = a[i - 1];
			a[i - 1] = a[j];
			a[j] = x;
		}

		return a;
	}

	function createPieCharts() {		
		createPie('.pieID--micro-skills' ); 
	}
    
	jQuery(document).ready(function() {
    
		jQuery("#extensions_updated_tooltip").tooltip();
		 
        jQuery( "#filter_fileintegrity_search_clear" ).click(function() {
            document.getElementById('filter_fileintegrity_search').value=''; 
            jQuery("#adminForm").submit();
        });
        
        jQuery( "#add_exception_button" ).click(function() {
            Joomla.submitbutton('addfile_exception');
        });
        
        jQuery( "#delete_exception_button" ).click(function() {
            Joomla.submitbutton('deletefile_exception');
        });
        
        jQuery( "#button_start_scan" ).click(function() {
            hideElement('button_start_scan'); 
            hideElement('container_resultado'); 
            hideElement('container_repair'); 
            hideElement('completed_message2');
            runButton();
        });
        
        jQuery( "#view_modal_log_button" ).click(function() {
            view_modal_log();
        });
        
        hideElement('backup-progress');
		estado_integrity_timediff();
                
        // Chequeamos cuando se pulsa el botón 'close' del modal 'initialize data' para actualizar la página
        $(function() {
            $("#buttonclose").click(function() {
                setTimeout(function () {window.location.reload()},1000);                
            });
        });    
		
		createPieCharts();
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
    
    function runButton() {
        if ( cont == 0 ){
            document.getElementById('backup-progress').style.display = "flex";			
            document.getElementById('warning_message2').innerHTML = '';           
            date_time('start_time');                                
            percent = 0;
        } else if ( cont == 1 ){            
            document.getElementById('task_status').innerHTML = in_progress_string;
            url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones_integrity';
            jQuery.ajax({
                url: url,                            
                method: 'GET',
                success: function(responseText){                                                    
                }
            });                            
        } else {
            url = 'index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent_integrity';
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
                    document.getElementById('error_message').className = 'alert alert-danger';
                    document.getElementById('error_message').innerHTML = error_button;
                }
            });
        }
                        
        cont = cont + 1;
        
        if ( percent == 100) {
        
        } else if  ( (cont > 40) && (percent < 90) ) {
            var t = setTimeout(runButton,5000);
        } else {                                
            var t = setTimeout(runButton,1000);
        }
                                                    
    }    

