<?php
/**
 * @package    Joomla
 * @subpackage emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * Error View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewError extends HtmlView
{
	protected $error_message;
	protected $error_code;

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$session = $app->getSession();
		$this->error_message = $session->get('error');
		$this->error_code = $session->get('errorcode');
		$session->clear('error');
		$session->clear('errorcode');

		parent::display();
	}
}