<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\ApplicationFile\TagsRepository;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class SylviaIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	public const STATUSES_TO_SYNC_PARAMETER = 'statuses_to_sync';
	public const TAG_NOT_FOUND = 'tag_not_found';
	public const TAG_DUPLICATE = 'tag_duplicate';

	public function getParameters(): array
	{
		$authGroup   = new FieldGroup('authentication', Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_AUTHENTICATION_GROUP_LABEL'));
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_CONFIGURATION_GROUP_LABEL'));

		return [
			new PasswordField('api_key', Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_API_KEY_LABEL'), true, $authGroup),
			new StringField('base_url', Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_BASE_URL_LABEL'), true, $configGroup),
			new StringField('api_path', Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_API_PATH_LABEL'), false, $configGroup),
			new ChoiceField(
				self::STATUSES_TO_SYNC_PARAMETER,
				Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_STATUSES_TO_SYNC_LABEL'),
				$this->getStatusChoices(),
				true,
				true,
				$configGroup
			),
			new ChoiceField(
				self::TAG_NOT_FOUND,
				Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_TAG_NOT_FOUND_LABEL'),
				$this->getStickersChoices(),
				false,
				false,
				$configGroup
			),
			new ChoiceField(
				self::TAG_DUPLICATE,
				Text::_('COM_EMUNDUS_INTEGRATIONS_SYLVIA_TAG_DUPLICATE_LABEL'),
				$this->getStickersChoices(),
				false,
				false,
				$configGroup
			),
		];
	}

	public function getDefaultParameters(): array
	{
		return [];
	}

	/**
	 * Build the list of application-file statuses offered in the "Statuts à synchroniser" field.
	 * The stored value is the status "step" (as persisted on jos_emundus_campaign_candidature.status).
	 *
	 * @return ChoiceFieldValue[]
	 */
	private function getStatusChoices(): array
	{
		$choices = [];

		try
		{
			foreach ((new StatusRepository())->getAll() as $status)
			{
				$choices[] = new ChoiceFieldValue($status->getStep(), $status->getLabel());
			}
		}
		catch (\Throwable $e)
		{
			Log::add('Error loading statuses for Sylvia configuration: ' . $e->getMessage(), Log::ERROR, 'com_emundus.sylvia');
		}

		return $choices;
	}

	private function getStickersChoices(): array
	{
		$choices = [];

		try
		{
			foreach ((new TagsRepository())->get() as $sticker)
			{
				$choices[] = new ChoiceFieldValue($sticker->getId(), $sticker->getLabel());
			}
		}
		catch (\Throwable $e)
		{
			Log::add('Error loading statuses for Sylvia configuration: ' . $e->getMessage(), Log::ERROR, 'com_emundus.sylvia');
		}

		return $choices;
	}
}
