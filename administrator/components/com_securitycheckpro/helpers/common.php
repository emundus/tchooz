<?php
defined('_JEXEC') or die();

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;

echo '<link href="' . Uri::root() .'media/com_securitycheckpro/new/vendor/bootstrap/css/bootstrap_j4.css" rel="stylesheet">';

// Custom styles for this template-->
echo '<link href="' . Uri::root() .'media/com_securitycheckpro/new/css/sb-admin.css" rel="stylesheet">';
// Custom fonts for this template-->
echo '<link href="' . Uri::root() .'media/com_securitycheckpro/new/vendor/font-awesome/css/fontawesome.css" rel="stylesheet" type="text/css">';
echo '<link href="' . Uri::root() .'media/com_securitycheckpro/new/vendor/font-awesome/css/fa-solid.css" rel="stylesheet" type="text/css">';

echo '<script src="' . URI::root() . 'media/vendor/jquery/js/jquery.min.js"></script>';
?>

<script type="text/javascript" language="javascript">    
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
    
	function show_left_menu()
	{
		if(window.MooTools) {
			// Mootools loaded. Let's force each element of the left meny to be shown (if we do not do this the elements dissapear on hover them)
			var nav_items = document.getElementsByClassName('nav2-item');
			for (var i = 0; i < nav_items.length; i++) {
			  nav_items[i].style.display = "block";
			}	
		}			
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
    var ended_string_initialize = '<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED'); ?>';
        
    function clear_data_button()
    {
        if (cont_initialize == 0)
        {                            
            document.getElementById('loading-container').innerHTML = '<?php echo ('<img src="../media/com_securitycheckpro/images/loading.gif" title="' . Text::_('loading') .'" alt="' . Text::_('loading') .'">'); ?>';
            document.getElementById('warning_message').innerHTML = '<?php echo addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_WARNING_MESSAGE')); ?>';
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
            document.getElementById('completed_message').innerHTML = '<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PROCESS_COMPLETED'); ?>';
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
            document.getElementById('tmpdir-container').innerHTML = '<?php echo ('<img src="../media/com_securitycheckpro/images/loading.gif" title="' . Text::_('loading') .'" alt="' . Text::_('loading') .'">'); ?>';
            document.getElementById('warning_message_tmpdir').innerHTML = '<?php echo addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_WARNING_MESSAGE')); ?>';
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
                        document.getElementById('completed_message_tmpdir').innerHTML = '<?php echo Text::_('COM_SECURITYCHECKPRO_COMPLETED_ERRORS'); ?>';
                        document.getElementById('container_result_area').value = clean_tmp_dir_result;
                        document.getElementById('container_result').style.display = "block";    
                    } else 
                    {
                        document.getElementById('completed_message_tmpdir').className += " color_verde";
                        document.getElementById('completed_message_tmpdir').innerHTML = '<?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PROCESS_COMPLETED'); ?>';                
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
    
    function get_otp_status()
    {        
    <?php 
    // Obtenemos el valor de la variable de estado "resultado_scans", que indicará si el escaneo ha sido correcto o incorrecto
    $model = new JsonModel();
    $two_factor = $model->get_two_factor_status();
            
    $params = ComponentHelper::getParams('com_securitycheckpro');
    $otp_enabled = $params->get('otp', 1);
    ?>
        
        status = "<?php echo $two_factor; ?>";
        otp_enabled = "<?php echo $otp_enabled; ?>";
        
        if (otp_enabled == 1)
        {
            if (status >= 2)
            {
                type = "success";
                text = "<?php echo Text::_('COM_SECURITYCHECKPRO_PASSED'); ?>";
            } else
            {
                type = "error";
                text = "<?php echo Text::_('COM_SECURITYCHECKPRO_FAILED'); ?>";
            }
        } else
        {
            type = "error";
            text = "<?php echo Text::_('COM_SECURITYCHECKPRO_FAILED'); ?>";
        }        
        
        show_otp_status(text,type,status,otp_enabled);
    }
    
    function show_otp_status(otp_text,otp_type,status,otp_enabled)
    {
        swal({
          title: "<?php echo Text::_('COM_SECURITYCHECKPRO_OTP_STATUS'); ?>",
          text: otp_text,
          type:    otp_type,
          showCancelButton: true,
          cancelButtonClass: "btn-success",
          cancelButtonText: "<?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>"
        },
        function(isConfirm)
        {
            if (isConfirm)
            {                
            } else
            {
                url = "https://scpdocs.securitycheckextensions.com/troubleshooting/otp";
                window.open(url);
            }
        });
        
        // Contenido extra que será mostrado en el pop-up con el resultado
        var extra_content= '<?php echo "<div class=\"card card-info bg-info h-100 text-center pt-2\" style=\"margin-bottom: 10px;\"><div class=\"card-block card-title\" style=\"color: #fff;\">" . Text::_('COM_SECURITYCHECKPRO_OTP_DESCRIPTION') . "</div></div>" ?>';
        
        if (extra_content && (cont_otp < 1))
        {            
            jQuery( ".form-group" ).after( extra_content );                                                    
            cont_otp++;
        }
        
        if (otp_enabled == 0)
        {
            var otp_enabled_content = '<?php echo "<div style=\"margin-top: 10px; margin-bottom: 10px;\"><span class=\"badge badge-danger\">" . Text::_('COM_SECURITYCHECKPRO_OTP_DISABLED') . "</span></div>"?>';
            if (cont_otp < 2)
            {
                jQuery( ".form-group" ).after( otp_enabled_content );    
                cont_otp++;
            }
        } 
        
        if (status == 0)
        {
            var status_content = '<?php echo "<div style=\"margin-top: 10px; margin-bottom: 10px;\"><span class=\"badge badge-danger\">" . Text::_('COM_SECURITYCHECKPRO_NO_2FA_ENABLED') . "</span></div>"?>';
            if (cont_otp < 2)
            {
                jQuery( ".form-group" ).after( status_content );    
                cont_otp++;
            }
        } else if (status == 1)
        {
            var status_content = '<?php echo "<div style=\"margin-top: 10px; margin-bottom: 10px;\"><span class=\"badge badge-danger\">" . Text::_('COM_SECURITYCHECKPRO_NO_2FA_USER_ENABLED') . "</span></div>"?>';
            if (cont_otp < 2)
            {
                jQuery( ".form-group" ).after( status_content );    
                cont_otp++;
            }
        }                
        
    }
</script>
