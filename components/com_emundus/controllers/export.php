<?php
/**
 * @version         $Id: export.php 750 2020-05-05 22:29:38Z brivalland $
 * @package         Joomla
 * @copyright   (C) 2020 eMundus LLC. All rights reserved.
 * @license         GNU General Public License
 */

// ensure this file is being included by a parent file
defined('_JEXEC') or die(JText::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/export.php');

//client api for file conversion

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Custom report controller
 * @package     Emundus
 */
class EmundusControllerExport extends BaseController
{
	protected $app;

	private $_user;

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
		parent::__construct($config);

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();
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
			$default = 'application_form';
			$this->input->set('view', $default);
		}
		parent::display();
	}

	public function getprofiles()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			die(JText::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		} else {
			$code = $this->input->getVar('code', null);
			$camp = $this->input->getVar('camp', null);

			$code = explode(',', $code);
			$camp = explode(',', $camp);

			require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
			$m_profile = $this->getModel('Profile');
			$profiles  = $m_profile->getProfileIDByCampaigns($camp, $code);

			echo json_encode((object) $profiles);
			exit();
		}
	}
}
