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
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Securitycheckpros  Controller
 */
class RulesController extends BaseController
{
   
    /* Método para aplicar las reglas a un grupo o conjunto de grupos */
    public function apply_rules()
    {
        // Inicializamos las variables.
        $jinput = Factory::getApplication()->input;
        $ids    =$jinput->getVar('cid', '', 'array');
    
        if (empty($ids)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_RULES_NO_GROUPS_SELECTED'), 'warning');
        } else
        {
            // Obtenemos el modelo
            $model = $this->getModel("rules");

            // Cambiamos el estado de los registros seleccionados
            if (!$model->apply_rules()) {
                Factory::getApplication()->enqueueMessage($model->getError(), 'warning');
            } else 
            {
                $this->setMessage(Text::plural('COM_SECURITYCHECKPRO_RULES_N_GROUPS_SELECTED', count($ids)));
            }
        }

        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=rules&'. Session::getFormToken() .'=1');
    }

    /* Método para NO aplicar las reglas a un grupo o conjunto de grupos */
    public function not_apply_rules()
    {
        // Inicializamos las variables.
        $jinput = Factory::getApplication()->input;
        $ids    =$jinput->getVar('cid', '', 'array');
    
        if (empty($ids)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_RULES_NO_GROUPS_SELECTED'), 'warning');
        } else 
        {
            // Obtenemos el modelo
            $model = $this->getModel("rules");

            // Cambiamos el estado de los registros seleccionados
            if (!$model->not_apply_rules()) {
                Factory::getApplication()->enqueueMessage($model->getError(), 'warning');
            } else 
            {
                $this->setMessage(Text::plural('COM_SECURITYCHECKPRO_RULES_N_GROUPS_SELECTED', count($ids)));
            }
        }

        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=rules&'. Session::getFormToken() .'=1');
    }

    /* Muestra las entradas de confianza */
    function rules_logs()
    {
        $jinput = Factory::getApplication()->input;
        $jinput->set('view', 'ruleslogs');
    
        parent::display();
    }

    /* Redirecciona las peticiones al componente */
    function redireccion()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=rules&view=rules');
    }

    /* Redirecciona las peticiones al Panel de Control */
    function redireccion_control_panel()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }

}
