<?php

namespace Tchooz\Enums\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

enum ApplicationFileActionsEnum: string
{
	case RENAME = 'rename';
	case COPY = 'copy';
	case DOCUMENTS = 'documents';
	case HISTORY = 'history';
	case COLLABORATE = 'collaborate';
	case ANONYMOUS = 'anonymous';
	case CUSTOM = 'custom';
	case DELETE = 'delete';
	case TRANSACTION = 'transaction';
	case OPENFILE = 'openfile';

	case MOVE_TO_TAB = 'move_to_tab';
	case CREATE_TAB = 'create_tab';

	case UNANONYMIZE = 'unanonymize';
	public function getLabel(): string
	{
		return match($this) {
			self::OPENFILE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_OPENFILE'),
			self::RENAME => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_RENAME'),
			self::COPY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COPY'),
			self::DOCUMENTS => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DOCUMENTS'),
			self::HISTORY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_HISTORY'),
			self::COLLABORATE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COLLABORATE'),
			self::DELETE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DELETE'),
			self::CUSTOM => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_CUSTOM'),
			self::TRANSACTION => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_TRANSACTION'),
			self::MOVE_TO_TAB => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_MOVE_TO_TAB'),
			self::CREATE_TAB => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_CREATE_TAB'),
			self::UNANONYMIZE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_UNANONYMIZE'),
		};
	}

	public function getIcon(): string
	{
		return match($this) {
			self::OPENFILE => 'open_in_new',
			self::RENAME => 'drive_file_rename_outline',
			self::COPY => 'file_copy',
			self::DOCUMENTS => 'description',
			self::HISTORY => 'history',
			self::COLLABORATE => 'collaborate',
			self::DELETE => 'delete',
			self::CUSTOM => 'rule_settings',
			self::TRANSACTION => 'universal_currency',
			self::MOVE_TO_TAB => 'tab_move',
			self::CREATE_TAB => 'tab_new_right',
			self::UNANONYMIZE => 'visibility_lock',
		};
	}

	public function getParameters(): array
	{
		$parameters = [];

		switch ($this)
		{
			case self::RENAME:
				$parameters[] = new StringField('name', Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_RENAME_PARAM'), true);
				break;

			case self::COPY:
				$campaignRepository = new CampaignRepository(false);
				$ongoingCampaigns = $campaignRepository->getOngoingCampaigns();

				$choices = array_map(function ($campaign) {
					return new ChoiceFieldValue((string) $campaign->getId(), $campaign->getLabel());
				}, $ongoingCampaigns);

				$parameters[] = new ChoiceField('campaign_id', Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COPY_CAMPAIGN_PARAM'), $choices, true);
				break;

			case self::MOVE_TO_TAB:
				if (!class_exists('EmundusModelApplication'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
				}
				$applicationModel = new \EmundusModelApplication();
				$tabs = $applicationModel->getTabs(Factory::getApplication()->getIdentity()->id);

				$choices = array_map(function ($tab) {
					return new ChoiceFieldValue($tab['id'], $tab['name']);
				}, $tabs);

				$parameters[] = new ChoiceField('tab', Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_TAB_PARAM'), $choices, true);
				break;

			case self::CREATE_TAB:
				$parameters[] = new StringField('name', Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_TAB_NAME_PARAM'), true);
				break;
			default:
				break;
		}

		return $parameters;
	}

	public function getOrdering(): int
	{
		return match($this)
		{
			self::OPENFILE => 0,
			self::RENAME => 1,
			self::MOVE_TO_TAB => 2,
			self::CREATE_TAB => 2,
			self::COPY => 3,
			self::DOCUMENTS => 4,
			self::HISTORY => 5,
			self::COLLABORATE => 6,
			self::TRANSACTION => 8,
			self::CUSTOM => 9,
			self::UNANONYMIZE => 10,
			self::DELETE => 99,
		};
	}

	public function isAvailable(): bool
	{
		$available = true;

		switch ($this)
		{
			case self::TRANSACTION:
				$addonRepository = new AddonRepository();
				$addon = $addonRepository->getByName(AddonEnum::PAYMENT->value);

				if (!$addon->isActivated())
				{
					$available = false;
				}
				break;
			case self::CREATE_TAB:
				if (!class_exists('EmundusModelApplication'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
				}
				$applicationModel = new \EmundusModelApplication();
				$tabs = $applicationModel->getTabs(Factory::getApplication()->getIdentity()->id);
				$available = empty($tabs);
				break;
			case self::MOVE_TO_TAB:
				if (!class_exists('EmundusModelApplication'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
				}
				$applicationModel = new \EmundusModelApplication();
				$tabs = $applicationModel->getTabs(Factory::getApplication()->getIdentity()->id);
				$available = !empty($tabs);
				break;
			case self::UNANONYMIZE:
				$addonRepository = new AddonRepository();
				$addon = $addonRepository->getByName(AddonEnum::ANONYMOUS->value);
				if (!$addon->isActivated())
				{
					$available = false;
				}
				break;
			default:
				$available = true;
		}

		return $available;
	}

	public function getClass(): string
	{
		return match($this)
		{
			self::UNANONYMIZE, self::DELETE => 'tw-text-red-500',
			default => ''
		};
	}
}
