<?php

/**
 * @package         Emundus.Plugin
 * @subpackage      System.emundus
 *
 * @copyright       Copyright (C) 2005-2025 eMundus - All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Emundus\Extension;

use EmundusModelForm;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Event\Application\AfterRenderEvent;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Event\User\LoginEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Users\Administrator\Helper\Mfa as MfaHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterRenderDefinition;
use Tchooz\Entities\Emails\Modifiers\CapitalizeModifier;
use Tchooz\Entities\Emails\Modifiers\ChoiceStatusModifier;
use Tchooz\Entities\Emails\Modifiers\LettersModifier;
use Tchooz\Entities\Emails\Modifiers\LowercaseModifier;
use Tchooz\Entities\Emails\Modifiers\NumberModifier;
use Tchooz\Entities\Emails\Modifiers\TrimModifier;
use Tchooz\Entities\Emails\Modifiers\UppercaseModifier;
use Tchooz\Entities\Emails\TagModifierRegistry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

final class Emundus extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	const LABEL_COLORS = [
		'lightpurple' => '--em-purple-2',
		'purple'      => '--em-purple-2',
		'darkpurple'  => '--em-purple-2',
		'lightblue'   => '--em-light-blue-2',
		'blue'        => '--em-blue-2',
		'darkblue'    => '--em-blue-3',
		'lightgreen'  => '--em-green-2',
		'green'       => '--em-green-2',
		'darkgreen'   => '--em-green-2',
		'lightyellow' => '--em-yellow-2',
		'yellow'      => '--em-yellow-2',
		'darkyellow'  => '--em-yellow-2',
		'lightorange' => '--em-orange-2',
		'orange'      => '--em-orange-2',
		'darkorange'  => '--em-orange-2',
		'lightred'    => '--em-red-1',
		'red'         => '--em-red-2',
		'darkred'     => '--em-red-2',
		'pink'        => '--em-pink-2',
		'default'     => '--neutral-600',
	];

	public static function getSubscribedEvents(): array
	{
		$app = Factory::getApplication();

		$mapping = [];

		if ($app->isClient('site') || $app->isClient('administrator'))
		{
			$mapping['onBeforeCompileHead'] = 'injectLazyJS';
			$mapping['onAfterRender']       = 'onAfterRender';
			$mapping['onAfterInitialise']   = 'onAfterInitialise';
		}

		return $mapping;
	}

	public function onAfterInitialise(AfterInitialiseEvent $event): void
	{
		if (!class_exists('TagModifierRegistry'))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Interfaces/TagModifierInterface.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/TagModifierRegistry.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/Modifiers/UppercaseModifier.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/Modifiers/LowercaseModifier.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/Modifiers/CapitalizeModifier.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/Modifiers/TrimModifier.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/Modifiers/LettersModifier.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/Modifiers/NumberModifier.php';
			require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Emails/Modifiers/ChoiceStatusModifier.php';
		}

		TagModifierRegistry::register(new UppercaseModifier());
		TagModifierRegistry::register(new LowercaseModifier());
		TagModifierRegistry::register(new CapitalizeModifier());
		TagModifierRegistry::register(new TrimModifier());
		TagModifierRegistry::register(new LettersModifier());
		TagModifierRegistry::register(new NumberModifier());
		TagModifierRegistry::register(new ChoiceStatusModifier());
	}

	public function injectLazyJS(EventInterface $event): void
	{
		// Only inject in HTML documents
		if ($this->getApplication()->getDocument()->getType() !== 'html')
		{
			return;
		}

		if ($this->getApplication()->isClient('administrator'))
		{
			if (empty($_REQUEST['option']) || $_REQUEST['option'] != 'com_emundus')
			{
				return;
			}
		}

		$head = $this->getApplication()->getDocument()->getHeadData();
		$wa   = $this->getApplication()->getDocument()->getWebAssetManager();

		$profile_data = [];
		if (!$this->getApplication()->getIdentity()->guest)
		{
			$e_session       = $this->getApplication()->getSession()->get('emundusUser');
			$profile_details = null;

			if (!empty($e_session->profile))
			{
				if (!class_exists('EmundusModelUsers'))
				{
					require_once JPATH_ROOT . '/components/com_emundus/models/users.php';
				}
				$m_users = $this->getApplication()->bootComponent('com_emundus')->getMVCFactory()->createModel('Users', 'EmundusModel');

				$profile_details = $m_users->getProfileDetails($e_session->profile);

				if (str_contains($profile_details->class, 'label-'))
				{
					$profile_details->class = str_replace('label-', '--em-', $profile_details->class);
				}
				elseif (!empty(self::LABEL_COLORS[$profile_details->class]))
				{
					$profile_details->class = self::LABEL_COLORS[$profile_details->class];
				}

				$profile_font       = $profile_details->published !== 1 ? '--em-coordinator-font' : '--em-applicant-font';
				$profile_font_title = $profile_details->published !== 1 ? '--em-coordinator-font-title' : '--em-applicant-font-title';

				$style = ':root {
					--em-profile-color: var(' . $profile_details->class . ');
					--em-profile-font: var(' . $profile_font . ');
					--em-profile-font-title: var(' . $profile_font_title . ');
				}';

				$wa->addInlineStyle($style);
			}

			//TODO: Improve this line with cache maybe
			if (!class_exists('EmundusModelForm'))
			{
				require_once JPATH_ROOT . '/components/com_emundus/models/form.php';
			}
			$m_form           = new EmundusModelForm();
			$profile_elements = $m_form->getUserProfileElements(true);

			if (!empty($profile_elements))
			{
				$query = $this->getDatabase()->getQuery(true);
				$query->select($profile_elements)
					->from($this->getDatabase()->quoteName('#__emundus_users'))
					->where($this->getDatabase()->quoteName('user_id') . ' = ' . (int) $this->getApplication()->getIdentity()->id);
				$this->getDatabase()->setQuery($query);
				$profile_data = $this->getDatabase()->loadAssoc();
			}
		}

		$this->getApplication()->getDocument()->setHeadData($head);

		// Add configuration options
		$currentLanguage = $this->getApplication()->getLanguage()->getTag();
		$defaultLanguage = ComponentHelper::getParams('com_languages')->get('site', 'fr-FR');
		if ($currentLanguage !== $defaultLanguage)
		{
			$currentLangPath = '/' . substr($currentLanguage, 0, 2);
		}
		else
		{
			$currentLangPath = '';
		}

		$emConfig = ComponentHelper::getParams('com_emundus');
		$allowAsync = $emConfig->get('async_export', 0);

		$options = [
			'current'     => $currentLanguage,
			'default'     => $defaultLanguage,
			'currentPath' => $currentLangPath
		];
		$this->getApplication()->getDocument()->addScriptOptions('plg_system_emundus.language', $options);
		$this->getApplication()->getDocument()->addScriptOptions('plg_system_emundus.user_details', $profile_data);
		$this->getApplication()->getDocument()->addScriptOptions('plg_system_emundus.async_export', $allowAsync);

		// Load and injection directive
		$wa->getRegistry()->addExtensionRegistryFile('plg_system_emundus');
	}

	public function onAfterRender(AfterRenderEvent $event): void
	{
		$app = $this->getApplication();
		if (!$app->isClient('site'))
		{
			return;
		}

		$user = $app->getIdentity();

		// If samlredirect plugin is active and we're coming from saml login page we can try to update user informations
		$userParams = (!empty($user->params)) ? json_decode($user->params) : [];
		$isSamlUser = false;
		if (!$user->guest && !empty($user->id) && PluginHelper::isEnabled('system', 'samlredirect'))
		{
			$isSamlUser = $this->isSamlUser($user->id);
		}

		if (!$user->guest && $isSamlUser)
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			// Check if __miniorange_saml_config table exists
			$tables = $db->getTableList();
			$tableExists = in_array($db->getPrefix() . 'miniorange_saml_config', $tables);

			if($tableExists)
			{
				$query->select('single_signon_service_url')
					->from($db->quoteName('#__miniorange_saml_config'));
				$db->setQuery($query);
				$singleSignOnServiceUrl = $db->loadResult();

				if (!empty($singleSignOnServiceUrl))
				{
					$parsedUrl   = parse_url($singleSignOnServiceUrl);
					$httpReferer = $_SERVER['HTTP_REFERER'] ?? '';

					if (!empty($httpReferer) && $httpReferer == ($parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/'))
					{
						$query->select('profile_key,profile_value')
							->from($db->quoteName('#__user_profiles'))
							->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));
						$db->setQuery($query);
						$profileDatas = $db->loadAssocList('profile_key', 'profile_value');

						foreach ($profileDatas as $profileKey => $profileValue)
						{
							$profileKeyParts = explode('.', $profileKey);
							if (!empty($profileKeyParts[1]) && !empty($profileKeyParts[2]))
							{
								$query = 'SHOW COLUMNS FROM ' . $db->quoteName('#__' . $profileKeyParts[1]) . ' LIKE ' . $db->quote($profileKeyParts[2]);
								$db->setQuery($query);
								$columnExists = $db->loadResult();

								if (!empty($columnExists))
								{
									// Update the user profile field in the users table
									$query = $db->getQuery(true);
									$query->update($db->quoteName('#__' . $profileKeyParts[1]))
										->set($db->quoteName($profileKeyParts[2]) . ' = ' . $db->quote($profileValue))
										->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));
									$db->setQuery($query);
									$db->execute();
								}
							}
						}
					}
				}
			}
		}
		// End of SAML user info update

		// Add a class to the body tag depending on the emundus profile
		$body = $app->getBody();

		// Define class via emundus profile
		$e_session = $app->getSession()->get('emundusUser');
		if (!empty($e_session))
		{
			$class = $e_session->applicant == 1 ? 'em-applicant' : 'em-coordinator';
		}
		else
		{
			$class = 'em-guest';
		}

		preg_match_all(\chr(1) . '(<div.*\s+id="g-page-surround".*>)' . \chr(1) . 'i', $body, $matches);
		foreach ($matches[0] as $match)
		{
			if (!strpos($match, 'class='))
			{
				$replace = '<div id="g-page-surround" class="' . $class . '">';
				$body    = str_replace($match, $replace, $body);
			}
		}

		$app->setBody($body);
		// End of body class injection

		PluginHelper::importPlugin('emundus');
		$dispatcher    = $app->getDispatcher();
		$onAfterRender = new GenericEvent('onCallEventHandler', [
			'onAfterRender',
			[
				'session' => $e_session,
				'context' => new EventContextEntity($user, [], [$user->id],
					[
						onAfterRenderDefinition::KEY_LOGGED_IN => $user->guest != 1 ? 1 : 0,
					]
				)
			]
		]);
		$dispatcher->dispatch('onCallEventHandler', $onAfterRender);

		// Manage 2fa
		$user = $app->getIdentity();
		if ($user instanceof User && !$user->guest && $user->activation != -1)
		{
			$plugin = PluginHelper::getPlugin('system', 'emundus');
			$params = new Registry($plugin->params);
			$mfaSso = $params->get('2faforSSO', 0);

			if ($mfaSso == 0 && ($isSamlUser || (!empty($userParams) && $userParams->OAuth2 === 'openid')))
			{
				// If user logged in via SAML or OIDC we skip the 2FA enforcement
				return;
			}
			// If 2fa for SSO is enabled but user email is @emundus.fr we skip the 2FA enforcement
			elseif (
				$mfaSso == 1 &&
				(!empty($userParams) && ($userParams->OAuth2 === 'openid') && str_ends_with($user->email, '@emundus.fr'))
			)
			{
				return;
			}

			$profiles = $params->get('2faForceForProfiles', []);
			if (!empty($profiles))
			{
				$mfaRequired   = false;
				$user_profiles = $this->getUserProfiles($user->id);

				foreach ($user_profiles as $user_profile)
				{
					if ($user_profile->published == 1 && in_array('applicant', $profiles))
					{
						$mfaRequired = true;
						break;
					}
					elseif (in_array($user_profile->id, $profiles))
					{
						$mfaRequired = true;
						break;
					}
				}

				if ($mfaRequired && $this->needsMultiFactorAuthenticationRedirection())
				{
					$session      = $app->getSession();
					$isMFAPending = $this->isMultiFactorAuthenticationPending();

					if (!$isMFAPending)
					{
						// First unset the flag to make sure the redirection will apply until they conform to the mandatory MFA
						$session->set('com_users.mfa_checked', 0);

						// Now set a flag which forces rechecking MFA for this user
						$session->set('com_users.mandatory_mfa_setup', 1);

						if (!$this->isMultiFactorAuthenticationPage())
						{
							$url = Route::_('index.php?option=com_users&view=methods', false);
							$app->redirect($url, 307);
						}
					}
				}
			}
		}
		// End of 2fa management
	}

	private function getUserProfiles(int $user_id): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$query->clear()
				->select('p.id,p.published,p.status')
				->from($db->quoteName('#__emundus_users', 'eu'))
				->leftJoin($db->quoteName('#__emundus_users_profiles', 'eup') . ' ON eu.user_id = eup.user_id')
				->leftJoin($db->quoteName('#__emundus_setup_profiles', 'p') . ' ON eu.profile = p.id OR eup.profile_id = p.id');
			$query->where($db->quoteName('eu.user_id') . ' = :user_id')
				->bind(':user_id', $user_id, ParameterType::INTEGER);
			$db->setQuery($query);

			return $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			// Handle exception
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return [];
		}
	}

	private function needsMultiFactorAuthenticationRedirection(): bool
	{
		$app     = Factory::getApplication();
		$isAdmin = $app->isClient('administrator');

		/**
		 * We only kick in if the session flag is not set AND the user is not flagged for monitoring of their MFA status
		 *
		 * In case a user belongs to a group which requires MFA to be always enabled and they logged in without having
		 * MFA enabled we have the recheck flag. This prevents the user from enabling and immediately disabling MFA,
		 * circumventing the requirement for MFA.
		 */
		// Make sure we are logged in
		try
		{
			$user = $app->getIdentity();
		}
		catch (\Exception)
		{
			// This would happen if we are in CLI or under an old Joomla! version. Either case is not supported.
			return false;
		}

		// The plugin only needs to kick in when you have logged in
		if (empty($user) || $user->guest)
		{
			return false;
		}

		// If we are in the administrator section we only kick in when the user has backend access privileges
		if ($isAdmin && !$user->authorise('core.login.admin'))
		{
			// @todo How exactly did you end up here if you didn't have the core.login.admin privilege to begin with?!
			return false;
		}

		$option = strtolower($app->input->getCmd('option', ''));
		$task   = strtolower($app->input->getCmd('task', ''));

		// Allow the frontend user to log out (in case they forgot their MFA code or something)
		if (!$isAdmin && ($option == 'com_users') && \in_array($task, ['user.logout', 'user.menulogout']))
		{
			return false;
		}

		// Allow the backend user to log out (in case they forgot their MFA code or something)
		if ($isAdmin && ($option == 'com_login') && ($task == 'logout'))
		{
			return false;
		}

		// Allow the Joomla update finalisation to run
		if ($isAdmin && $option === 'com_joomlaupdate' && \in_array($task, ['update.finalise', 'update.cleanup', 'update.finaliseconfirm']))
		{
			return false;
		}

		// Do not redirect if we are already in a MFA management or captive page
		$onlyCaptive = $this->isMultiFactorAuthenticationPending();

		if ($this->isMultiFactorAuthenticationPage($onlyCaptive))
		{
			return false;
		}

		return true;
	}

	private function isMultiFactorAuthenticationPage(bool $onlyCaptive = false): bool
	{
		$input  = Factory::getApplication()->input;
		$option = $input->get('option');
		$task   = $input->get('task');
		$view   = $input->get('view');

		if ($option !== 'com_users')
		{
			return false;
		}

		$allowedViews = ['captive', 'method', 'methods', 'callback'];
		$allowedTasks = [
			'captive.display', 'captive.captive', 'captive.validate',
			'methods.display',
		];

		if (!$onlyCaptive)
		{
			$allowedTasks = array_merge(
				$allowedTasks,
				[
					'method.display', 'method.add', 'method.edit', 'method.regenerateBackupCodes',
					'method.delete', 'method.save', 'methods.disable', 'methods.doNotShowThisAgain',
				]
			);
		}

		return \in_array($view, $allowedViews) || \in_array($task, $allowedTasks);
	}

	private function isMultiFactorAuthenticationPending(): bool
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if (empty($user) || $user->guest)
		{
			return false;
		}

		// Get the user's MFA records
		$records = MfaHelper::getUserMfaRecords($user->id);

		// No MFA Methods? Then we obviously don't need to display a Captive login page.
		if (\count($records) < 1)
		{
			return false;
		}

		// Let's get a list of all currently active MFA Methods
		$mfaMethods = MfaHelper::getMfaMethods();

		// If no MFA Method is active we can't really display a Captive login page.
		if (empty($mfaMethods))
		{
			return false;
		}

		// Get a list of just the Method names
		$methodNames = [];

		foreach ($mfaMethods as $mfaMethod)
		{
			$methodNames[] = $mfaMethod['name'];
		}

		// Filter the records based on currently active MFA Methods
		foreach ($records as $record)
		{
			if (\in_array($record->method, $methodNames))
			{
				// We found an active Method. Show the Captive page.
				return true;
			}
		}

		// No viable MFA Method found. We won't show the Captive page.
		return false;
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
