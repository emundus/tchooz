<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

/**
 * The Falang Tasker manages the general tasks within the Falang admin interface
 *
 */
class HelpController extends BaseController {

    protected $default_view = 'help';

	/**
	 *
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'show',  'display' );
		$this->registerTask('postInstall', 'postInstall');
		$this->registerTask('information', 'information');
	}

	/**
	 * Standard display control structure
     * @update 5.0 use default fiew name
	 * 
	 */
	function display($cachable = false, $urlparams = array())
	{
		parent::display();
	}
	
	function cancel()
	{
		$this->setRedirect( 'index.php?option=com_falang' );
	}
	
	function postinstall() {
		// get the view
		$this->view =  $this->getView("help");

		// Set the layout
		$this->view->setLayout('postinstall');
		$this->view->display();
	}
	
	function information() {
		// get the view
		$this->view =  $this->getView("help");

		// Set the layout
		$this->view->setLayout('information');
		$this->view->display();
	}
}

