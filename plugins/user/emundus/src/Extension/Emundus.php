<?php

namespace Joomla\Plugin\User\Emundus\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\User\AfterDeleteEvent;
use Joomla\CMS\Event\User\AfterResetCompleteEvent;
use Joomla\CMS\Event\User\AfterSaveEvent;
use Joomla\CMS\Event\User\LoginEvent;
use Joomla\CMS\Event\User\LogoutEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Tchooz\Traits\TraitVersion;

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitVersion.php';

/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */
final class Emundus extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	use TraitVersion;

	const VERSION = '2.10.5';

	public static function getSubscribedEvents(): array
	{
		return [
			'onUserAfterDelete'        => 'onUserAfterDelete',
			'onUserAfterSave'          => 'onUserAfterSave',
			'onUserLogin'              => 'onUserLogin',
			'onUserLogout'             => 'onUserLogout',
			'onUserAfterResetComplete' => 'onUserAfterResetComplete'
		];
	}

	public function onUserAfterDelete(AfterDeleteEvent $event): void
	{
		$user    = $event->getUser();
		$success = $event->getDeletingResult();

		if (!$success)
		{
			return;
		}

		$userId = (int) $user['id'];

		// Only execute this if the session metadata is tracked
		if ($this->getApplication()->get('session_metadata', true))
		{
			UserHelper::destroyUserSessions($userId, true);
		}

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$db->setQuery('SHOW TABLES');
		$tables = $db->loadColumn();

		foreach ($tables as $table)
		{
			if ($table === 'jos_messages')
			{
				$query->clear()
					->delete($db->quoteName($table))
					->where($db->quoteName('user_id_from') . ' = ' . $userId . ' OR ' . $db->quoteName('user_id_to') . ' = ' . $userId);
			}

			if (strpos($table, 'emundus_') === false)
			{
				continue;
			}
			if (strpos($table, 'emundus_group_assoc') > 0)
			{
				continue;
			}
			if (strpos($table, 'emundus_groups_eval') > 0)
			{
				continue;
			}
			if (strpos($table, 'emundus_tag_assoc') > 0)
			{
				continue;
			}
			if (strpos($table, 'emundus_stats') > 0)
			{
				continue;
			}
			if (strpos($table, '_repeat') > 0)
			{
				continue;
			}
			if (strpos($table, 'setup_') > 0 || strpos($table, '_country') > 0 || strpos($table, '_users') > 0 || strpos($table, '_acl') > 0)
			{
				continue;
			}
			
			$columns = $db->getTableColumns($table);

			if (in_array($table, ['jos_emundus_files_request', 'jos_emundus_final_grade', 'jos_emundus_evaluations']))
			{
				if(!in_array('student_id', array_keys($columns)))
				{
					continue;
				}

				$query->clear()
					->delete($db->quoteName($table))
					->where($db->quoteName('student_id') . ' = ' . $userId);
			}
			elseif (in_array($table, ['jos_emundus_uploads', 'jos_emundus_groups', 'jos_emundus_users', 'jos_emundus_emailalert']))
			{
				if(!in_array('user_id', array_keys($columns)))
				{
					continue;
				}

				$query->clear()
					->delete($db->quoteName($table))
					->where($db->quoteName('user_id') . ' = ' . $userId);
			}
			elseif (in_array($table, ['jos_emundus_comments', 'jos_emundus_campaign_candidature']))
			{
				if(!in_array('applicant_id', array_keys($columns)))
				{
					continue;
				}

				$query->clear()
					->delete($db->quoteName($table))
					->where($db->quoteName('applicant_id') . ' = ' . $userId);
			}
			elseif (str_contains($table, 'jos_emundus_evaluations_'))
			{
				if(!in_array('evaluator', array_keys($columns)))
				{
					continue;
				}

				$query->clear()
					->delete($db->quoteName($table))
					->where($db->quoteName('evaluator') . ' = ' . $userId);
			}
			else
			{
				continue;
			}

			try
			{
				$db->setQuery($query);
				$db->execute();
			}
			catch (\Exception $e)
			{
				// Do nothing.
				continue;
			}
		}

		// Delete hikashop user even if orders exist
		$query->clear()
			->delete($db->quoteName('#__hikashop_user'))
			->where($db->quoteName('user_email') . ' = ' . $db->quote($user['email']));
		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (\Exception $e)
		{
			Log::add('Error at line ' . __LINE__ . ' of file ' . __FILE__ . ' : ' . '. Error is : ' . preg_replace("/[\r\n]/", " ", $e->getMessage()), Log::ERROR, 'com_emundus');

			return;
		}
		//

		$dir = EMUNDUS_PATH_ABS . $userId . DS;
		if (is_dir($dir))
		{
			if (!$dh = opendir($dir))
			{
				if (!$this->getApplication()->isClient('cli'))
				{
					$this->getApplication()->enqueueMessage(Text::_("JERROR_AN_ERROR_HAS_OCCURRED"), 'error');
				}

				return;
			}

			while (false !== ($obj = readdir($dh)))
			{
				if ($obj == '.' || $obj == '..') continue;
				if (!unlink($dir . $obj))
				{
					if (!$this->getApplication()->isClient('cli'))
					{
						$this->getApplication()->enqueueMessage(Text::_("FILE_NOT_FOUND") . " : " . $obj . "\n", 'error');
					}
				}
			}

			closedir($dh);
			rmdir($dir);
		}

		if ($this->getApplication()->get('mailonline', true) && $this->params->get('send_email_delete', 0) == 1 && !empty($user['email']) && !$this->getApplication()->isClient('cli'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
			require_once(JPATH_SITE . '/components/com_emundus/helpers/emails.php');

			$m_emails = new \EmundusModelEmails();
			$post     = [
				'NAME'      => $user['name'],
				'APPLICANT_NAME' => $user['name'],
				'LOGO'      => \EmundusHelperEmails::getLogo(),
				'SITE_NAME' => $this->getApplication()->get('sitename'),
			];

			$m_emails->sendEmailNoFnum($user['email'], 'delete_user', $post);
		}
	}

	public function onUserAfterSave(AfterSaveEvent $event): void
	{
		$user  = $event->getUser();
		$isnew = $event->getIsNew();

		// Do not run in CLI mode
		if ($this->getApplication()->isClient('cli'))
		{
			return;
		}

		$db = $this->getDatabase();

		$input      = $this->getApplication()->input;
		$details    = $input->post->get('jform', null, 'none');
		$fabrik     = $input->post->get('listid', null);
		$option     = $input->get->get('option', null);
		$controller = $input->get->get('controller', null);
		$task       = $input->get->get('task', null);

		$profile = 0;

		// If the details are empty, we are probably signing in via LDAP for the first time.
		if ($isnew && empty($details) && empty($fabrik))
		{
			if (PluginHelper::getPlugin('system', 'emundusproxyredirect'))
			{
				return;
			}

			if (PluginHelper::getPlugin('authentication', 'ldap') && ($option !== 'com_emundus' && $controller !== 'users' && $task !== 'adduser'))
			{
				$this->isLdap($user);
			}

			if (PluginHelper::getPlugin('authentication', 'externallogin') && ($option !== 'com_emundus' && $controller !== 'users' && $task !== 'adduser'))
			{
				$username = explode(' ', $user["name"]);
				$name     = '';
				if (count($username) > 2)
				{
					for ($i = 1; $i > count($username); $i++)
					{
						$name .= ' ' . $username[$i];
					}
				}
				else
				{
					$name = $username[1];
				}

				$details['name']                        = $name;
				$details['emundus_profile']['lastname'] = $name;
				$details['firstname']                   = $username[0];
			}

			if (PluginHelper::getPlugin('authentication', 'miniorangesaml') && ($option !== 'com_emundus' && $controller !== 'users' && $task !== 'adduser'))
			{
				$o_user = $this->getUserFactory()->loadUserById($user['id']);

				$username        = explode(' ', $user["name"]);
				$details['name'] = count($username) > 2 ? implode(' ', array_slice($username, 1)) : $username[1];

				$details['emundus_profile']['lastname']  = $details['name'];
				$details['emundus_profile']['firstname'] = $username[0];

				$o_user->setParam('saml', '1');
				$o_user->save();

				// Set the user table instance to include the new token.
				$table = Table::getInstance('user');
				$table->load($o_user->id);
				$table->block = 0;

				// Save user data
				if (!$table->store())
				{
					throw new \RuntimeException($table->getError());
				}

				$eMConfig = ComponentHelper::getParams('com_emundus');
				$profile  = $eMConfig->get('saml_default_profile', 1000);
			}
		}

		$query = $db->getQuery(true);

		if (is_array($details) && count($details) > 0 && $task != 'reset.complete')
		{
			$campaign_id = $details['emundus_profile']['campaign'] ?? $details['campaign'];
			$lastname    = $details['emundus_profile']['lastname'] ?? $details['name'];
			$firstname   = $details['emundus_profile']['firstname'] ?? $details['firstname'];

			if ($isnew)
			{
				$query->update($db->quoteName('#__users'))
					->set($db->quoteName('name') . ' = ' . ($db->quote(ucfirst($firstname) . ' ' . strtoupper($lastname))))
					->set($db->quoteName('usertype') . ' = (SELECT u.title FROM #__usergroups AS u
												LEFT JOIN #__user_usergroup_map AS uum ON u.id=uum.group_id
												WHERE uum.user_id=' . $user['id'] . ' ORDER BY uum.group_id DESC LIMIT 1)')
					->where($db->quoteName('id') . ' = ' . $db->quote($user['id']));

				try
				{
					$db->setQuery($query);
					$db->execute();
				}
				catch (ExecutionFailureException $e)
				{
					// catch any database errors.
					Log::add($e->getMessage(), Log::WARNING, 'com_emundus');
				}

				if (!empty($campaign_id))
				{
					// Get the profile ID from the campaign selected
					$query->clear()
						->select('*')
						->from($db->quoteName('#__emundus_setup_campaigns'))
						->where($db->quoteName('id') . ' = :campaignId')
						->bind(':campaignId', $campaign_id, ParameterType::INTEGER);
					$db->setQuery($query);
					$campaign = $db->loadAssocList();

					if (!empty($campaign) && !empty($campaign[0]['profile_id']))
					{
						$profile = $campaign[0]['profile_id'];
					}
					elseif (empty($profile))
					{
						$profile = 1000;
					}
				}
				elseif (empty($profile))
				{
					$profile = 1000;
				}

				$insert = (object) [
					'user_id'      => $user['id'],
					'firstname'    => ucfirst($firstname),
					'lastname'     => strtoupper($lastname),
					'profile'      => $profile,
					'registerDate' => $user['registerDate'],
				];

				try
				{
					$db->insertObject('#__emundus_users', $insert);
				}
				catch (\Exception $e)
				{
					// catch any database errors.
				}

				// Insert data in #__emundus_users_profiles
				$insert = (object) [
					'user_id'    => $user['id'],
					'profile_id' => $profile,
				];

				try
				{
					$db->insertObject('#__emundus_users_profiles', $insert);
				}
				catch (\Exception $e)
				{
					// catch any database errors.
				}

				if (!empty($campaign_id))
				{
					if (!class_exists('EmundusHelperFiles'))
					{
						require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
					}
					$insert = (object) [
						'applicant_id' => $user['id'],
						'campaign_id'  => $campaign_id,
						'fnum'         => \EmundusHelperFiles::createFnum($campaign_id, $user['id'])
					];

					try
					{
						$db->insertObject('#__emundus_campaign_candidature', $insert);
					}
					catch (\Exception $e)
					{
						// catch any database errors.
					}
				}
			}
			else
			{
				try
				{
					if (!empty($firstname) && !empty($lastname))
					{
						$update = (object) [
							'id'   => $user['id'],
							'name' => ucfirst($firstname) . ' ' . strtoupper($lastname),
						];
						$db->updateObject('#__users', $update, 'id');

						$update = (object) [
							'user_id'   => $user['id'],
							'firstname' => ucfirst($firstname),
							'lastname'  => strtoupper($lastname),
						];
						$db->updateObject('#__emundus_users', $update, 'user_id');

						// Update personal details table if exists
						$update = (object) [
							'user'       => $user['id'],
							'first_name' => ucfirst($firstname),
							'last_name'  => strtoupper($lastname),
						];
						$db->updateObject('#__emundus_personal_detail', $update, 'user');
					}

					if (!empty($details['email1']))
					{
						$update = (object) [
							'user_id' => $user['id'],
							'email'   => $details['email1'],
						];
						$db->updateObject('#__emundus_users', $update, 'user_id');

						$e_session        = $this->getApplication()->getSession()->get('emundusUser');
						$e_session->email = $details['email1'];
						$this->getApplication()->getSession()->set('emundusUser', $e_session);
					}
				}
				catch (\Exception $e)
				{
					Log::add('Error at line ' . __LINE__ . ' of file ' . __FILE__ . ' : ' . '. Error is : ' . preg_replace("/[\r\n]/", " ", $e->getMessage()), Log::ERROR, 'com_emundus');
				}

				//$this->onUserLogin($user);
			}
		}
	}
	
	public function onUserAfterResetComplete(AfterResetCompleteEvent $event): void
	{
		$user = $event->getUser();

		if(!empty($user->id))
		{
			$db = $this->getDatabase();
			// Update lastResetTime
			$update = (object) [
				'id'            => $user->id,
				'lastResetTime' => Factory::getDate()->toSql(),
			];
			$db->updateObject('#__users', $update, 'id');
		}
	}

	public function onUserLogin(LoginEvent $event): void
	{
		// Do not run in CLI mode
		if ($this->getApplication()->isClient('cli'))
		{
			return;
		}

		$db = $this->getDatabase();

		$user     = $event->getAuthenticationResponse();
		$options  = $event->getOptions();
		$instance = $this->getUserFactory()->loadUserByUsername($user['username']);

		$input    = $this->getApplication()->input;
		$session  = $this->getApplication()->getSession();
		$redirect = $input->get->getBase64('redirect');

		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.auth.php'], Log::ALL, array('com_emundus.auth'));
		Log::add($user['username'] . ' try to log in.', Log::INFO, 'com_emundus.auth');

		include_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		include_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
		$m_profile = new \EmundusModelProfile();
		$m_users   = new \EmundusModelUsers();

		$id = (int) UserHelper::getUserId($user['username']);

		$mapping_emundus_profiles = !empty($user['emundus_profiles']) ? $user['emundus_profiles'] : [];
		$openid_profiles          = !empty($user['openid_profiles']) ? $user['openid_profiles'] : [];

		if ($id)
		{
			$instance->load($id);
		}

		$emConfig = ComponentHelper::getParams('com_emundus');
		$ask_reset_password = $emConfig->get('ask_reset_password', 0);
		if($ask_reset_password == 1)
		{
			$version_date = $this->getVersionDate($db, self::VERSION);

			if (
				$this->isLocal($instance)
				&&
				(
					(
						empty($instance->lastResetTime) && strtotime($instance->registerDate) < strtotime($version_date)
					)
					||
					(
						!empty($instance->lastResetTime) && strtotime($instance->lastResetTime) < strtotime($version_date)
					)
				)
			)
			{
				$this->loadLanguage();

				$event->addResult(false);

				// Logout user if lastResetTime is empty and redirect to forgot password
				$this->getApplication()->logout($id, ['redirect_link' => Route::_('index.php?option=com_users&view=reset') . '?email=' . urlencode($user['email']), 'redirect_message' => Text::_('PLG_USER_EMUNDUS_RESET_PASSWORD'), 'redirect_code' => 303]);

				return;
			}
		}

		if (empty($redirect))
		{
			$previous_url = base64_decode($this->getApplication()->getUserState('users.openfile.return', ''));

			if (empty($previous_url))
			{
				parse_str($input->server->getVar('HTTP_REFERER'), $return_url);
				$previous_url = base64_decode($return_url['return']);
				if (empty($previous_url))
				{
					$previous_url = base64_decode($input->POST->getVar('return'));
				}
				if (empty($previous_url))
				{
					$previous_url = base64_decode($return_url['redirect']);
				}
			}

			// Clear user state for openfile return
			$this->getApplication()->setUserState('users.openfile.return', '');
		}
		else
		{
			$previous_url = base64_decode($redirect);
		}

		$isAdmin = $this->getApplication()->isClient('administrator');
		if (!$isAdmin)
		{
			// Users coming from an OAuth system are immediately signed in and thus need to have their data entered in the eMundus table.
			if ($user['type'] == 'OAuth2')
			{
				// Insert the eMundus user info into the DB.
				if ($user['isnew'])
				{
					$query = $db->getQuery(true);

					$query->select('*')
						->from('#__emundus_users')
						->where('user_id = ' . $this->getApplication()->getIdentity()->id);

					try
					{
						$db->setQuery($query);
						$result = $db->loadObject();
					}
					catch (\Exception $e)
					{
						Log::add('Error checking if user is not already in emundus users', Log::ERROR, 'com_emundus.error');
					}

					if (empty($result) && empty($result->id))
					{
						$user_params = [
							'firstname' => $user['firstname'],
							'lastname'  => strtoupper($user['lastname']),
							'profile'   => $user['profile']
						];
						$m_users->addEmundusUser($this->getApplication()->getIdentity()->id, $user_params);
					}

					$pass     = bin2hex(openssl_random_pseudo_bytes(4));
					$password = array('password' => $pass, 'password2' => $pass);

					$o_user       = new User(UserHelper::getUserId($user['username']));
					$o_user->name = $user['firstname'] . ' ' . strtoupper($user['lastname']);
					$o_user->bind($password);
					$o_user->save();

					unset($pass, $password);

					PluginHelper::importPlugin('authentication');
					$this->getApplication()->triggerEvent('onOAuthAfterRegister', ['user' => $user]);
				}

				// Add the Oauth provider type to the Joomla user params.
				if (!empty($options['provider']))
				{
					$o_user = new User(UserHelper::getUserId($user['username']));
					$o_user->setParam('OAuth2', $options['provider']);
					$o_user->setParam('token', json_encode($options['token']));
					$o_user->save();
				}

				$previous_url = "";
				if (!empty($options['redirect']))
				{
					$previous_url = $options['redirect'];
				}

			}

			/* DEPRECATED */
			if ($user['type'] == 'externallogin')
			{
				try
				{
					$query = $db->getQuery(true);

					$user_id = $this->getApplication()->getIdentity()->id;

					if (isset($user['firstname']) || isset($user['lastname']))
					{
						$query->clear()
							->update('#__emundus_users');

						if (isset($user['firstname']))
						{
							$query->set($db->quoteName('firstname') . ' = ' . $db->quote($user['firstname']));
						}
						if (isset($user['lastname']))
						{
							$query->set($db->quoteName('lastname') . ' = ' . $db->quote($user['lastname']));
						}
						$query->where($db->quoteName('user_id') . ' = ' . $db->quote($user_id));

						$db->setQuery($query);
						$db->execute();
					}

					$query->clear()
						->update('#__users')
						->set($db->quoteName('activation') . ' = 1')
						->where($db->quoteName('id') . ' = ' . $db->quote($user_id));
					$db->setQuery($query);
					$db->execute();


					if (!empty($user['other_properties']))
					{
						foreach ($user['other_properties'] as $key => $other_property)
						{
							if (!empty($other_property->values))
							{
								$table  = explode('___', $key)[0];
								$column = explode('___', $key)[1];

								$query->clear()
									->select($db->quoteName($column))
									->from($db->quoteName($table))
									->where($db->quoteName('user_id') . ' = ' . $user_id);
								if ($other_property->method == 'insert')
								{
									$query->andWhere($db->quoteName($column) . ' = ' . $other_property->values);
								}
								$db->setQuery($query);
								$result = $db->loadResult();

								if (empty($result))
								{
									$query->clear();
									if ($other_property->method == 'update')
									{
										$query->update($db->quoteName($table));
									}
									if ($other_property->method == 'insert')
									{
										$query->insert($db->quoteName($table));
									}
									$query->set($db->quoteName($column) . ' = ' . $db->quote($other_property->values));

									if ($other_property->method == 'update')
									{
										$query->where($db->quoteName('user_id') . ' = ' . $db->quote($user_id));
									}
									if ($other_property->method == 'insert')
									{
										$query->set($db->quoteName('user_id') . ' = ' . $db->quote($user_id));
									}
									$db->setQuery($query);
									$db->execute();
								}
							}
						}
					}
				}
				catch (\Exception $e)
				{
					Log::add('plugins/user/emundus/emundus.php | Error when update some informations on profile with external login : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}

			}
			// END DEPRECATED

			// Check if the user is in the emundus_users table
			$user_repaired = $m_users->repairEmundusUser($this->getApplication()->getIdentity()->id);
			if (!$user_repaired)
			{
				Log::add('Failed attempt to log in ' . $user['username'] . ', user repair failed.', Log::WARNING, 'com_emundus.auth');

				return;
			}

			$m_profile->initEmundusSession();
			$user = $session->get('emundusUser');

			$user_profiles_id     = array_map(function ($profile) {
				return $profile->id;
			}, $user->emProfiles);
			$user_default_profile = $user->profile;

			// Check if we have to remove openid profiles
			if (!empty($openid_profiles))
			{
				$query = $db->getQuery(true);
				foreach ($openid_profiles as $openidProfile)
				{
					if (!in_array($openidProfile, $mapping_emundus_profiles) && in_array($openidProfile, $user_profiles_id))
					{
						$m_users->removeProfileToUser($user->id, $openidProfile);
					}
				}
			}

			// Check if we have a mapping of emundus_profiles
			if (!empty($mapping_emundus_profiles))
			{
				foreach ($mapping_emundus_profiles as $profile)
				{
					if (!in_array($profile, $user_profiles_id))
					{
						$query = $db->getQuery(true);
						$query->clear()
							->select('esp.published')
							->from($db->quoteName('#__emundus_users', 'eu'))
							->leftJoin($db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $db->quoteName('esp.id') . ' = ' . $db->quoteName('eu.profile'))
							->where($db->quoteName('eu.user_id') . ' = ' . $db->quote($user->id));
						$db->setQuery($query);
						$default_profile_status = $db->loadResult();

						if ($default_profile_status == 1)
						{
							$query->clear()
								->update($db->quoteName('#__emundus_users'))
								->set($db->quoteName('profile') . ' = ' . $db->quote($profile))
								->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));
							$db->setQuery($query);
							$db->execute();

							$user->profile = $profile;
						}

						$m_users->addProfileToUser($user->id, $profile);
					}
				}
			}

			$m_users->checkProfilesUser($user->id);

			if (!empty($openid_profiles) || !empty($mapping_emundus_profiles))
			{
				$m_profile->initEmundusSession();
				$user = $session->get('emundusUser');
			}

			// Log the action of signing in.
			// No id exists in jos_emundus_actions for signin so we use -2 instead.
			if (!class_exists('EmundusModelLogs'))
			{
				require_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
			}

			// if user_id is null -> there is no session data because the account is not activated yet, so don't log
			if ($user->id)
			{
				\EmundusModelLogs::log($user->id, $user->id, null, -2, '', 'COM_EMUNDUS_LOGS_USER_LOGIN');
			}
			else
			{
				Log::add('Failed attempt to log in ' . $user->username . ', user id is null.', Log::INFO, 'com_emundus.auth');
			}

			$user->just_logged = true;

			if (empty($user->lastvisitDate))
			{
				$user->first_logged = true;
			}
			$session->set('emundusUser', $user);

			if ($options['redirect'] === 0)
			{
				$previous_url = '';
			}
			else
			{
				if ($user->activation != -1)
				{
					$cid_session = $session->get('login_campaign_id');
					if (!empty($cid_session))
					{
						$previous_url = 'index.php?option=com_fabrik&view=form&formid=102&cid=' . $cid_session;
						$session->clear('login_campaign_id');
					}
				}
			}

			PluginHelper::importPlugin('emundus', 'custom_event_handler');
			$this->getApplication()->triggerEvent('onCallEventHandler', ['onUserLogin', ['user_id' => $user->id]]);

			if (!empty($previous_url))
			{
				Log::add('Log in ' . $user->username . ', user redirected to previous url ' . $previous_url . '.', Log::INFO, 'com_emundus.auth');
				$this->getApplication()->redirect($previous_url);
			}
		}
	}

	public function onUserLogout(LogoutEvent $event)
	{
		$user     = $event->getParameters();
		$options  = $event->getOptions();

		$my      = $this->getApplication()->getIdentity();
		$session = Factory::getSession();

		$userid = (int) $user['id'];

		if(empty($options['redirect_link']))
		{
			include_once(JPATH_SITE . '/components/com_emundus/helpers/menu.php');
			$url = \EmundusHelperMenu::getLogoutRedirectLink();

			$this->getApplication()->redirect(Uri::base(true) . $url);
		}
		else {
			if(!empty($options['redirect_message']))
			{
				$this->getApplication()->enqueueMessage($options['redirect_message']);
			}
			$this->getApplication()->redirect($options['redirect_link'], $options['redirect_code'] ?? 303);
		}
	}

	private function isLdap(array $user): array
	{
		$details = [];

		require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		$m_users = new \EmundusModelusers();

		$return = $m_users->searchLDAP($user['username']);

		if (!empty($return->users[0]))
		{
			$params       = ComponentHelper::getParams('com_emundus');
			$ldapElements = explode(',', $params->get('ldapElements'));

			$details['firstname'] = $return->users[0][trim($ldapElements[2])];
			$details['name']      = $return->users[0][trim($ldapElements[3])];
			if (is_array($details['firstname']))
			{
				$details['firstname'] = $details['firstname'][0];
			}
			if (is_array($details['name']))
			{
				$details['name'] = $details['name'][0];
			}

			// Give the user an LDAP param.
			$o_user = Factory::getUser($user['id']);

			// Store token in User's Parameters
			$o_user->setParam('ldap', '1');
			$o_user->save();
		}

		return $details;
	}

	private function isLocal(User $user): bool
	{
		$userParams = (!empty($user->params)) ? json_decode($user->params) : [];

		if (!empty($userParams) && ($userParams->OAuth2 === 'openid' || $userParams->proxy_user == 1 || $userParams->ldap == 1 || $this->isSamlUser($user->id)))
		{
			return false;
		}

		return true;
	}

	private function isSamlUser(int $user_id): bool
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('profile_value')
			->from($db->quoteName('#__user_profiles'))
			->where($db->quoteName('user_id') . ' = ' . $db->quote($user_id))
			->where($db->quoteName('profile_key') . ' = ' . $db->quote('profile.issaml'));
		$db->setQuery($query);

		return !empty($db->loadResult());
	}
}