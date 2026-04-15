<?php

namespace Tchooz\Enums\ApplicationFile;

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

	public function getLabel(): string
	{
		return match($this) {
			self::OPENFILE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_OPENFILE'),
			self::RENAME => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_RENAME'),
			self::COPY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COPY'),
			self::DOCUMENTS => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DOCUMENTS'),
			self::HISTORY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_HISTORY'),
			self::COLLABORATE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COLLABORATE'),
			self::ANONYMOUS => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_ANONYMOUS'),
			self::DELETE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DELETE'),
			self::CUSTOM => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_CUSTOM'),
			self::TRANSACTION => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_TRANSACTION'),
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
			self::ANONYMOUS => 'domino_mask',
			self::DELETE => 'delete',
			self::CUSTOM => 'rule_settings',
			self::TRANSACTION => 'universal_currency',
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
			self::COPY => 2,
			self::DOCUMENTS => 3,
			self::HISTORY => 4,
			self::COLLABORATE => 5,
			self::ANONYMOUS => 6,
			self::TRANSACTION => 7,
			self::CUSTOM => 8,
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
			default:
				$available = true;
		}

		return $available;
	}
}
