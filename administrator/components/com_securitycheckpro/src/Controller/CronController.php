<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;
// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;

/**
 * Securitycheckpros  Controller
 */
class CronController extends SecuritycheckproBaseController
{

    /* Redirecciona las peticiones al componente */
    function redireccion()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }


    /* Guarda los cambios y redirige al cPanel */
    public function save()
    {
        $model = $this->getModel('cron');
        $jinput = Factory::getApplication()->input;
        $data = $jinput->getArray($_POST);
        
        $model->saveConfig($data, 'cron_plugin');

        $this->setRedirect('index.php?option=com_securitycheckpro&view=cron&'. Session::getFormToken() .'=1', Text::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
    }

    /* Guarda los cambios */
    public function apply()
    {
        $this->save('cron_plugin');
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=cron&view=cron&'. Session::getFormToken() .'=1', Text::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
    }

}
