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
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

class DisplayController extends BaseController
{
	/**
     * Constructor.
     *
     * @param array<string, mixed> $config
     */
	public function __construct(
        array $config = [],
        ?MVCFactoryInterface $factory = null,
        ?CMSApplicationInterface $app = null,
        ?Input $input = null
    ) {
        parent::__construct($config, $factory, $app, $input);
    }
	
	/**
     * Typical view method for MVC based architecture
     *
     * This function is provide as a default implementation, in most cases
     * you will need to override it in your own controllers.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array<mixed>    $urlparams  An array of safe url parameters and their variable types.
     *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
     *
     * @return  static|void  A \JControllerLegacy object to support chaining.
     *
     * @since   3.0
     * @throws  \Exception
     */
    public function display($cachable = false, $urlparams = [])
    {
		$app = Factory::getApplication();
				
		if ( !($app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface) || !($app instanceof \Joomla\CMS\Application\AdministratorApplication) ) {	
			throw new \RuntimeException('Expected CMSWebApplicationInterface or AdministratorApplication');
		}
		$document = $app->getDocument();
        $viewName = $this->input->getCmd('view', 'Cpanel');
        $viewFormat = $document->getType();
				        
		/** @var \Joomla\CMS\MVC\View\ViewInterface $view */
        $view = $this->getView($viewName, $viewFormat);	
		
		if (!$view instanceof HtmlView) {			
			throw new \RuntimeException('Expected HtmlView');
		}
		
        $view->setModel($this->getModel($viewName), true);
		
		
		// Pass parameters to the common.js script using Joomla's script options API
		Text::script('COM_SECURITYCHECKPRO_FILEMANAGER_PROCESS_COMPLETED');
		Text::script('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED');
		Text::script('COM_SECURITYCHECKPRO_FILEMANAGER_WARNING_MESSAGE');
		Text::script('COM_SECURITYCHECKPRO_COMPLETED_ERRORS');
		Text::script('COM_SECURITYCHECKPRO_FILEMANAGER_ACTIVE_TASK');
		Text::script('COM_SECURITYCHECKPRO_FILEMANAGER_TASK_FAILURE');
		Text::script('COM_SECURITYCHECKPRO_FILEMANAGER_FAILURE');
		Text::script('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR');

		// No es necesario pasar "true" a Text::_ porque addscriptoptions ya json-escapa el contenido
		$document->addScriptOptions('securitycheckpro.Common.loadinggif', '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        $view->document = $document;
        $view->display();		
    }
}