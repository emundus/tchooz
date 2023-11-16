<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;

Session::checkToken('get') or die('Invalid Token');

// Cargamos los archivos javascript necesarios
$document = Factory::getDocument();

$document->addScript(Uri::root().'media/com_securitycheckpro/new/js/sweetalert.min.js');

// Add style declaration
$media_url = "media/com_securitycheckpro/stylesheets/cpanelui.css";
HTMLHelper::stylesheet($media_url);

$sweet = "media/com_securitycheckpro/stylesheets/sweetalert.css";
HTMLHelper::stylesheet($sweet);

?>

<?php 
// Cargamos el contenido común...
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/common.php';

// ... y el contenido específico
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/upload.php';
?>

<form enctype="multipart/form-data" method="post" class="margin-top-minus18" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
                        
           
            <div class="card mb-6">
                <div class="card-body">
                    <div class="alert alert-warning">
						<?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS_ALERT'); ?>
                    </div>                
                    
                    <div class="col-6 mb-3">
						<label for="formFile" class="form-label"><?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS'); ?></label>
						<input type="file" id="file_to_import" name="file_to_import" class="form-control" onchange="$(this).next().after().text($(this).val().split('\\').slice(-1)[0])">			
					</div>
					<input class="btn btn-primary" class="margin-left-20" type="button" id="read_file_button" value="<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOAD_AND_IMPORT'); ?>" />
				</div>
			</div>
</div>

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="upload" />

</form>
