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
use Joomla\CMS\Language\Text;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewImports extends JViewLegacy
{
	private $_user;

	function __construct($config = array())
	{
		$this->_user = Factory::getApplication()->getIdentity();
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		parent::__construct($config);
	}

	function display($tpl = null)
	{
		parent::display($tpl);
	}
}