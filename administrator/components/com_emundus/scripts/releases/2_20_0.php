<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\AddonHandlerResolver;
use Tchooz\Enums\Fabrik\ElementPluginEnum;

class Release2_20_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$campaignColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'public', 'TINYINT', 1, 0, 0);
			$this->tasks[] = $campaignColumn['status'];
			if(!$campaignColumn['status'])
			{
				$result['message'] .= $campaignColumn['message'];
			}

			$appFilePublicColumn = \EmundusHelperUpdate::addColumn('jos_emundus_campaign_candidature', 'public', 'TINYINT', 1, 0, 0);
			$this->tasks[] = $appFilePublicColumn['status'];
			if(!$appFilePublicColumn['status'])
			{
				$result['message'] .= $appFilePublicColumn['message'];
			}

			$appFileAnonymousColumn = \EmundusHelperUpdate::addColumn('jos_emundus_campaign_candidature', 'anonymous', 'TINYINT', 1, 0, 0);
			$this->tasks[] = $appFileAnonymousColumn['status'];
			if(!$appFileAnonymousColumn['status'])
			{
				$result['message'] .= $appFileAnonymousColumn['message'];
			}

			$query->select('params')
				->from('#__modules')
				->where('module = ' . $this->db->quote('mod_emundus_applications'))
				->andWhere('published = 1');

			$eventsAdded   = \EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAskForAnonymousReveal', 'published' => 1, 'category' => 'File', 'available' => 0]
			]);
			$this->tasks[] = $eventsAdded['status'];
			if (!$eventsAdded['status'])
			{
				$result['message'] .= $eventsAdded['message'];
			}

			$query->clear()
				->select('parent_id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&controller=files&task=getstate'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('actions'));
			$this->db->setQuery($query);
			$params = $this->db->loadResult();

			if (!empty($params))
			{
				$params = json_decode($params, true);

				if (!empty($params['mod_emundus_applications_actions']))
				{
					$config = ComponentHelper::getComponent('com_emundus')->getParams();

					if (in_array('rename', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_rename', 1);
					}
					else
					{
						$config->set('action_rename', 0);
					}

					if (in_array('copy', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_copy', 1);
					}
					else
					{
						$config->set('action_copy', 0);
					}

					if (in_array('documents', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_documents', 1);
					}
					else
					{
						$config->set('action_documents', 0);
					}

					if (in_array('history', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_history', 1);
					}
					else
					{
						$config->set('action_history', 0);
					}

					$componentId = ComponentHelper::getComponent('com_emundus')->id;

					$query->clear()
						->update($this->db->quoteName('#__extensions'))
						->set($this->db->quoteName('params') . ' = ' . $this->db->quote($config->toString()))
						->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($componentId));

					$this->db->setQuery($query);
					$this->tasks[] = $this->db->execute();
				}
			}

			$tableCreated  = \EmundusHelperUpdate::createTable('jos_emundus_file_access', [
				new \EmundusTableColumn('ccid', \EmundusColumnTypeEnum::INT, 11, false, null),
				new \EmundusTableColumn('token', \EmundusColumnTypeEnum::VARCHAR, 100, false, null),
				new \EmundusTableColumn('expiration_date', \EmundusColumnTypeEnum::DATETIME, null, false, null),
			],
				[
					new \EmundusTableForeignKey('jos_emundus_file_access_ccid_fk', 'ccid', 'jos_emundus_campaign_candidature', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
				],
				'',
				[
					['name' => 'jos_emundus_file_access_ccid_idx', 'columns' => ['ccid']]
				]
			);
			$this->tasks[] = $tableCreated['status'];
			if (!$tableCreated['status'])
			{
				$result['message'] .= $tableCreated['message'];
			}

			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_system_emunduspublicaccess', 'emunduspublicaccess', null, 'plugin', 1, 'system');
			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_emundus_anonymization', 'anonymization', null, 'plugin', 0, 'emundus');

			$resolver           = new AddonHandlerResolver();
			$addonRepository    = new AddonRepository();
			$publicSessionAddon = $addonRepository->getByName(AddonEnum::PUBLIC_SESSION->value);
			if (empty($publicSessionAddon))
			{
				$publicSessionAddon = new AddonEntity(AddonEnum::PUBLIC_SESSION->value, false, false, false);
				$handler            = $resolver->resolve(AddonEnum::PUBLIC_SESSION->value, $publicSessionAddon);
				$params             = [];
				foreach ($handler->getParameters() as $parameter)
				{
					if ($parameter->getName() === 'token_validity_duration')
					{
						$params[$parameter->getName()] = 30;
					}
					else
					{
						$params[$parameter->getName()] = 1;
					}
				}
				$publicSessionAddon->setParams($params);

				$this->tasks[] = $addonRepository->flush($publicSessionAddon);
			}

			$addonRepository = new AddonRepository();
			$anonymAddon     = $addonRepository->getByName('anonymous');
			if (!empty($anonymAddon))
			{
				if (!isset($anonymAddon->getParams()['policy']))
				{
					$handler = $resolver->resolve('anonymous', $publicSessionAddon);
					$params  = [];
					foreach ($handler->getParameters() as $parameter)
					{
						$value = null;
						if ($parameter->getName() === 'policy')
						{
							$value = AnonymizationPolicyEnum::OPTIONAL;
						}
						$params[$parameter->getName()] = $value;
					}
					$anonymAddon->setParams($params);

					$this->tasks[] = $addonRepository->flush($anonymAddon);
				}
			}

			$addAnonymizationPolicy = \EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'anonymization_policy', 'VARCHAR', 20, 1, AnonymizationPolicyEnum::GLOBAL->value);
			$this->tasks[]          = $addAnonymizationPolicy['status'];
			if (!$addAnonymizationPolicy['status'])
			{
				$result['message'] .= $addAnonymizationPolicy['message'];
			}

			$actionRepository      = new ActionRepository(false);
			$anonymousRevealAction = $actionRepository->getByName('anonymous_reveal');
			if (empty($anonymousRevealAction))
			{
				$anonymousRevealAction = new ActionEntity(0, 'anonymous_reveal', Text::_('COM_EMUNDUS_ACL_ANONYMIZATION_REVEAL'), new CrudEntity(0, 1, 0, 0, 0), 30, false, 'COM_EMUNDUS_ACL_ANONYMIZATION_REVEAL_DESC');
				if (!$actionRepository->flush($anonymousRevealAction))
				{
					$this->tasks[]     = false;
					$result['message'] .= 'Failed to create new action ' . $anonymousRevealAction->getName() . '. ';
				}
			}

			$eventsAdded   = \EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAskForAnonymousReveal', 'published' => 1, 'category' => 'File', 'available' => 0]
			]);
			$this->tasks[] = $eventsAdded['status'];
			if (!$eventsAdded['status'])
			{
				$result['message'] .= $eventsAdded['message'];
			}

			$query->clear()
				->select('parent_id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&controller=files&task=getstate'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('actions'));
			$this->db->setQuery($query);
			$parent_id = $this->db->loadResult();

			$datas       = [
				'menutype'     => 'actions',
				'title'        => 'Demander la désanonymisation du dossier',
				'alias'        => 'ask-for-reveal',
				'link'         => 'index.php?option=com_emundus&view=application',
				'type'         => 'url',
				'component_id' => 0,
				'note'         => 'anonymous_reveal|c|1'
			];
			$reveal_menu = \EmundusHelperUpdate::addJoomlaMenu($datas, $parent_id, 0);
			if ($this->tasks[] = $reveal_menu['status'])
			{
				$this->tasks[] = \EmundusHelperUpdate::insertFalangTranslation(1, $reveal_menu['id'], 'menu', 'title', 'Demander la désanonymisation du dossier', true);
			}

			$datas = [
				'menutype'     => 'topmenu',
				'title'        => 'Sauvegarder ses informations d\'accès',
				'alias'        => 'store-token',
				'link'         => 'index.php?option=com_emundus&view=publicaccess&layout=storetoken',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'menu_show'    => 0
			];
			$storeTokenMenu = \EmundusHelperUpdate::addJoomlaMenu($datas, 0, 0);
			if ($this->tasks[] = $storeTokenMenu['status'])
			{
				$this->tasks[] = \EmundusHelperUpdate::insertFalangTranslation(1, $storeTokenMenu['id'], 'menu', 'title', 'Sauvegarder ses informations d\'accès', true);
			}

			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_PUBLIC_ACCESS_INVALID_TOKEN', 'La clé d\'accès est invalide ou a expiré. Veuillez vérifier votre clé et réessayer.');
			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_PUBLIC_ACCESS_INVALID_TOKEN', 'The access key is invalid or has expired. Please check your key and try again.', 'override', 0, null, null, 'en-GB');
			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FILE_SUBMITTED_PUBLIC_ACCESS_MESSAGE', 'Votre candidature a été soumise avec succès.');
			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FILE_SUBMITTED_PUBLIC_ACCESS_MESSAGE', 'Your application has been successfully submitted.', 'override', 0, null, null, 'en-GB');

			$systemUserId = (int) ComponentHelper::getParams('com_emundus')->get('system_public_user_id', 0);
			if (empty($systemUserId))
			{
				require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
				require_once(JPATH_SITE . '/components/com_emundus/helpers/users.php');

				$h_users = new \EmundusHelperUsers();
				$m_users = new \EmundusModelUsers();

				$other_param = [
					'firstname'    => 'Public',
					'lastname'     => 'SYSTEM',
					'profile'      => 1000,
					'em_oprofiles' => '',
					'univ_id'      => 0,
					'em_groups'    => [],
					'em_campaigns' => [],
					'news'         => 0,
					'is_anonym'    => 1,
				];

				$user           = clone(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0));
				$user->name     = 'Public SYSTEMACCOUNT';
				$user->username = 'system-public@emundus.fr';
				$user->email    = 'system-public@emundus.fr';

				$password       = $h_users->generateStrongPassword(30);
				$user->password = UserHelper::hashPassword($password);

				$now                 = \EmundusHelperDate::getNow();
				$user->registerDate  = $now;
				$user->lastvisitDate = null;
				$user->block         = 1;
				$user->authProvider  = '';

				$acl_aro_groups = $m_users->getDefaultGroup(1000);
				$user->groups   = $acl_aro_groups;

				$usertype       = $m_users->found_usertype($acl_aro_groups[0]);
				$user->usertype = $usertype;

				$systemUserId = $m_users->adduser($user, $other_param);
				if (!empty($systemUserId))
				{
					\EmundusHelperUpdate::updateComponentParameter('com_emundus', 'system_public_user_id', $systemUserId);
				}
				else
				{
					$this->tasks[]     = false;
					$result['message'] .= 'Failed to create system public user. ';
				}
			}

			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_emundus_anonymization', 'anonymization', null, 'plugin', 1, 'emundus');
			$this->tasks[] = \EmundusHelperUpdate::enableEmundusPlugins('anonymization', 'plugin');
			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_fabrik_element_' . ElementPluginEnum::EMUNDUSREADONLY->value, ElementPluginEnum::EMUNDUSREADONLY->value, null, 'plugin', 1, 'fabrik_element');

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
