<?php
/**
 * @package    Joomla
 * @subpackage eMundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;


/**
 * users Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      2.0.0
 */
class EmundusControllerUser extends JControllerLegacy
{
	protected $app;

	private $_user;
	private $m_user;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		require_once(JPATH_COMPONENT . DS . 'models' . DS . 'user.php');

		$this->app    = Factory::getApplication();
		$this->_user  = $this->app->getSession()->get('emundusUser');
		$this->m_user = $this->getModel('User');

		parent::__construct($config);
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @return  DisplayController  This object to support chaining.
	 *
	 * @since   1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view')) {
			$default = 'user';
			$this->input->set('view', $default);
		}

		if ($this->_user->guest == 0)
			parent::display();
		else
			echo JText::_('ACCESS_DENIED');
	}

	public function redirectMeWithMessage()
	{
		$input   = $this->input;
		$message = $input->getString('message', null);

		$this->setRedirect('/', $message);
	}

	public function getpasswordsecurity()
	{
		$result  = array('rules' => [], 'message' => '');
		$uConfig = JComponentHelper::getParams('com_users');

		$result['rules']['minimum_length']    = $uConfig->get('minimum_length', 0);
		$result['rules']['minimum_integers']  = $uConfig->get('minimum_integers', 0);
		$result['rules']['minimum_symbols']   = $uConfig->get('minimum_symbols', 0);
		$result['rules']['minimum_uppercase'] = $uConfig->get('minimum_uppercase', 0);
		$result['rules']['minimum_lowercase'] = $uConfig->get('minimum_lowercase', 0);

		echo json_encode($result);
		exit;
	}
}
