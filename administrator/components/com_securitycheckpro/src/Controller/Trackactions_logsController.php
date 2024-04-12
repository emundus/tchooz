<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Plugin\System\Trackactions\helpers\TrackActionsHelper;

/**
 * Securitycheckpros  Controller
 */
class Trackactions_logsController extends BaseController
{
    
    /* Redirecciona las peticiones al Panel de Control */
    function redireccion_control_panel()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }


    /**
     * Method to export logs
     *
     * @return void
     */
    public function exportLogs()
    {
        // Get the logs data
        $data = $this->getModel('trackactions_logs')->getLogsData();
    
        // Export data to CSV file
        TrackActionsHelper::dataToCsv($data);
    }
    

    /**
     * Borrar log(s) de la base de datos
     */
    function delete()
    {
        $model = $this->getModel('trackactions_logs');
        $read = $model->delete();
    
        parent::display();
    }

    /**
     * Borrar todos los log(s) de la base de datos
     */
    function delete_all()
    {
        $model = $this->getModel('trackactions_logs');
        $read = $model->delete_all();
    
        parent::display();
    }


}
