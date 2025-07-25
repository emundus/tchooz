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

use Joomla\CMS\Event\MultiFactor\NotifyActionLog;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Users\Administrator\Helper\Mfa as MfaHelper;
use Joomla\Component\Users\Administrator\Model\MethodsModel;

/**
 * Emundus Component Users Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      2.0.0
 */
class EmundusControllerUsers extends BaseController
{
	/**
	 * Emundus user session
	 *
	 * @var mixed
	 * @since version 1.0.0
	 */
	private $euser;

	/**
	 * Joomla user
	 *
	 * @var User|JUser|mixed|null
	 * @since version 1.0.0
	 */
	private ?User $user;

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

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/helpers/filters.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/helpers/files.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/helpers/access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/helpers/date.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/models/users.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/models/logs.php');
		require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');

		$this->user = $this->app->getIdentity();
		$session    = $this->app->getSession();

		$this->euser = $session->get('emundusUser');
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *
	 * @return  EmundusControllerUsers  This object to support chaining.
	 *
	 * @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @since      1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		if (!$this->input->get('view'))
		{
			$default = 'users';
			$this->input->set('view', $default);
		}

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			parent::display();
		}
		else
		{
			echo Text::_('ACCESS_DENIED');
		}

		return $this;
	}


	public function adduser()
	{
		if (!EmundusHelperAccess::asAccessAction(12, 'c'))
		{
			echo json_encode((object) array('status' => false, 'uid' => $this->user->id, 'msg' => Text::_('ACCESS_DENIED')));
			exit;
		}

		$firstname       = $this->input->getString('firstname');
		$lastname        = $this->input->getString('lastname');
		$username        = $this->input->getString('login');
		$name            = ucfirst($firstname) . ' ' . strtoupper($lastname);
		$email           = $this->input->getString('email');
		$profile         = $this->input->getInt('profile', 0);
		$oprofiles       = $this->input->getString('oprofiles');
		$jgr             = $this->input->getString('jgr');
		$univ_id         = $this->input->getInt('university_id', 0);
		$groups          = $this->input->getString('groups');
		$campaigns       = $this->input->getString('campaigns');
		$news            = $this->input->getInt('newsletter', 0);
		$ldap            = $this->input->getInt('ldap', 0);
		$auth_provider   = $this->input->getInt('auth_provider', 0);
		$testing_account = $this->input->getInt('testing_account', 0);
		$do_not_notify   = $this->input->getInt('do_not_notify', 0);

		$user = clone(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0));

		if (preg_match('/^[0-9a-zA-Z\_\@\+\-\.]+$/', $username) !== 1)
		{
			echo json_encode((object) array('status' => false, 'msg' => Text::_('COM_EMUNDUS_USERS_ERROR_USERNAME_NOT_GOOD')));
			exit;
		}

		require_once JPATH_BASE . '/components/com_emundus/helpers/emails.php';
		$h_emails = new EmundusHelperEmails();
		if (!$h_emails->correctEmail($email))
		{
			echo json_encode((object) array('status' => false, 'msg' => Text::_('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_EMAIL')));
			exit;
		}

		if (empty($profile)) {
			$profile = 1000;
		}

		$user->name     = $name;
		$user->username = $username;
		$user->email    = $email;

		if ($ldap == 0)
		{
			// If we are creating a new user from the LDAP system, he does not have a password.
			include_once(JPATH_SITE . '/components/com_emundus/helpers/users.php');
			$h_users        = new EmundusHelperUsers;
			$password       = $h_users->generateStrongPassword();
			$user->password = UserHelper::hashPassword($password);
		}

		$now                 = EmundusHelperDate::getNow();
		$user->registerDate  = $now;
		$user->lastvisitDate = null;
		$user->groups        = array($jgr);
		$user->block         = 0;
		$user->authProvider  = $auth_provider == 1 ? 'sso' : '';

		$other_param['firstname']    = $firstname;
		$other_param['lastname']     = $lastname;
		$other_param['profile']      = $profile;
		$other_param['em_oprofiles'] = !empty($oprofiles) ? explode(',', $oprofiles) : $oprofiles;
		$other_param['univ_id']      = $univ_id;
		$other_param['em_groups']    = !empty($groups) ? explode(',', $groups) : $groups;
		$other_param['em_campaigns'] = !empty($campaigns) ? explode(',', $campaigns) : $campaigns;
		$other_param['news']         = $news;

		$m_users        = $this->getModel('Users');
		$acl_aro_groups = $m_users->getDefaultGroup($profile);
		$user->groups   = $acl_aro_groups;

		$usertype       = $m_users->found_usertype($acl_aro_groups[0]);
		$user->usertype = $usertype;

		$uid = $m_users->adduser($user, $other_param, $testing_account);

		if (is_array($uid))
		{
			echo json_encode((object) array('status' => false));
			exit;
		}
		else
		{
			if (empty($uid))
			{
				echo json_encode((object) array('status' => false, 'user' => $user, 'msg' => $user->getError()));
				exit;
			}
		}

		$data          = array();
		$data['email'] = $user->email;
		$email_tmpl    = 'new_account';
		if ($auth_provider == 1)
		{
			$email_tmpl = 'new_account_sso';
		}

		if($do_not_notify != 1)
		{
			$m_users->passwordReset($data, '', '', true, $email_tmpl);
		}

		// If index.html does not exist, create the file otherwise the process will stop with the next step
		if (!file_exists(EMUNDUS_PATH_ABS . 'index.html'))
		{
			$filename = EMUNDUS_PATH_ABS . 'index.html';
			$file     = fopen($filename, 'w');
			fwrite($file, '');
			fclose($file);
		}

		$create_repo = mkdir(EMUNDUS_PATH_ABS . $uid, 0755);
		$copy_index  = copy(EMUNDUS_PATH_ABS . 'index.html', EMUNDUS_PATH_ABS . $uid . DS . 'index.html');
		if (!$create_repo || !$copy_index)
		{
			$msg = Text::_('COM_EMUNDUS_USERS_CANT_CREATE_USER_FOLDER_CONTACT_ADMIN');

			if (!$create_repo)
			{
				$msg .= ' ' . Text::_('COM_EMUNDUS_USERS_CANT_CREATE_USER_FOLDER');
			}

			if (!$copy_index)
			{
				$msg .= ' ' . Text::_('COM_EMUNDUS_USERS_CANT_COPY_INDEX');
			}

			echo json_encode((object) array('status' => false, 'uid' => $uid, 'msg' => $msg));
			exit;
		}

		echo json_encode((object) array('status' => true, 'msg' => Text::_('COM_EMUNDUS_USERS_USER_CREATED')));
		exit;
	}

	public function delincomplete()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}

		$query = 'SELECT u.id FROM #__users AS u LEFT JOIN #__emundus_declaration AS d ON u.id=d.user WHERE u.usertype = "Registered" AND d.user IS NULL';
		$this->_db->setQuery($query);
		$this->delusers($this->_db->loadResultArray());
	}

	public function delrefused()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}

		$this->_db->setQuery('SELECT student_id FROM #__emundus_final_grade WHERE Final_grade=2 AND type_grade ="candidature"');
		$this->delusers($this->_db->loadResultArray());
	}

	public function delnonevaluated()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}

		$this->_db->setQuery('SELECT u.id FROM #__users AS u LEFT JOIN #__emundus_final_grade AS efg ON u.id=efg.student_id WHERE u.usertype = "Registered" AND efg.student_id IS NULL');
		$this->delusers($this->_db->loadResultArray());
	}

	/*
	 * todo: why here ?
	 */
	public function lastSavedFilter()
	{
		$query = "SELECT MAX(id) FROM #__emundus_filters";
		$this->_db->setQuery($query);
		$result = $this->_db->loadResult();
		echo $result;
	}

	public function getConstraintsFilter()
	{
		$filter_id = $this->input->get('filter_id', null, 'POST');

		$query = "SELECT constraints FROM #__emundus_filters WHERE id=" . $filter_id;
		$this->_db->setQuery($query);
		echo $this->_db->loadResult();
	}

	public function addsession()
	{
		global $option;
		$select_filter = $this->input->get('select_id', null, 'GET');
		$mainframe     = $this->app;
		$mainframe->setUserState($option . "select_filter", $select_filter);
	}


	/////////////Nouvelle Gestion /////////////////
	public function clear()
	{
		$h_files = new EmundusHelperFiles();
		$h_files->clear();
		echo json_encode((object) (array('status' => true)));
		exit;
	}


	public function setfilters()
	{
		try
		{

			$filterName = $this->input->getString('id', null);
			$elements   = $this->input->getString('elements', null);
			$multi      = $this->input->getString('multi', null);

			@EmundusHelperFiles::clearfilter();

			if ($multi == "true")
			{
				$filterval = $this->input->get('val', array(), 'ARRAY');
			}
			else
			{
				$filterval = $this->input->getString('val', null);
			}

			$session = JFactory::getSession();
			$params  = $session->get('filt_params');

			if ($elements == 'false')
			{
				$params[$filterName] = $filterval;
			}
			else
			{
				$vals = (array) json_decode(stripslashes($filterval));

				if (isset($vals[0]->name))
				{
					foreach ($vals as $val)
					{
						if ($val->adv_fil)
						{
							$params['elements'][$val->name] = $val->value;
						}
						else
						{
							$params[$val->name] = $val->value;
						}
					}
				}
				else $params['elements'][$filterName] = $filterval;
			}
			$session->set('filt_params', $params);

			$session->set('limitstart', 0);
			echo json_encode((object) (array('status' => true)));
			exit();
		}
		catch (Exception $e)
		{
			error_log($e->getMessage(), 0);
			error_log($e->getLine(), 0);
			error_log($e->getTraceAsString(), 0);
			throw new JDatabaseException;
		}
	}

	public function loadfilters()
	{
		try
		{


			$id                      = $this->input->getInt('id', null);
			$filter                  = @EmundusHelperFiles::getEmundusFilters($id);
			$params                  = (array) json_decode($filter->constraints);
			$params['select_filter'] = $id;
			$params                  = json_decode($filter->constraints, true);

			JFactory::getSession()->set('select_filter', $id);
			if (isset($params['filter_order']))
			{
				JFactory::getSession()->set('filter_order', $params['filter_order']);
				JFactory::getSession()->set('filter_order_Dir', $params['filter_order_Dir']);
			}
			JFactory::getSession()->set('filt_params', $params['filter']);

			echo json_encode((object) (array('status' => true)));
			exit();

		}
		catch (Exception $e)
		{
			throw new Exception;
		}
	}

	public function order()
	{

		$order = $this->input->getString('filter_order', null);

		$ancientOrder = JFactory::getSession()->get('filter_order');
		$params       = JFactory::getSession()->get('filt_params');
		JFactory::getSession()->set('filter_order', $order);
		$params['filter_order'] = $order;

		if ($order == $ancientOrder)
		{
			if (JFactory::getSession()->get('filter_order_Dir') == 'desc')
			{
				JFactory::getSession()->set('filter_order_Dir', 'asc');
				$params['filter_order_Dir'] = 'asc';
			}
			else
			{
				JFactory::getSession()->set('filter_order_Dir', 'desc');
				$params['filter_order_Dir'] = 'desc';
			}
		}
		else
		{
			JFactory::getSession()->set('filter_order_Dir', 'asc');
			$params['filter_order_Dir'] = 'asc';
		}
		JFactory::getSession()->set('filt_params', $params);
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function setlimit()
	{

		$limit = $this->input->getInt('limit', null);

		JFactory::getSession()->set('limit', $limit);
		JFactory::getSession()->set('limitstart', 0);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function savefilters()
	{
		$current_user = $this->user;
		$user_id      = $current_user->id;

		$itemid = $this->input->get('Itemid', null, 'GET');
		$name   = $this->input->get('name', null, 'POST');

		$filt_params = JFactory::getSession()->get('filt_params');
		$adv_params  = JFactory::getSession()->get('adv_cols');
		$constraints = array('filter' => $filt_params, 'col' => $adv_params);

		$constraints = json_encode($constraints);

		if (empty($itemid))
		{
			$itemid = $this->input->get('Itemid', null, 'POST');
		}

		$time_date = (date('Y-m-d H:i:s'));

		$query = "INSERT INTO #__emundus_filters (time_date,user,name,constraints,item_id) values('" . $time_date . "'," . $user_id . ",'" . $name . "'," . $this->_db->quote($constraints) . "," . $itemid . ")";
		$this->_db->setQuery($query);

		try
		{

			$this->_db->Query();
			$query = 'select f.id, f.name from #__emundus_filters as f where f.time_date = "' . $time_date . '" and user = ' . $user_id . ' and name="' . $name . '" and item_id="' . $itemid . '"';
			$this->_db->setQuery($query);
			$result = $this->_db->loadObject();
			echo json_encode((object) (array('status' => true, 'filter' => $result)));
			exit;

		}
		catch (Exception $e)
		{
			echo json_encode((object) (array('status' => false)));
			exit;
		}
	}

	public function deletefilters()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->deletefilters();
	}

	public function setlimitstart()
	{

		$limistart  = $this->input->getInt('limitstart', null);
		$limit      = intval(JFactory::getSession()->get('limit'));
		$limitstart = ($limit != 0 ? ($limistart > 1 ? (($limistart - 1) * $limit) : 0) : 0);
		JFactory::getSession()->set('limitstart', $limitstart);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function addgroup()
	{
		$gname   = $this->input->getString('gname', null);
		$actions = $this->input->getString('actions', null);
		$progs   = $this->input->getString('gprog', null);
		$gdesc   = $this->input->getString('gdesc', null);
		$actions = (array) json_decode(stripslashes($actions));

		$m_users = $this->getModel('Users');
		$res     = $m_users->addGroup($gname, $gdesc, $actions, explode(',', $progs));

		if ($res !== false)
		{
			$msg = Text::_('COM_EMUNDUS_GROUPS_GROUP_ADDED');
		}
		else
		{
			$msg = Text::_('COM_EMUNDUS_ERROR_OCCURED');
		}

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}

	public function changeblock()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asAdministratorAccessLevel($user->id) && !EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}


		$users = $this->input->getString('users', null);
		$state = $this->input->getInt('state', null);

		$m_users = $this->getModel('Users');


		if ($users === 'all')
		{

			$us    = $m_users->getUsers(0, 0);
			$users = array();

			foreach ($us as $u)
			{
				$users[] = $u->id;
			}

		}
		else
		{
			$users = (array) json_decode(stripslashes($users));
		}

		$res = $m_users->changeBlock($users, $state);

		if ($res !== false)
		{
			$res = true;
			if (count($users) > 1)
			{
				if ($state === 1)
				{
					$msg = Text::_('COM_EMUNDUS_USERS_BLOCK_ACCOUNT_MULTI');
				}
				else
				{
					$msg = Text::_('COM_EMUNDUS_USERS_UNBLOCK_ACCOUNT_MULTI');
				}
			}
			else
			{
				if ($state === 1)
				{
					$msg = Text::_('COM_EMUNDUS_USERS_BLOCK_ACCOUNT_SINGLE');
				}
				else
				{
					$msg = Text::_('COM_EMUNDUS_USERS_UNBLOCK_ACCOUNT_SINGLE');
				}
			}
		}
		else $msg = Text::_('COM_EMUNDUS_ERROR_OCCURED');

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}

	public function changeactivation()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asAdministratorAccessLevel($user->id) && !EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}


		$users = $this->input->getString('users', null);
		$state = $this->input->getInt('state', null);

		if ($state == 0)
		{
			$state = 1;
		}
		else
		{
			$state = -1;
		}

		$m_users = $this->getModel('Users');


		if ($users === 'all')
		{

			$us    = $m_users->getUsers(0, 0);
			$users = array();

			foreach ($us as $u)
			{
				$users[] = $u->id;
			}

		}
		else
		{
			$users = (array) json_decode(stripslashes($users));
		}

		$res = $m_users->changeActivation($users, $state);

		if ($res !== false)
		{
			$res = true;
			if (count($users) > 1)
			{
				$msg = Text::_('COM_EMUNDUS_USERS_ACTIVATE_ACCOUNT_MULTI');
			}
			else
			{
				$msg = Text::_('COM_EMUNDUS_USERS_ACTIVATE_ACCOUNT_SINGLE');
			}
		}
		else $msg = Text::_('COM_EMUNDUS_ERROR_OCCURED');

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}

	public function affectgroups()
	{

		$users = $this->input->getString('users', null);

		$groups  = $this->input->getString('groups', null);
		$m_users = $this->getModel('Users');

		if ($users === 'all')
		{
			$us    = $m_users->getUsers(0, 0);
			$users = array();
			foreach ($us as $u)
			{
				$users[] = $u->id;
			}
		}
		else
		{
			$users = (array) json_decode(stripslashes($users));
		}

		$users = array_filter($users, function ($user) {
			return $user !== 'em-check-all' && is_numeric($user);
		});

		$users = $m_users->getNonApplicantId($users);
		$res   = $m_users->affectToGroups($users, explode(',', $groups));

		if ($res === true)
		{
			$res = true;
			$msg = Text::_('COM_EMUNDUS_GROUPS_USERS_AFFECTED_SUCCESS');
		}
		elseif ($res === 0)
		{
			$msg = Text::_('COM_EMUNDUS_GROUPS_NO_GROUP_AFFECTED');
		}
		else
		{
			$msg = Text::_('COM_EMUNDUS_ERROR_OCCURED');
		}

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}

	public function edituser()
	{
		$current_user = $this->app->getIdentity();

		if (!EmundusHelperAccess::isAdministrator($current_user->id) && !EmundusHelperAccess::isCoordinator($current_user->id) && !EmundusHelperAccess::asAccessAction(12, 'u') && !EmundusHelperAccess::asAccessAction(20, 'u'))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}

		$newuser['id']               = $this->input->getInt('id', 0);
		$newuser['firstname']        = $this->input->getString('firstname');
		$newuser['lastname']         = $this->input->getString('lastname');
		$newuser['username']         = $this->input->getString('login');
		$newuser['name']             = $newuser['firstname'] . ' ' . $newuser['lastname'];
		$newuser['email']            = $this->input->getString('email');
		$newuser['same_login_email'] = $this->input->getInt('sameLoginEmail', 1);
		$newuser['testing_account']  = $this->input->getInt('testingAccount', 0);
		$newuser['authProvider']     = $this->input->getInt('authProvider', 0);
		$newuser['profile']          = $this->input->getInt('profile', 0);
		$newuser['em_oprofiles']     = $this->input->getString('oprofiles');
		$newuser['groups']           = array($this->input->get('jgr'));
		$newuser['university_id']    = $this->input->getInt('university_id', 0);
		$newuser['em_campaigns']     = $this->input->getString('campaigns');
		$newuser['em_groups']        = $this->input->getString('groups');
		$newuser['news']             = $this->input->getInt('newsletter');

		if (preg_match('/^[0-9a-zA-Z\_\@\-\.\+]+$/', $newuser['username']) !== 1)
		{
			echo json_encode((object) array('status' => false, 'msg' => 'LOGIN_NOT_GOOD'));
			exit;
		}
		if (!filter_var($newuser['email'], FILTER_VALIDATE_EMAIL))
		{
			echo json_encode((object) array('status' => false, 'msg' => Text::_('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_EMAIL')));
			exit;
		}

		$m_users = $this->getModel('Users');
		$res     = $m_users->editUser($newuser);

		if (EmundusHelperAccess::asAccessAction(EmundusHelperAccess::getActionIdFromActionName('edit_user_role'), 'u', $current_user->id) && !empty($newuser['profile']))
		{
			$other_profiles = explode(',', $newuser['em_oprofiles']);
			$user_groups = explode(',', $newuser['em_groups']);
			$edited = $m_users->editUserProfiles((int)$newuser['id'], (int)$newuser['profile'], $other_profiles, $user_groups);

			if ($edited === false)
			{
				$res = false;
			}
		}

		if ($res === true && !is_array($res))
		{
			$res = true;
			$msg = Text::_('COM_EMUNDUS_USERS_EDITED');

			$e_user = $this->app->getSession()->get('emundusUser');
			if ($e_user->id == $newuser['id'])
			{
				$e_user->firstname = $newuser['firstname'];
				$e_user->lastname  = $newuser['lastname'];
				$e_user->name      = $newuser['name'];
				$e_user->email     = $newuser['email'];
				$this->app->getSession()->set('emundusUser', $e_user);
			}
		}
		else
		{
			if (is_array($res))
			{
				$res['status'] = false;
				echo json_encode((object) ($res));
				exit;
			}
			else $msg = Text::_('COM_EMUNDUS_ERROR_OCCURED');
		}

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}


	public function deleteusers()
	{

		if (!EmundusHelperAccess::asAccessAction(12, 'd') && !EmundusHelperAccess::asAccessAction(20, 'd'))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}

		$users = $this->input->getString('users', null);

		$m_users = $this->getModel('Users');
		if ($users === 'all')
		{
			$us = $m_users->getUsers(0, 0);

			$users = array();
			foreach ($us as $u)
			{
				$users[] = $u->id;
			}

		}
		else
		{
			$users = (array) json_decode(stripslashes($users));
		}

		$res      = true;
		$msg      = Text::_('COM_EMUNDUS_USERS_DELETED');
		$users_id = "";
		foreach ($users as $user)
		{
			if (is_numeric($user))
			{
				$u     = User::getInstance($user);
				$count = $m_users->countUserEvaluations($user);
				$count += $m_users->countUserDecisions($user);

				if ($count > 0)
				{
					/** user disactivation */
					$m_users->changeBlock(array($user), 1);
					$users_id .= $user . " ,";
					$res      = false;
				}
				else
				{
					$u->delete();
					EmundusModelLogs::log($this->user->id, $user, null, 20, 'd', 'COM_EMUNDUS_ADD_USER_DELETE');
				}
			}
		}

		if ($users_id != "")
		{
			$msg = Text::sprintf('COM_EMUNDUS_USERS_THIS_USER_CAN_NOT_BE_DELETED', $users_id);
		}
		echo json_encode((object) array('status' => $res, 'msg' => $msg));

		exit;
	}

	// Edit actions rights for group
	public function setgrouprights()
	{
		$current_user = $this->user;
		$msg          = '';

		if (!EmundusHelperAccess::isAdministrator($current_user->id) && !EmundusHelperAccess::isCoordinator($current_user->id) && !EmundusHelperAccess::isPartner($current_user->id))
		{
			$msg = Text::_('ACCESS_DENIED');
			echo json_encode((object) array('status' => false, 'msg' => $msg));
			exit;
		}

		$id     = $this->input->getInt('id', null);
		$action = $this->input->get('action', null, 'WORD');
		$value  = $this->input->getInt('value', '');

		$m_users = $this->getModel('Users');
		$res     = $m_users->setGroupRight($id, $action, $value);

		try
		{
			require_once(JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');
			EmundusHelperUpdate::clearJoomlaCache('mod_menu');
		}
		catch (Exception $e)
		{
			JLog::add('Cannot clear cache : ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
		}


		echo json_encode((object) array('status' => $res, 'msg' => $msg));
		exit;
	}

	/**
	 * Search the LDAP for a user to add.
	 */
	public function ldapsearch()
	{

		if (!EmundusHelperAccess::asAccessAction(12, 'c'))
		{
			echo json_encode((object) array('status' => false));
			exit;
		}

		$m_users = $this->getModel('Users');

		$search = $this->input->getString('search', null);

		$return = $m_users->searchLDAP($search);

		// If no users are found :O or the LDAP is broken
		if (!$return)
		{
			echo json_encode((object) ['status' => false, 'msg' => 'Failed to connect to the ldap']);
			exit;
		}

		// Iterate through all of the LDAP search results and check if they exist already.
		$users = [];
		if (is_array($return->users))
		{
			foreach ($return->users as $user)
			{

				// TODO: Implement getting the user photo.
				if (!empty($user['jpegPhoto']))
				{
					$user['jpegPhoto'] = null;
				}

				// Certain users have a binary certificate file which breaks the JSON parsing as it is not UTF-8.
				if (!empty($user['userCertificate;binary']))
				{
					$user["userCertificate;binary"] = null;
				}

				if (JUserHelper::getUserId($user['uid'][0]) > 0)
				{
					$user['exists'] = true;
				}
				else
				{
					$user['exists'] = false;
				}

				$users[] = $user;
			}
		}

		$response = json_encode((object) ['status' => $return->status, 'ldapUsers' => $users, 'count' => count($users)]);
		if (!$response)
		{
			echo json_encode((object) ['status' => false, 'msg' => 'Information retrieved from LDAP is of incorrect format.']);
			exit;
		}

		echo $response;
		exit;
	}

	/**
	 * Method to request a password reset. Taken from Joomla and adapted for eMundus.
	 *
	 * @return  boolean
	 *
	 * @throws Exception
	 * @since   3.9.11
	 */
	public function passrequest()
	{

		$m_users  = $this->getModel('Users');
		$response = array('status' => true, 'msg' => '');

		// Check the request token.
		if ($this->app->getIdentity()->guest)
		{
			$this->checkToken('post');

			$data = $this->input->post->get('jform', array(), 'array');

			$return = $m_users->passwordReset($data);

			// Check for a hard error.
			if ($return->status === false)
			{
				// The request failed.
				// Go back to the request form.
				$message = Text::sprintf('COM_USERS_RESET_REQUEST_FAILED', $return->message);
				$menu    = $this->app->getMenu()->getItems('link', 'index.php?option=com_users&view=reset', true);
				if (!empty($menu))
				{
					$this->setRedirect($menu->alias, $message, 'notice');
				}
				else
				{
					$this->setRedirect(Route('index.php?option=com_users&view=reset&layout=confirm'), $message, 'notice');
				}

			}
			else
			{
				// The request succeeded.
				// Proceed to step two.
				$this->setRedirect(JRoute::_('index.php?option=com_users&view=reset&layout=confirm'));
			}
		}
		elseif (EmundusHelperAccess::asAccessAction(12, 'u') || EmundusHelperAccess::asAccessAction(20, 'u'))
		{
			$response['msg'] = Text::_('COM_EMUNDUS_USERS_RESET_REQUEST_LINK_SENDED');
			$users           = $this->input->post->getString('users', null);
			if ($users === 'all')
			{
				$us = $m_users->getUsers(0, 0);

				$users = array();
				foreach ($us as $u)
				{
					$users[] = $u->id;
				}
			}
			else
			{
				$users = (array) json_decode(stripslashes($users));
			}

			foreach ($users as $user)
			{
				$data          = array();
				$data['email'] = Factory::getUser($user)->email;

				$return = $m_users->passwordReset($data, 'COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT_FOR_OTHER', 'COM_USERS_EMAIL_PASSWORD_RESET_BODY_FOR_OTHER');
				if ($return->status === false)
				{
					$response['status'] = false;
					$response['msg']    = $return->msg;
				}
			}
		}
		else
		{
			$response['status'] = false;
			$response['msg']    = Text::_('ACCESS_DENIED');
		}

		if (!JFactory::getUser()->guest)
		{
			echo json_encode($response);
			exit;
		}
	}

	public function getuserbyid()
	{
		$response     = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));
		$current_user = $this->app->getIdentity()->id;

		$id = $this->input->getInt('id', $current_user);
		if (!empty($id))
		{
			if ($id == $current_user || EmundusHelperAccess::asPartnerAccessLevel($current_user))
			{
				$m_users = $this->getModel('Users');
				$users   = $m_users->getUserById($id);

				if (!empty($users))
				{
					foreach ($users as $key => $user)
					{
						if (isset($user->password))
						{
							unset($user->password);
							$users[$key] = $user;
						}
					}

					$response['user']   = $users;
					$response['status'] = true;
					$response['msg']    = Text::_('SUCCESS');
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getUserNameById()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		$id = $this->input->getInt('id', $this->user->id);

		if (!empty($id))
		{
			if ($id !== $this->user->id)
			{
				if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
				{
					$id = $this->user->id;
				}
			}

			$m_users  = $this->getModel('Users');
			$username = $m_users->getUserNameById($id);

			if (!empty($username))
			{
				$response['user']   = $username;
				$response['status'] = true;
				$response['msg']    = Text::_('SUCCESS');
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getattachmentaccessrights()
	{
		$rights = array();

		$fnum = $this->input->getString('fnum', null);

		$rights['canCreate'] = EmundusHelperAccess::asAccessAction(4, 'c', $this->user->id, $fnum);
		$rights['canDelete'] = EmundusHelperAccess::asAccessAction(4, 'd', $this->user->id, $fnum);
		$rights['canUpdate'] = EmundusHelperAccess::asAccessAction(4, 'u', $this->user->id, $fnum);
		$rights['canExport'] = EmundusHelperAccess::asAccessAction(8, 'c', $this->user->id, $fnum);

		echo json_encode(array('status' => true, 'rights' => $rights));
		exit;
	}

	public function getprofileform()
	{
		$m_users = $this->getModel('Users');
		$form    = $m_users->getProfileForm();

		echo json_encode(array('status' => true, 'form' => $form));
		exit;
	}

	public function getprofilegroups()
	{
		$formid = $this->input->getInt('formid', null);
		if (!empty($formid))
		{
			$m_users = $this->getModel('Users');
			$groups  = $m_users->getProfileGroups($formid);
		}
		else
		{
			$groups = [];
		}

		echo json_encode(array('status' => true, 'groups' => $groups));
		exit;
	}

	public function getprofileelements()
	{
		$groupid = $this->input->getInt('groupid', null);
		if (!empty($groupid))
		{
			$m_users  = $this->getModel('Users');
			$elements = $m_users->getProfileElements($groupid);
		}
		else
		{
			$elements = [];
		}

		echo json_encode(array('status' => true, 'elements' => $elements));
		exit;
	}

	public function getprofileattachments()
	{
		$m_users     = $this->getModel('Users');
		$attachments = $m_users->getProfileAttachments($this->app->getIdentity()->id);

		echo json_encode(array('status' => true, 'attachments' => $attachments));
		exit;
	}

	public function getprofileattachmentsallowed()
	{
		$m_users     = $this->getModel('Users');
		$attachments = $m_users->getProfileAttachmentsAllowed();

		echo json_encode(array('status' => true, 'attachments' => $attachments));
		exit;
	}

	public function uploaddefaultattachment()
	{
		$user = $this->user;


		$file             = $this->input->files->get('file');
		$attachment_id    = $this->input->getInt('attachment_id');
		$attachment_label = $this->input->getString('attachment_lbl');

		if (isset($file))
		{
			$root_dir   = "images/emundus/files/" . $user->id;
			$target_dir = $root_dir . '/default_attachments/';

			$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

			if (!file_exists($target_dir))
			{
				mkdir($target_dir);
			}

			$target_file = $target_dir . basename($user->id . '-' . $attachment_id . '-' . strtolower(substr($attachment_label, 1)) . '-' . time() . '.' . $ext);

			if (move_uploaded_file($file["tmp_name"], $target_file))
			{
				$m_users  = $this->getModel('Users');
				$uploaded = $m_users->addDefaultAttachment($user->id, $attachment_id, $target_file);

				$result = array('status' => $uploaded);
			}
			else
			{
				$result = array('status' => false);
			}
		}
		else
		{
			$result = array('status' => false);
		}
		echo json_encode((object) $result);
		exit;
	}

	public function deleteprofileattachment()
	{
		$user = $this->user;


		$id       = $this->input->getInt('id', null);
		$filename = $this->input->getString('filename');

		if (!empty($id))
		{
			$m_users = $this->getModel('Users');
			$deleted = $m_users->deleteProfileAttachment($id, $user->id);

			if ($deleted && !empty($filename))
			{
				unlink(JPATH_SITE . DS . $filename);
			}
		}
		else
		{
			$deleted = false;
		}

		echo json_encode(array('status' => true, 'deleted' => $deleted));
		exit;
	}

	public function uploadprofileattachmenttofile()
	{

		$aids = $this->input->getString('aids');

		$current_user = $this->user;

		if (!empty($aids))
		{
			$m_users = $this->getModel('Users');
			$copied  = $m_users->uploadProfileAttachmentToFile($this->euser->fnum, $aids, $current_user->id);
		}
		else
		{
			$copied = false;
		}

		echo json_encode(array('status' => true, 'copied' => $copied));
		exit;
	}

	public function uploadfileattachmenttoprofile()
	{

		$aid = $this->input->getInt('aid');

		$current_user = $this->user;

		if (!empty($aid))
		{
			$m_users = $this->getModel('Users');
			$copied  = $m_users->uploadFileAttachmentToProfile($this->euser->fnum, $aid, $current_user->id);
		}
		else
		{
			$copied = false;
		}

		echo json_encode(array('status' => true, 'copied' => $copied));
		exit;
	}

	public function updateprofilepicture()
	{
		$user = $this->user;


		$file = $this->input->files->get('file');

		if (isset($file))
		{
			$root_dir   = "images/emundus/files/" . $user->id;
			$target_dir = $root_dir . '/profile/';
			if (!file_exists($root_dir))
			{
				mkdir($root_dir);
			}
			if (!file_exists($target_dir))
			{
				mkdir($target_dir);
			}

			$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

			$target_file = $target_dir . basename('profile.' . $ext);

			if (move_uploaded_file($file["tmp_name"], $target_file))
			{
				$m_users  = $this->getModel('Users');
				$uploaded = $m_users->updateProfilePicture($user->id, $target_file);

				$result = array('status' => $uploaded, 'profile_picture' => $target_file);
			}
			else
			{
				$result = array('status' => false);
			}
		}
		else
		{
			$result = array('status' => false);
		}
		echo json_encode((object) $result);
		exit;
	}


	public function activation()
	{
		$m_user = $this->getModel('User');

		$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

		if (!empty($email))
		{
			$user = $this->app->getIdentity();
			$uid  = $user->id;

			if (!empty($uid))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true);

				// check user is not already activated
				$query->select('activation')
					->from('#__users')
					->where('id = ' . $uid);

				try
				{
					$db->setQuery($query);
					$activation = $db->loadResult();
				}
				catch (Exception $e)
				{
					JLog::add('Error checking if user is already activated or not : ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
					echo json_encode((object) (array('status' => false, 'msg' => Text::_('COM_EMUNDUS_FAILED_TO_CHECK_ACTIVATION'))));
					exit();
				}

				if ($activation == '-1')
				{
					$query->clear()
						->select('count(id)')
						->from($db->quoteName('#__users'))
						->where($db->quoteName('email') . ' LIKE ' . $db->quote($email))
						->andWhere($db->quoteName('id') . ' <> ' . $db->quote($uid));
					$db->setQuery($query);

					try
					{
						$email_alreay_use = $db->loadResult();
					}
					catch (Exception $e)
					{
						JLog::add('Error getting email already use: ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
						echo json_encode((object) (array('status' => false, 'msg' => Text::_('COM_EMUNDUS_MAIL_ERROR_TRYING_TO_GET_EMAIL_ALREADY_USE'))));
						exit();
					}

					if (!$email_alreay_use)
					{
						$query->clear()
							->select($db->quoteName('params'))
							->from($db->quoteName('#__users'))
							->where($db->quoteName('id') . ' = ' . $db->quote($uid));
						$db->setQuery($query);
						$result = $db->loadObject();

						$token = json_decode($result->params);
						$token = $token->emailactivation_token;

						$emailSent = $m_user->sendActivationEmail($user->getProperties(), $token, $email);

						if ($user->email != $email)
						{
							$m_user->updateEmailUser($user->id, $email);
						}
						if ($emailSent)
						{
							echo json_encode((object) (array('status' => true, 'msg' => Text::_('COM_EMUNDUS_MAIL_SUCCESSFULLY_SENT'))));
							exit();
						}
						else
						{
							echo json_encode((object) (array('status' => false, 'msg' => Text::_('COM_EMUNDUS_MAIL_ERROR_AT_SEND'))));
							exit();
						}
					}
					else
					{
						echo json_encode((object) (array('status' => false, 'msg' => Text::_('COM_EMUNDUS_MAIL_ALREADY_USE'))));
						exit();
					}
				}
				else
				{
					echo json_encode((object) (array('status' => false, 'msg' => Text::_('COM_EMUNDUS_ALREADY_ACTIVATED_USER'))));
					exit();
				}
			}
			else
			{
				echo json_encode((object) (array('status' => false, 'msg' => Text::_('EMPTY_CURRENT_USER'))));
				exit();
			}
		}
		else
		{
			echo json_encode((object) (array('status' => false, 'msg' => Text::_('INVALID_EMAIL'))));
			exit();
		}
	}

	public function updateemundussession()
	{

		$param = $this->input->getString('param', null);
		$value = $this->input->getBool('value', null);

		$session   = JFactory::getSession();
		$e_session = $session->get('emundusUser');

		$e_session->{$param} = $value;
		$session->set('emundusUser', $e_session);

		echo json_encode(array('status' => true));
		exit;
	}

	public function addapplicantprofile()
	{
		$user = $this->user;

		$session   = JFactory::getSession();
		$e_session = $session->get('emundusUser');

		$already_applicant = false;
		foreach ($e_session->emProfiles as $profile)
		{
			if ($profile->published == 1)
			{
				$already_applicant = true;
				$app_profile       = $profile;
				break;
			}
		}

		if (!$already_applicant)
		{
			$m_users     = $this->getModel('Users');
			$app_profile = $m_users->addApplicantProfile($user->id);

			$e_session->profile      = $app_profile->id;
			$e_session->emProfiles[] = $app_profile;
			$e_session->menutype     = null;
			$e_session->first_logged = true;
			$session->set('emundusUser', $e_session);
		}
		else
		{
			$e_session->profile  = $app_profile->id;
			$e_session->menutype = null;
			$session->set('emundusUser', $e_session);
		}

		echo json_encode(array('status' => true));
		exit;
	}

	public function affectjoomlagroups()
	{
		$response = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));


		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$params = $this->input->getArray();
			$users  = json_decode($params['users'], true);
			$groups = explode(',', $params['groups']);

			if (!empty($users) && !empty($groups))
			{
				$m_users  = $this->getModel('Users');
				$affected = $m_users->affectToJoomlaGroups($users, $groups);
			}
			else
			{
				$affected = false;
			}

			$response = array('status' => $affected, 'msg' => Text::_("GROUPS_AFFECTED"));
		}

		echo json_encode($response);
		exit;
	}

	public function removejoomlagroups()
	{
		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$params = $this->input->getArray();
			$users  = json_decode($params['users'], true);
			$groups = explode(',', $params['groups']);

			if (!empty($users) && !empty($groups))
			{
				$m_users = $this->getModel('Users');
				$removed = $m_users->removeJoomlaGroups($users, $groups);
			}
			else
			{
				$removed = false;
			}

			$tab = array('status' => $removed, 'msg' => Text::_("GROUPS_REMOVED"));
		}
		else
		{
			$tab = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));
		}

		echo json_encode($tab);
		exit;
	}


	public function activation_anonym_user()
	{
		$app = $this->app;

		$user_id = $this->input->getInt('user_id', 0);
		$token   = $this->input->getString('token', '');

		if (!empty($token) && !empty($user_id))
		{
			$m_users = $this->getModel('Users');
			$valid   = $m_users->checkTokenCorrespondToUser($token, $user_id);

			if ($valid)
			{
				$updated = $m_users->updateAnonymUserAccount($token, $user_id);

				if ($updated)
				{
					$app->enqueueMessage(Text::_('COM_EMUNDUS_USERS_ANONYM_USER_ACTIVATION_SUCCESS'), 'success');
				}
				else
				{
					$app->enqueueMessage(Text::_('COM_EMUNDUS_USERS_FAILED_TO_ACTIVATE_USER'), 'warning');
				}
				$app->redirect('/');
			}
			else
			{
				JLog::add("WARNING! Wrong paramters together, token $token and user_id $user_id from" . $_SERVER['REMOTE_ADDR'], JLog::WARNING, 'com_emundus.error');
			}
		}
		else
		{
			JLog::add('WARNING! Attempt to activate anonym user without necessary parameters from ' . $_SERVER['REMOTE_ADDR'], JLog::WARNING, 'com_emundus.error');
		}
	}

	public function getCurrentUser()
	{
		$currentUser = $this->user;

		if (!EmundusHelperAccess::asPartnerAccessLevel($currentUser->id))
		{
			return false;
		}

		echo json_encode($currentUser);
		exit;
	}

	function getcurrentprofile()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];
		$user     = $this->app->getIdentity();

		if (!$user->guest)
		{
			$em_users = $this->app->getSession()->get('emundusUser');
			$m_users  = $this->getModel('Users');

			if (!empty($em_users->profile))
			{
				$response['data']   = $m_users->getProfileDetails($em_users->profile);
				$response['status'] = true;
				$response['msg']    = Text::_('COM_EMUNDUS_SUCCESS');
			}
			else
			{
				$response['msg'] = 'No profile found';
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * @return void
	 *
	 * @description Export users' selected data. Extracted data are also selected by the user.
	 *
	 * @throws Exception
	 */
	public function exportusers()
	{
		$current_user = Factory::getApplication()->getIdentity();
		if (!EmundusHelperAccess::asAccessAction(12, 'r', $current_user->id))
		{
			$this->setRedirect('index.php', Text::_('ACCESS_DENIED'), 'error');

			return;
		}

		$m_users = new EmundusModelUsers();

		// Retrieve the users' data to extract (indicated by the checkboxes checked)
		$checkboxes = $this->input->getString('checkboxes');
		$users      = $this->input->getString('users', null);

		$checkboxes = (array) json_decode(stripslashes($checkboxes));

		// If 'all' is choosed, it's necessary to retrieve the ids
		if ($users === 'all')
		{
			$all_users = $m_users->getUsers(0, 0);
			$user_ids  = array();
			foreach ($all_users as $user)
			{
				$user_ids[] = $user->id;
			}
		}
		else
		{
			$user_ids = (array) json_decode(stripslashes($users));
		}

		$user_details = array();
		foreach ($user_ids as $uid)
		{
			$user_details[] = $m_users->getUserDetails($uid);
		}

		// Fill CSV
		$export_filename = 'export_users_' . $current_user->id . '_' . date('Y-m-d_H:i') . '.csv';
		$path            = JPATH_SITE . '/tmp/' . $export_filename;

		$seen_keys = [];
		$headers   = array();

		// Fill keys
		$csv_file = fopen($path, 'w');

		foreach ($user_details as $user_detail)
		{
			foreach ($user_detail as $key => $value)
			{
				if (!in_array($key, $seen_keys) && $checkboxes[$key])
				{
					$seen_keys[] = $key;
					$headers[]   = Text::_(strtoupper($key));
				}
			}
		}
		fputcsv($csv_file, $headers, ';');
		//

		// Retrieve all the value of users' data necessary
		foreach ($user_details as $user_detail)
		{
			$userData = array();
			foreach ($user_detail as $key => $value)
			{
				if (in_array($key, $seen_keys))
				{

					if ($key === 'COM_EMUNDUS_FIRSTNAME' || $key === 'COM_EMUNDUS_LASTNAME')
					{
						$userData[] = $value;
					}
					else
					{
						$userData[] = Text::_($value);
					}
				}
			}
			if (!empty(array_filter($userData)))
			{
				fputcsv($csv_file, $userData, ';');
			}
		}
		fclose($csv_file);

		$nb_cols  = count($headers);
		$nb_rows  = count($user_details);
		$xls_file = $m_users->convertCsvToXls($export_filename, $nb_cols, $nb_rows, 'export_users_' . $current_user->id . '_' . date('Y-m-d_H:i'), ';');
		//

		// Add all the headers necessary
		if (!empty($xls_file))
		{
			$path            = JPATH_SITE . '/tmp/' . $xls_file;
			$export_filename = $xls_file;

			header('Content-type: application/vnd.ms-excel');
		}
		else
		{
			header('Content-type: text/csv');
		}

		header('Content-Disposition: attachment; filename=' . $export_filename);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: pre-check=0, post-check=0, max-age=0');
		header('Cache-control: private');
		header('Expires: 0');

		// Encode file's path and file's name if necessary
		echo json_encode(['csvFilePath' => $path, 'fileName' => $export_filename]);
		exit;
	}

	public function getcolumnsfromprofileform()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];
		$user     = $this->app->getIdentity();

		if (!$user->guest)
		{
			$em_users = $this->app->getSession()->get('emundusUser');
			$m_users  = $this->getModel('Users');

			if (!empty($em_users->profile))
			{
				$response['data'] = array_map(function($column) {
					return array_merge((array) $column, ['label' => Text::_($column->label)]);
				}, $m_users->getColumnsFromProfileForm());

				$response['status'] = true;
				$response['msg']    = Text::_('COM_EMUNDUS_SUCCESS');
			}
			else
			{
				$response['msg'] = 'No profile found';
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getacl()
	{
		$action = $this->input->get('action');
		$crud   = $this->input->getString('crud', 'r');
		$fnum   = $this->input->getString('fnum', '');

		if (is_string($action))
		{
			$action = EmundusHelperAccess::getActionIdFromActionName($action);
		}

		$right = EmundusHelperAccess::asAccessAction($action, $crud, $this->user->id, $fnum);

		echo json_encode(array('status' => true, 'right' => $right));
		exit;
	}

	public function disablemfa()
	{
		$result = [
			'status' => true,
			'msg'    => Text::_('COM_USERS_MFA_METHODS_DISABLED'),
		];

		if ($this->user->guest)
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->checkToken($this->input->getMethod());

		// Make sure I am allowed to edit the specified user
		$userId = $this->input->getInt('user_id', null);
		$user   = ($userId === null)
			? $this->app->getIdentity()
			: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
		$user   = $user ?? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);

		if (!MfaHelper::canDeleteMethod($user))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Delete all MFA Methods for the user
		/** @var MethodsModel $model */
		$model = $this->app->bootComponent('com_users')->getMVCFactory()->createModel('Methods', 'Administrator');

		$event = new NotifyActionLog('onComUsersControllerMethodsBeforeDisable', [$user]);
		$this->app->getDispatcher()->dispatch($event->getName(), $event);

		try
		{
			$model->deleteAll($user);
		}
		catch (\Exception $e)
		{
			$result['msg']    = $e->getMessage();
			$result['status'] = false;
		}

		echo json_encode($result);
		exit;
	}

	function getuseraccessrights()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			$m_users = $this->getModel('Users');
			$response['data'] = $m_users->getUserACL($this->user->id);
			$response['status'] = true;
			$response['msg'] = Text::_('COM_EMUNDUS_SUCCESS');
		}

		echo json_encode((object)$response);
		exit;
	}

	function getprofiles()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$m_users = $this->getModel('Users');
			$response['data'] = array_values($m_users->getProfiles());
			$response['status'] = true;
			$response['msg'] = Text::_('COM_EMUNDUS_SUCCESS');
		}

		echo json_encode((object)$response);
		exit;
	}
}
