<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;

class DisplayController extends BaseController
{
 
	/**
     * Displays the Control Panel 
     */
    public function display($cachable = false, $urlparams = Array())
    {
		
        $document = Factory::getDocument();
        $viewName = $this->input->getCmd('view', 'Cpanel');
        $viewFormat = $document->getType();
		        
        $view = $this->getView($viewName, $viewFormat);	
        $view->setModel($this->getModel($viewName), true);
		
		// Params used for the OTP feature
		$front_model = new JsonModel();
		$two_factor = $front_model->get_two_factor_status();
				
		$params = ComponentHelper::getParams('com_securitycheckpro');
		$otp_enabled = $params->get('otp', 1);
		
		// Pass parameters to the common.js script using Joomla's script options API
		$document->addScriptOptions('securitycheckpro.Common.loadinggif', '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
		$document->addScriptOptions('securitycheckpro.Common.endedstringinitializeText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED')));
		$document->addScriptOptions('securitycheckpro.Common.filemanagerwarningmessageText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_WARNING_MESSAGE')));
		$document->addScriptOptions('securitycheckpro.Common.processcompletedText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PROCESS_COMPLETED')));
		$document->addScriptOptions('securitycheckpro.Common.completederrorText', addslashes(Text::_('COM_SECURITYCHECKPRO_COMPLETED_ERRORS')));
		$document->addScriptOptions('securitycheckpro.Common.passedText', addslashes(Text::_('COM_SECURITYCHECKPRO_PASSED')));
		$document->addScriptOptions('securitycheckpro.Common.failedText', addslashes(Text::_('COM_SECURITYCHECKPRO_FAILED')));
		$document->addScriptOptions('securitycheckpro.Common.otpstatusText', addslashes(Text::_('COM_SECURITYCHECKPRO_OTP_STATUS')));
		$document->addScriptOptions('securitycheckpro.Common.moreinfoText', addslashes(Text::_('COM_SECURITYCHECKPRO_MORE_INFO')));
		$document->addScriptOptions('securitycheckpro.Common.extracontent', '<div class="card card-info bg-info h-100 text-center pt-2" style="margin-bottom: 10px;"><div class="card-block card-title" style="color: #fff;">' . addslashes(Text::_('COM_SECURITYCHECKPRO_OTP_DESCRIPTION')) . "</div></div>");
		$document->addScriptOptions('securitycheckpro.Common.otpenabledcontent', '<div style="margin-top: 10px; margin-bottom: 10px;"><span class="badge bg-danger">' . addslashes(Text::_('COM_SECURITYCHECKPRO_OTP_DISABLED')) . "</span></div>");
		$document->addScriptOptions('securitycheckpro.Common.no2faenabled', '<div style="margin-top: 10px; margin-bottom: 10px;"><span class="badge bg-danger">' . addslashes(Text::_('COM_SECURITYCHECKPRO_NO_2FA_ENABLED')) . "</span></div>");
		$document->addScriptOptions('securitycheckpro.Common.no2fauserenabled', '<div style="margin-top: 10px; margin-bottom: 10px;"><span class="badge bg-danger">' . addslashes(Text::_('COM_SECURITYCHECKPRO_NO_2FA_USER_ENABLED')) . "</span></div>");
		$document->addScriptOptions('securitycheckpro.Common.twofactorstatus', addslashes($two_factor));
		$document->addScriptOptions('securitycheckpro.Common.otpenabled', (int) $otp_enabled);   
        
        $view->document = $document;
        $view->display();		
    }
}