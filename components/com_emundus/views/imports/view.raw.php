<?php
/**
 * @package    eMundus
 * @subpackage Components
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

use Joomla\CMS\Factory;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewImports extends JViewLegacy
{
	private $_user;
	private $_app;

	function __construct($config = array())
	{
		$this->_app  = Factory::getApplication();
		$this->_user = $this->_app->getIdentity();
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			die(JText::_('ACCESS_DENIED'));
		}
		parent::__construct($config);
	}

	function display($tpl = null)
	{
		parent::display($tpl);
	}
}