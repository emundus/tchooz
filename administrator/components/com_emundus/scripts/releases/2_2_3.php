<?php


/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\UserHelper;

class Release2_2_3Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES', 'Langues du programme');
			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES', 'Program languages', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES_INTRO', 'Définissez les langues de dépôt autorisées pour les déposants. Si ils ne sont pas dans la bonne langue de dépôt, ils verront un message apparaitre les invitants à basculer de langue. Ce paramètre sera hérité sur toutes les campagnes de ce programme.');
			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES_INTRO', 'Define the deposit languages allowed for depositors. If they\'re not in the right language, they\'ll see a message prompting them to switch languages. This setting will be inherited by all campaigns in this program.', 'override', 0, null, null, 'en-GB');

			// Check if we have a user with email automatedtask@emundus.fr
			$query->select('id')
				->from('#__users')
				->where('email = ' . $this->db->quote('automatedtask@emundus.fr'));
			$this->db->setQuery($query);
			$automated_user_id = $this->db->loadResult();

			if(empty($automated_user_id)) {
				require_once(JPATH_SITE.'/components/com_emundus/helpers/users.php');
				$h_users = new \EmundusHelperUsers;
				$password = $h_users->generateStrongPassword(30);
				$automated_user_id = $this->createUser(1000,'automatedtask@emundus.fr',$password,[2],'Task', 'AUTOMATED');

				if ($automated_user_id) {
					EmundusHelperUpdate::updateComponentParameter('com_emundus', 'automated_task_user', $automated_user_id);
				}
			} else {
				// Check if exist in emundus users
				$query->clear()
					->select('id')
					->from('#__emundus_users')
					->where('user_id = ' . $automated_user_id);
				$this->db->setQuery($query);
				$emundus_user_id = $this->db->loadResult();

				if(empty($emundus_user_id)) {
					$other_param['firstname'] 		= 'Task';
					$other_param['lastname'] 		= 'AUTOMATED';
					$other_param['profile'] 		= 1000;
					$other_param['em_oprofiles'] 	= '';
					$other_param['univ_id'] 		= 0;
					$other_param['em_groups'] 		= '';
					$other_param['em_campaigns'] 	= [];
					$other_param['news'] 			= '';

					require_once(JPATH_SITE.'/components/com_emundus/models/users.php');
					$m_users = new \EmundusModelUsers;
					$m_users->addEmundusUser($automated_user_id, $other_param);
				}
			}
			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();

			return $result;
		}


		return $result;
	}

	private function createUser($profile = 9, $username = 'user.test@emundus.fr', $password = 'test1234', $j_groups = [2], $firstname = 'Test', $lastname = 'USER'): int
	{
		$user_id = 0;

		require_once(JPATH_SITE.'/components/com_emundus/models/users.php');
		$m_users = new \EmundusModelUsers;
		$query = $this->db->getQuery(true);

		$query->insert('#__users')
			->columns('name, username, email, password, registerDate, lastvisitDate, params')
			->values($this->db->quote($lastname . ' ' . $firstname) . ', ' . $this->db->quote($username) .  ', ' . $this->db->quote($username) . ',' .  $this->db->quote(UserHelper::hashPassword($password)) . ',' . $this->db->quote(date('Y-m-d H:i:s')) . ',' . $this->db->quote(date('Y-m-d H:i:s')) . ',' . $this->db->quote('{}'));

		try {
			$this->db->setQuery($query);
			$this->db->execute();
			$user_id = $this->db->insertid();
		} catch (\Exception $e) {
			error_log("Failed to insert jos_users" . $e->getMessage());
			Log::add("Failed to insert jos_users" . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		if (!empty($user_id)) {
			if(!empty($j_groups)) {
				foreach ($j_groups as $j_group) {
					$query->clear()
						->insert($this->db->quoteName('#__user_usergroup_map'))
						->columns('user_id, group_id')
						->values($user_id . ',' . $j_group);
					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (\Exception $e) {
						Log::add("Failed to insert jos_user_usergroup_map" . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}
				}
			}

			$other_param['firstname'] 		= $firstname;
			$other_param['lastname'] 		= $lastname;
			$other_param['profile'] 		= $profile;
			$other_param['em_oprofiles'] 	= '';
			$other_param['univ_id'] 		= 0;
			$other_param['em_groups'] 		= '';
			$other_param['em_campaigns'] 	= [];
			$other_param['news'] 			= '';
			$m_users->addEmundusUser($user_id, $other_param);
		} else {
			error_log('Failed to create sample user');
		}

		return $user_id;
	}
}