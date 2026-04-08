<?php

namespace Tchooz\Enums\ApplicationFile;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\StringField;
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

	public function getLabel(): string
	{
		return match($this) {
			self::RENAME => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_RENAME'),
			self::COPY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COPY'),
			self::DOCUMENTS => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DOCUMENTS'),
			self::HISTORY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_HISTORY'),
			self::COLLABORATE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COLLABORATE'),
			self::ANONYMOUS => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_ANONYMOUS'),
			self::DELETE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DELETE'),
			self::CUSTOM => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_CUSTOM'),
		};
	}

	public function getIcon(): string
	{
		return match($this) {
			self::RENAME => 'drive_file_rename_outline',
			self::COPY => 'file_copy',
			self::DOCUMENTS => 'description',
			self::HISTORY => 'history',
			self::COLLABORATE => 'collaborate',
			self::ANONYMOUS => 'domino_mask',
			self::DELETE => 'delete',
			self::CUSTOM => 'rule_settings'
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
			self::RENAME => 0,
			self::COPY => 1,
			self::DOCUMENTS => 2,
			self::HISTORY => 3,
			self::COLLABORATE => 4,
			self::ANONYMOUS => 5,
			self::DELETE => 6,
			self::CUSTOM => 7,
		};
	}
}
