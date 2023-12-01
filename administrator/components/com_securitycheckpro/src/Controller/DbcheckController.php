<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// Protección frente a accesos no autorizados
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

class DbcheckController extends BaseController
{
    
    public function optimize()
    {
        $app     = Factory::getApplication();
        $model     = $this->getModel('Dbcheck');
        
        if (!($result = $model->optimizeTables())) {
            echo $model->getError();
        } else
        {
            echo Text::sprintf('COM_SECURITYCHECKPRO_DB_OPTIMIZE_RESULT', $result['optimize'], $result['repair']);
        }
        
        $app->close();
    }

    /* Redirecciona las peticiones al Panel de Control */
    function redireccion_control_panel()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }
}