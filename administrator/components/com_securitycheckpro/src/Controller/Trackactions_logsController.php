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
use Joomla\Plugin\Actionlog\Trackactions\Model\TrackActionsHelperModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\Trackactions_logsModel;

/**
 * Tracactions_logs Controller
 */
class Trackactions_logsController extends SecuritycheckproBaseController
{    
	/**
     * Borrar todos los log(s) de la base de datos
	 *
	 * @return void
     */
    function redireccion_control_panel():void {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }


    /**
     * Method to export logs
     *
     * @return void
     */
    public function exportLogs():void {
		if (!Session::checkToken()) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

        // Get the logs data
		$model = $this->getModel('Trackactions_logs');
		if (!$model instanceof Trackactions_logsModel) {
			Factory::getApplication()->enqueueMessage('Trackactions_logs model not found', 'error');
			return;
		}
        $data = $model->getLogsData();

        // El plugin Track Actions instalado puede ser una version antigua que no tenga esta clase todavia
        if (!class_exists(TrackActionsHelperModel::class)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_NEEDS_UPDATE'), 'warning');
            return;
        }

        // Export data to CSV file
		// @phpstan-ignore-next-line
        TrackActionsHelperModel::dataToCsv($data);
    }
    

    /**
     * Borrar log(s) de la base de datos
	 *
	 * @return void
     */
    function delete():void {
		if (!Session::checkToken()) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

		$model = $this->getModel('Trackactions_logs');
		if (!$model instanceof Trackactions_logsModel) {
			Factory::getApplication()->enqueueMessage('Trackactions_logs model not found', 'error');
			return;
		}
        $model->delete();
    
        parent::display();
    }

    /**
     * Borrar todos los log(s) de la base de datos
	 *
	 * @return void
     */
    function delete_all():void {
		if (!Session::checkToken()) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

        $model = $this->getModel('Trackactions_logs');
		if (!$model instanceof Trackactions_logsModel) {
			Factory::getApplication()->enqueueMessage('Trackactions_logs model not found', 'error');
			return;
		}
        $model->delete_all();
    
        parent::display();
    }


}
