/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";
    
    jQuery(document).ready(function() {
        
        jQuery( "#disable_firewall_button" ).click(function() {
            Joomla.submitbutton('disable_firewall');
        });
        
        jQuery( "#enable_firewall_button" ).click(function() {
            Joomla.submitbutton('enable_firewall');
        });
        
        jQuery( "#disable_cron_button" ).click(function() {
            Joomla.submitbutton('disable_cron');
        });
        
        jQuery( "#enable_cron_button" ).click(function() {
            Joomla.submitbutton('enable_cron');
        });
        
        jQuery( "#disable_update_database_button" ).click(function() {
            Joomla.submitbutton('disable_update_database');
        });
        
        jQuery( "#enable_update_database_button" ).click(function() {
            Joomla.submitbutton('enable_update_database');
        });
        
        jQuery( "#disable_spam_protection_button" ).click(function() {
            Joomla.submitbutton('disable_spam_protection');
        });
        
        jQuery( "#enable_spam_protection_button" ).click(function() {
            Joomla.submitbutton('enable_spam_protection');
        });
        
        jQuery( "#manage_lists_button" ).click(function() {
			SetActiveTab('#li_lists_tab');			
            Joomla.submitbutton('manage_lists');
        });
        
        jQuery( "#go_system_info_buton" ).click(function() {
            Joomla.submitbutton('Go_system_info');
        });
        
        jQuery( "#unlock_tables_button" ).click(function() {
            Joomla.submitbutton('unlock_tables');
        });
        
        jQuery( "#lock_tables_button" ).click(function() {
            Joomla.submitbutton('lock_tables');
        });
        
        jQuery( "#apply_default_config_button" ).click(function() {
            Set_Default_Config();
        });
        
        jQuery( "#apply_easy_config_button" ).click(function() {
            Set_Easy_Config();
        });
        
		var blockedaccessText = Joomla.getOptions("securitycheckpro.Cpanel.blockedaccessText");
		var userandsessionprotectionText = Joomla.getOptions("securitycheckpro.Cpanel.userandsessionprotectionText");
		var firewallrulesappliedText = Joomla.getOptions("securitycheckpro.Cpanel.firewallrulesappliedText");
		var total_blocked_access = Joomla.getOptions("securitycheckpro.Cpanel.totalblockedaccess", 0);
		var total_user_session_protection = Joomla.getOptions("securitycheckpro.Cpanel.totalusersessionprotection", 0);
		var total_firewall_rules = Joomla.getOptions("securitycheckpro.Cpanel.totalfirewallrules", 0);
        
        // Actualizamos los datos del gráfico 'pie'
        Chart.defaults.global.defaultFontFamily='-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif',Chart.defaults.global.defaultFontColor="#cdcdcd";var ctx=document.getElementById("piechart"),piechart=new Chart(ctx,{type:"pie",data:{labels:[blockedaccessText,userandsessionprotectionText,firewallrulesappliedText],datasets:[{data:[total_blocked_access,total_user_session_protection,total_firewall_rules],backgroundColor:["#007bff","#dc3545","#ffc107"]}]}});
    
        
        //Tooltip subscripcion
        jQuery("#subscriptions_status").tooltip();
        jQuery("#scp_version").tooltip();
        jQuery("#update_database_version").tooltip();
		jQuery("#trackactions_version").tooltip();
		
		// Si existe el mensaje informativo lo ocultamos en 5 segundos
		var element =  document.getElementById('mensaje_informativo');
		if (typeof(element) != 'undefined' && element != null)
		{
		  window.setTimeout(function () {
                jQuery("#mensaje_informativo").fadeTo(500, 0).slideUp(500, function () {
                    jQuery(this).remove();
                });
            }, 5000);
		}        
        
    });
    
    
    function muestra_progreso(){
        jQuery("#div_boton_subida").hide();
        jQuery("#div_loading").show();
    }

    function Set_Easy_Config() {
        var url = 'index.php?option=com_securitycheckpro&controller=cpanel&format=raw&task=Set_Easy_Config';
        jQuery.ajax({
            url: url,                            
            method: 'GET',
            success: function(data){
                location.reload();                
            }
        });
    }
    
    function Set_Default_Config() {
		var message = Joomla.getOptions("securitycheckpro.Cpanel.setdefaultconfigconfirmText");
		var answer = confirm(message);
		var url = 'index.php?option=com_securitycheckpro&controller=cpanel&format=raw&task=Set_Default_Config';
        if (!answer) {
            e.preventDefault();
        }       
        jQuery.ajax({
            url: url,                            
            method: 'GET',
            success: function(data){
                location.reload();                
            }
        });
    }
    var ActiveTab = "lists"; 
        
    function SetActiveTab($value) {
        ActiveTab = $value;
        storeValue('active', ActiveTab);
    }
    
    function storeValue(key, value) {
        if (localStorage) {
            localStorage.setItem(key, value);
        } else {
            $.cookies.set(key, value);
        }
    }        
