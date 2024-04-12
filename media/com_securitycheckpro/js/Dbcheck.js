 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

	var tables_data = Joomla.getOptions("securitycheckpro.Dbcheck.tables");
	
	console.log(tables_data);

    jQuery(document).ready(function() {            
        //Tooltips
        jQuery("#show_tables").tooltip();
		jQuery("#last_optimization_tooltip").tooltip();	
		        
        jQuery( "#start_db_check" ).click(function() {
            StartDbCheck();
        });
    });
    
    var requestTimeOut = {};
    requestTimeOut.Seconds = 60;

    
    var Database = {};
    Database.Check = {
        unhide: function(item) {
            return $(item).removeClass('hidden');
        },
        tables: [],
        tablesNum: 0,
        table: '',
        content: '',
        prefix: '',
        startCheck: function() {
            this.table      = $('#' + this.prefix + '-table');
            this.content = $('#' + this.prefix);
            if (!this.tables.length) {
                return false;
            }
            
            this.unhide(this.content);
            this.content.hide().show('fast', function() {
                Database.Check.stepCheck(0);
            });
        },
        stopCheck: function() {
            
        },
        setProgress: function(index) {
            if ($('#' + this.prefix + '-progress .securitycheckpro-bar').length > 0) {
                var currentProgress = (100 / this.tablesNum) * index;
                $('#' + this.prefix + '-progress .securitycheckpro-bar').css('width', currentProgress + '%');                
            }
        },
        stepCheck: function(index) {
            this.setProgress(index);
            if (!this.tables || !this.tables.length) {
                this.stopCheck();
                return false;
            }
            
            this.unhide(this.table.find('tr')[index+1]);
            
                        
            var jArray= JSON.stringify(tables_data);
            var table = jArray[index]['Name'];
            var engine = jArray[index]['Engine'];
            $.ajax({
                type: 'POST',
                url: 'index.php?option=com_securitycheckpro&controller=dbcheck',
                data: {
                    task: 'optimize',
                    table: table,
                    engine: engine,
                    sid: Math.random()
                },
                success: function(data) {
                    $('#result' + index).html(data);
                    if (requestTimeOut.Seconds != 0) {    
                        setTimeout(function(){Database.Check.stepCheck(index+1)}, 60);                        
                    }
                    else {                        
                        Database.Check.stepCheck(index+1);                        
                    }
                }
            });
        }
    }
    
    
    // DB Check
    function StartDbCheck() {
        hideElement('buttondatabase');
        
        Database.Check.unhide('#securitycheck-bootstrap-database');
                
        Database.Check.prefix = 'securitycheck-bootstrap-database';
        Database.Check.tables = [];
		for (const table of tables_data) {
			Database.Check.tables.push(table['Name']);
		}    
        Database.Check.tablesNum = Database.Check.tables.length;
        
        Database.Check.stopCheck = function() {
            $('#securitycheck-bootstrap-database-progress').fadeOut('fast', function(){$(this).remove()});            
        }
        
        Database.Check.startCheck();    
    }