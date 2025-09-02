<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Tchooz\Exception\EmundusException;

/**
 * HTML Users View class for the Emundus Component
 * @package    Emundus
 */
class EmundusViewUsers extends HtmlView
{
	protected $_user;
	protected $_db;

	protected $filts_details = null;
	protected $user = null;
	protected $users = [];
	protected $pagination = [];
	protected $lists = [];
	protected $code = null;
	protected $fnum_assoc = null;
	protected $filters = null;
	protected $uGroups = null;
	protected $juGroups = null;
	protected $uCamps = null;
	protected $uOprofiles = null;
	protected $app_prof = null;
	protected $edit = null;
	protected $profiles = null;
	protected $groups = null;
	protected $jgroups = null;
	protected $campaigns = null;
	protected $universities = null;
	protected $ldapElements = null;
	protected $haveExternalAuth = false;
	protected $actions = null;
	protected $progs = null;
	protected $items = null;
	protected $display = null;

	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'javascript.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

		$app = Factory::getApplication();

		$this->_user = $app->getIdentity();
		$this->_db   = Factory::getContainer()->get('DatabaseDriver');

		$menu         = $app->getMenu();
		$current_menu = $menu->getActive();
		$menu_params  = $menu->getParams($current_menu->id);

		// Pre-filters
		$filts_names  = explode(',', $menu_params->get('em_filters_names'));
		$filts_values = explode(',', $menu_params->get('em_filters_values'));

		foreach ($filts_names as $key => $filt_name) {
			if (array_key_exists($key, $filts_values) && !empty($filts_values[$key])) {
				$this->filts_details[$filt_name] = explode('|', $filts_values[$key]);
			}
			else {
				$this->filts_details[$filt_name] = null;
			}
		}
	}

	private function _loadData()
	{
		$app = Factory::getApplication();
		$m_users                = new EmundusModelUsers();
		$m_users->filts_details = $this->filts_details;
		$users                  = $m_users->getUsers();

		$applicant_profiles = $m_users->getApplicantProfiles();
		$applicant_profiles = array_column($applicant_profiles, 'id');

		foreach ($users as $user) {
			if (!empty($user->o_profiles)) {
				$o_profiles       = explode(',', $user->o_profiles);
				$profile_details  = $m_users->getProfilesByIDs($o_profiles);
				$user->o_profiles = array_map((function ($a) use ($applicant_profiles, $profile_details) {
					if (in_array($a, $applicant_profiles)) {
						return Text::_('COM_EMUNDUS_APPLICANT');
					}
					else {
						return $profile_details[$a]->label;
					}
				}), $o_profiles);
				$user->o_profiles = array_unique($user->o_profiles);
				$user->o_profiles = implode('<br>', $user->o_profiles);
			}

			if ($user->is_anonym)
			{
				$user->username =  Text::_('COM_EMUNDUS_ANONYM_ACCOUNT');
				$user->email = Text::_('COM_EMUNDUS_ANONYM_ACCOUNT');
				$user->lastname = '';
				$user->firstname = '';
			}
		}

		$this->users      = $users;
		$this->pagination       = $m_users->getPagination();
		$this->pageNavigation = $m_users->getPageNavigation();

		$lists['order_dir'] = $app->getSession()->get('filter_order_Dir');
		$lists['order']     = $app->getSession()->get('filter_order');
		$this->lists        = $lists;
	}

	private function _loadFilter()
	{
		$m_users = new EmundusModelUsers();
		$model   = new EmundusModelFiles;

		$model->code       = $m_users->getUserGroupsProgrammeAssoc($this->_user->id);
		$model->fnum_assoc = $m_users->getApplicantsAssoc($this->_user->id);
		$this->code        = $model->code;
		$this->fnum_assoc  = $model->fnum_assoc;

		$this->filters = EmundusHelperFiles::resetFilter();
	}

	private function _loadUserForm()
	{
		$app = Factory::getApplication();
		$m_users = new EmundusModelUsers();
		$input = $app->getInput();
		$edit    = $input->getInt('edit', null);

		include_once(JPATH_BASE . '/components/com_emundus/models/profile.php');
		$m_profiles = new EmundusModelProfile;
		$app_prof   = $m_profiles->getApplicantsProfilesArray();

		$eMConfig = ComponentHelper::getParams('com_emundus');

		if ($edit == 1) {
			$uid  = $input->getInt('user', null);
			$user = $m_users->getUserInfos($uid);

			$uGroups = $m_users->getUserGroups($uid);
			if ($eMConfig->get('showJoomlagroups', 0)) {
				$juGroups = $m_users->getUsersIntranetGroups($uid);
			}
			$uCamps     = $m_users->getUserCampaigns($uid);
			$uOprofiles = $m_users->getUserOprofiles($uid);

			$this->user    = $user;
			$this->uGroups = $uGroups;
			if ($eMConfig->get('showJoomlagroups', 0)) {
				$this->juGroups = $juGroups;
			}
			$this->uCamps     = $uCamps;
			$this->uOprofiles = $uOprofiles;
			$this->app_prof   = $app_prof;
		}
		$this->edit = $edit;

		if (!empty($this->filts_details['profile_users'])) {
			$this->profiles = $m_users->getProfilesByIDs($this->filts_details['profile_users']);
		}
		else {
			$this->profiles = $m_users->getProfiles();
		}

		$this->groups = $m_users->getGroups();

		if ($eMConfig->get('showJoomlagroups', 0)) {
			$this->jgroups = $m_users->getLascalaIntranetGroups();
		}

		$this->campaigns    = $m_users->getAllCampaigns();
		$this->universities = $m_users->getUniversities();

		// Get the LDAP elements.
		$params             = ComponentHelper::getParams('com_emundus');
		$this->ldapElements = $params->get('ldapElements');

		// Check if we have external authentication
		$emundusOauth2 = PluginHelper::getPlugin('authentication','emundus_oauth2');
		$ldap = PluginHelper::getPlugin('authentication','ldap');
		$saml = PluginHelper::getPlugin('authentication','miniorangesaml');

		$this->samlConfig = [];

		if(!empty($ldap)) {
			$this->haveExternalAuth = true;
		}
		elseif(!empty($saml)) {
			require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'settings.php');
			$m_settings = new EmundusModelSettings();

			$this->samlConfig = $m_settings->getSAMLSettings();
			if(!empty($this->samlConfig)) {
				$this->haveExternalAuth = true;
			}
		}
		elseif(!empty($emundusOauth2))
		{
			$oauth2Config = json_decode($emundusOauth2->params);

			if(!empty($oauth2Config->configurations)) {
				foreach ($oauth2Config->configurations as $config) {
					if(in_array($config->display_on_login,[1,3,4])) {
						$this->haveExternalAuth = true;
						break;
					}
				}
			}
		}
	}

	private function _loadGroupForm()
	{
		$m_users       = new EmundusModelUsers();
		$this->actions = $m_users->getActions();
		$this->progs   = $m_users->getProgramme();
	}

	private function _loadAffectForm()
	{
		$m_users      = new EmundusModelUsers();
		$this->groups = $m_users->getGroups();
	}

	private function _loadAffectIntranetForm()
	{
		$m_users = new EmundusModelUsers();
		$groups  = $m_users->getLascalaIntranetGroups();
	}

	private function _loadRightsForm()
	{
		$m_users = new EmundusModelUsers();
		$uid     = Factory::getApplication()->getInput()->getInt('user', null);
		$groups  = $m_users->getUserGroups($uid);

		$g = array();
		foreach ($groups as $key => $label) {
			$g[$key]['label'] = $label;
			$g[$key]['progs'] = $m_users->getGroupProgs($key);
			$g[$key]['acl']   = $m_users->getGroupsAcl($key);
		}

		$this->groups = $g;
	}

	function display($tpl = null)
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			throw new EmundusException(Text::_('JERROR_ALERTNOAUTHOR'), 403, null, false, false);
		}

		$app = Factory::getApplication();
		$layout  = $app->getInput()->getString('layout', null);
		$m_files = new EmundusModelFiles();

		switch ($layout) {
			case 'user':
				if (!EmundusHelperAccess::asAccessAction(12,'r',$this->_user->id)) {
					throw new EmundusException(Text::_('JERROR_ALERTNOAUTHOR'), 403, null, true, false, 'raw');
				}

				$this->_loadData();
				break;
			case 'filter':
				if (EmundusHelperAccess::asAccessAction(12,'r',$this->_user->id)) {
					$this->_loadFilter();
				}
				break;
			case 'adduser':
				if (!EmundusHelperAccess::asAccessAction(12,'c',$this->_user->id) && !EmundusHelperAccess::asAccessAction(12,'u',$this->_user->id)) {
					throw new EmundusException(Text::_('JERROR_ALERTNOAUTHOR'), 403, null, true, false, 'raw');
				}

				$this->_loadUserForm();
				break;
			case 'addgroup':
				if (!EmundusHelperAccess::asAccessAction(19,'c',$this->_user->id)) {
					throw new EmundusException(Text::_('JERROR_ALERTNOAUTHOR'), 403, null, false, false, 'raw');
				}

				$this->_loadGroupForm();
				break;
			case 'affectintranetlascala':
			case 'removeintranetlascala':
				$this->_loadAffectIntranetForm();
				break;
			case 'affectgroup':
				if (!EmundusHelperAccess::asAccessAction(12,'u',$this->_user->id)) {
					throw new EmundusException(Text::_('JERROR_ALERTNOAUTHOR'), 403, null, true, false, 'raw');
				}

				$this->_loadAffectForm();
				break;
			case 'showrights':
				if (!EmundusHelperAccess::asAccessAction(12,'r',$this->_user->id)) {
					throw new EmundusException(Text::_('JERROR_ALERTNOAUTHOR'), 403, null, true, false, 'raw');
				}

				$this->_loadRightsForm();
				break;
			case 'menuactions':
				$display      = $app->getInput()->getString('display', 'none');
				$menu         = $app->getMenu();
				$current_menu = $menu->getActive();
				$params       = $menu->getParams($current_menu->id);

				$items   = EmundusHelperFiles::getMenuList($params);
				$actions = $m_files->getAllActions();

				$menuActions = array();
				foreach ($items as $item) {
					if (!empty($item->note)) {
						$note = explode('|', $item->note);
						if ($actions[$note[0]][$note[1]] == 1) {
							$actions[$note[0]]['multi'] = $note[2];
							$actions[$note[0]]['grud']  = $note[1];
							$item->action               = $actions[$note[0]];
							$menuActions[]              = $item;
						}
					}
					else {
						$menuActions[] = $item;
					}
				}

				$this->items   = $menuActions;
				$this->display = $display;
				break;
		}

		$this->onSubmitForm = EmundusHelperJavascript::onSubmitForm();
		$this->itemId       = $app->getInput()->getInt('Itemid', null);

		parent::display($tpl);
	}

}
