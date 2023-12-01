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
        
        $view->document = $document;
        $view->display();		
    }
}