<?php

namespace scripts;

use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\AddonHandlerResolver;

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

			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_system_emunduspublicaccess', 'emunduspublicaccess', null, 'plugin', 0, 'system');
			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_emundus_anonymization', 'anonymization', null, 'plugin', 0, 'emundus');

			$resolver           = new AddonHandlerResolver();
			$addonRepository    = new AddonRepository();
			$publicSessionAddon = $addonRepository->getByName('public_session');
			if (empty($publicSessionAddon))
			{
				$publicSessionAddon = new AddonEntity('public_session', false, false, false);
				$handler            = $resolver->resolve('public_session', $publicSessionAddon);
				$params             = [];
				foreach ($handler->getParameters() as $parameter)
				{
					$params[$parameter->getName()] = null;
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

			$addAnonymizationPolicy = EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'anonymization_policy', 'VARCHAR', 20, 1, AnonymizationPolicyEnum::GLOBAL->value);
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

			$eventsAdded   = EmundusHelperUpdate::addCustomEvents([
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

			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_PUBLIC_ACCESS_INVALID_TOKEN', 'La clé d\'accès est invalide ou a expiré. Veuillez vérifier votre clé et réessayer.');
			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_PUBLIC_ACCESS_INVALID_TOKEN', 'The access key is invalid or has expired. Please check your key and try again.', 'override', 0, null, null, 'en-GB');
			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FILE_SUBMITTED_PUBLIC_ACCESS_MESSAGE', 'Votre candidature a été soumise avec succès.');
			\EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FILE_SUBMITTED_PUBLIC_ACCESS_MESSAGE', 'Your application has been successfully submitted.', 'override', 0, null, null, 'en-GB');

		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}