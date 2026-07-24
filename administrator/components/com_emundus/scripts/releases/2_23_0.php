<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Tchooz\Entities\Automation\Actions\ActionRedirect;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\QueryInterface;
use scripts\ReleaseInstaller;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Poll\PollAnswerEntity;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Addons\AddonRepository;

class Release2_23_0Installer extends ReleaseInstaller
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
			$repository = new SynchronizerRepository();
			$payzen     = $repository->getByType('payzen');

			if (empty($payzen))
			{
				$config = [
					'authentication' => [
						'client_id'     => '',
						'client_secret' => '',
					],
					'endpoint'       => 'https://secure.payzen.eu/vads-payment/',
					'mode'           => 'TEST',
					'return_url'     => '',
					'configuration'  => [
						'endpoint' => 'https://secure.payzen.eu/vads-payment/',
						'mode'     => 'TEST',
					],
				];

				$payzen = new SynchronizerEntity(
					0,
					'payzen',
					'PayZen',
					'Paiement via le service PayZen',
					[],
					$config,
					false,
					false,
					'payzen.svg'
				);

				$this->tasks[] = $repository->flush($payzen);
			}

			if (!empty($payzen) && !empty($payzen->getId()))
			{
				$this->tasks[] = $this->associatePaymentMethod('CB', $payzen->getId());
				$this->tasks[] = $this->associatePaymentMethod('sepa', $payzen->getId());
			}

			$this->rebuildRedirectAutomationsActions();

			// Free-text location for poll/event slots.
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_setup_event_slots', 'location_text', 'VARCHAR', 255, 1)['status'];

			$this->initPollFeature($query);

			$result['status'] = !in_array(false, $this->tasks, true);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function associatePaymentMethod(string $methodName, int $serviceId): bool
	{
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_setup_payment_method'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($methodName));
		$this->db->setQuery($query);
		$methodId = $this->db->loadResult();

		if (empty($methodId))
		{
			return true;
		}

		$query->clear()
			->select('payment_method_id')
			->from($this->db->quoteName('#__emundus_setup_payment_method_sync'))
			->where($this->db->quoteName('payment_method_id') . ' = ' . $this->db->quote($methodId))
			->andWhere($this->db->quoteName('service_id') . ' = ' . $this->db->quote($serviceId));
		$this->db->setQuery($query);

		if (!empty($this->db->loadResult()))
		{
			return true;
		}

		$association                    = new \stdClass();
		$association->payment_method_id = $methodId;
		$association->service_id        = $serviceId;

		return $this->db->insertObject('#__emundus_setup_payment_method_sync', $association);
	}

	private function rebuildRedirectAutomationsActions(): void
	{
		$query = $this->db->createQuery();

		$query->select('id, params')
			->from($this->db->quoteName('#__emundus_action'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote('redirect'));
		$this->db->setQuery($query);
		$redirectActions = $this->db->loadObjectList();

		foreach ($redirectActions as $redirectAction)
		{
			$params = json_decode($redirectAction->params);

			// Same priority that execute method in ActionRedirect class
			if (!empty($params->known_url))
			{
				$params->url_type = ActionRedirect::KNOWN_URL;
			}
			elseif (!empty($params->custom_url))
			{
				$params->url_type = ActionRedirect::CUSTOM_URL;
			}
			else
			{
				$params->url_type = ActionRedirect::INTERN_URL;
			}

			$redirectAction->params = json_encode($params);
			$this->tasks[]          = $this->db->updateObject('#__emundus_action', $redirectAction, 'id');
		}
	}

	private function initPollFeature(QueryInterface $query): void
	{
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('topmenu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=polls&layout=reply'));
		$this->db->setQuery($query);
		$pollsTopMenuId = $this->db->loadResult();
		if (empty($pollsTopMenuId))
		{
			$data      = [
				'menutype'          => 'topmenu',
				'title'             => 'Sondages',
				'alias'             => 'my-polls',
				'path'              => 'my-polls',
				'link'              => 'index.php?option=com_emundus&view=polls&layout=reply',
				'type'              => 'component',
				'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
				'access'            => AccessLevelEnum::PUBLIC->value,
				'template_style_id' => 0,
				'params'            => [
					'menu_image_css' => 'library_add_check',
					'menu_show' => 0
				],
			];
			$pollsMenu = \EmundusHelperUpdate::addJoomlaMenu($data, 1, 0);
			if ($this->tasks[] = $pollsMenu['status'])
			{
				$pollsTopMenuId = $pollsMenu['id'];
				\EmundusHelperUpdate::insertFalangTranslation(1, $pollsTopMenuId, 'menu', 'title', 'Group polls');
			}
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=polls'));
		$this->db->setQuery($query);
		$pollsMenuId = $this->db->loadResult();
		if (empty($pollsMenuId))
		{
			$data      = [
				'menutype'          => 'onboardingmenu',
				'title'             => 'Sondages',
				'alias'             => 'polls',
				'path'              => 'polls',
				'link'              => 'index.php?option=com_emundus&view=polls',
				'type'              => 'component',
				'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
				'access'            => AccessLevelEnum::COORDINATOR->value,
				'template_style_id' => 0,
				'params'            => [
					'menu_image_css' => 'library_add_check'
				],
			];
			$pollsMenu = \EmundusHelperUpdate::addJoomlaMenu($data, 1, 0);
			if ($this->tasks[] = $pollsMenu['status'])
			{
				$pollsMenuId = $pollsMenu['id'];
				\EmundusHelperUpdate::insertFalangTranslation(1, $pollsMenuId, 'menu', 'title', 'Group polls');
			}
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=polls&layout=add'));
		$this->db->setQuery($query);
		$addPollMenuId = $this->db->loadResult();
		if (empty($addPollMenuId))
		{
			$data        = [
				'menutype'          => 'onboardingmenu',
				'title'             => 'Créer un sondage',
				'alias'             => 'create-poll',
				'path'              => 'polls/create-poll',
				'link'              => 'index.php?option=com_emundus&view=polls&layout=add',
				'type'              => 'component',
				'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
				'access'            => AccessLevelEnum::COORDINATOR->value,
				'template_style_id' => 0,
				'params'            => [],
			];
			$addPollMenu = \EmundusHelperUpdate::addJoomlaMenu($data, $pollsMenuId, 0);
			if ($this->tasks[] = $addPollMenu['status'])
			{
				$addPollMenuId = $addPollMenu['id'];
				\EmundusHelperUpdate::insertFalangTranslation(1, $addPollMenuId, 'menu', 'title', 'Create a poll');
			}
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=polls&layout=edit'));
		$this->db->setQuery($query);
		$editPollMenuId = $this->db->loadResult();
		if (empty($editPollMenuId))
		{
			$data         = [
				'menutype'          => 'onboardingmenu',
				'title'             => 'Modifier un sondage',
				'alias'             => 'edit-poll',
				'path'              => 'polls/edit-poll',
				'link'              => 'index.php?option=com_emundus&view=polls&layout=edit',
				'type'              => 'component',
				'access'            => AccessLevelEnum::COORDINATOR->value,
				'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
				'template_style_id' => 0,
				'params'            => [],
			];
			$editPollMenu = \EmundusHelperUpdate::addJoomlaMenu($data, $pollsMenuId, 0);
			if ($this->tasks[] = $editPollMenu['status'])
			{
				$editPollMenuId = $editPollMenu['id'];
				\EmundusHelperUpdate::insertFalangTranslation(1, $editPollMenuId, 'menu', 'title', 'Edit a poll');
			}
		}

		$addonRepository = new AddonRepository();
		$pollAddon       = $addonRepository->getByName('poll');
		if (empty($pollAddon))
		{
			$params        = [
				'configuration' => [
					'run_email_subject' => 'Vous êtes invité(e) à participer au sondage : {poll}',
					'run_email_body'    => "<p>Bonjour {name},</p><p></p><p>Nous avons le plaisir de vous inviter à participer au sondage <strong>{poll}</strong>.</p><p>{description}</p><p></p><p>Votre réponse ne vous prendra que quelques minutes et nous sera très précieuse.</p><p></p><p><a target='_blank' rel='noopener noreferrer nofollow' href='{siteurl}'>Répondre au sondage</a></p><p></p><p>Nous vous remercions par avance pour votre contribution.</p><p>Bien cordialement, </p><p>L'équipe {sitename}</p>",
					'close_email_subject' => "Le sondage {poll} est désormais clôturé",
					'close_email_body'    => "<p>Bonjour {name},</p><p></p><p>Le sondage <strong>{poll}</strong> est désormais clôturé.</p><p>{description}</p><p>Merci pour votre participation.</p><p>Cordialement,<br />L'équipe {sitename}</p>"
				]
			];
			$pollAddon     = new AddonEntity('poll', false, false, true, $params);
			$this->tasks[] = $addonRepository->flush($pollAddon);
		}

		$this->tasks[] = \EmundusHelperUpdate::makeFromEntity(PollEntity::class);
		$this->tasks[] = \EmundusHelperUpdate::makeFromEntity(PollParticipantsEntity::class);
		$this->tasks[] = \EmundusHelperUpdate::makeFromEntity(PollAnswerEntity::class);

		$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_setup_event_slots', 'poll', 'INT', 11)['status'];

		$queryString = 'alter table jos_emundus_setup_event_slots modify event int null;';
		$this->db->setQuery($queryString);
		$this->tasks[] = $this->db->execute();

		// Scheduled task that opens/closes polls based on their start and end dates.
		$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_task_managepolls', 'managepolls', null, 'plugin', 1, 'task');

		$execution_rules = [
			'rule-type'      => 'interval-hours',
			'interval-hours' => '1',
			'exec-day'       => date('d'),
			'exec-time'      => '00:00',
		];
		$cron_rules      = [
			'type' => 'interval',
			'exp'  => 'PT1H',
		];

		// Unpublished by default: enabling the Poll addon publishes this task (see PollAddonHandler).
		$this->tasks[] = \EmundusHelperUpdate::createSchedulerTask('Open and close polls based on their start and end dates', 'plg_task_managepolls', $execution_rules, $cron_rules, [], $pollAddon->isActivated() ? 1 : 0);

		$columns      = [
			[
				'name' => 'poll',
				'type' => 'int',
				'null' => 0,
			],
			[
				'name' => 'program',
				'type' => 'int',
				'null' => 0,
			],
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_setup_polls_programs_fk_poll',
				'from_column'    => 'poll',
				'ref_table'      => 'jos_emundus_setup_polls',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true,
			],
			[
				'name'           => 'jos_emundus_setup_polls_programs_fk_program',
				'from_column'    => 'program',
				'ref_table'      => 'jos_emundus_setup_programmes',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true,
			],
		];

		$table = \EmundusHelperUpdate::createTable(
			'jos_emundus_setup_polls_programs',
			$columns,
			$foreign_keys,
			'Association des sondages aux programmes'
		);

		$this->tasks[] = $table['status'];
	}
}