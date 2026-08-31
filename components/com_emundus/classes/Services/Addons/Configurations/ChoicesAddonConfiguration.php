<?php

namespace Tchooz\Services\Addons\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Services\Addons\EmundusAddonConfiguration;

class ChoicesAddonConfiguration extends EmundusAddonConfiguration
{
	public const CONFIGURATION_GROUP = 'configuration';

	/**
	 * When enabled, choices stay editable outside the phases the choices step is bound to.
	 */
	public const APPLICANT_CAN_UPDATE_ANYTIME = 'applicant_can_update_anytime';

	/**
	 * Rules applied while editing outside the phases. Inside a phase the choices step keeps ruling: its
	 * values are the ones the applicant was given, and reusing them out of context would silently apply
	 * the selection phase rules to a file that has left it.
	 */
	public const DEFAULT_MAX = 'default_max';
	public const DEFAULT_CAN_BE_ORDERING = 'default_can_be_ordering';
	public const DEFAULT_CAN_BE_CONFIRMED = 'default_can_be_confirmed';
	public const DEFAULT_CAN_BE_SENT = 'default_can_be_sent';

	/**
	 * @var array<ChoiceFieldValue>
	 */
	private array $statusChoices = [];

	public function getParameters(): array
	{
		$configGroup = new FieldGroup(self::CONFIGURATION_GROUP, Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONFIGURATION_GROUP_LABEL'));

		return [
			new ChoiceField('status_when_refused', Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_STATUS_REFUSED_LABEL'), $this->getStatusChoices(), false, false, $configGroup),
			new ChoiceField('status_when_accepted', Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_STATUS_ACCEPTED_LABEL'), $this->getStatusChoices(), false, false, $configGroup),
			new BooleanField(self::APPLICANT_CAN_UPDATE_ANYTIME, Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_APPLICANT_CAN_UPDATE_ANYTIME_LABEL'), false, $configGroup),
			new NumericField(self::DEFAULT_MAX, Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_DEFAULT_MAX_LABEL'), false, $configGroup),
			new BooleanField(self::DEFAULT_CAN_BE_ORDERING, Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_DEFAULT_CAN_BE_ORDERING_LABEL'), false, $configGroup),
			new BooleanField(self::DEFAULT_CAN_BE_CONFIRMED, Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_DEFAULT_CAN_BE_CONFIRMED_LABEL'), false, $configGroup),
			new BooleanField(self::DEFAULT_CAN_BE_SENT, Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_DEFAULT_CAN_BE_SENT_LABEL'), false, $configGroup)
		];
	}

	public function getDefaultParameters(): array
	{
		return [];
	}

	private function getStatusChoices(): array
	{
		if (!empty($this->statusChoices))
		{
			return $this->statusChoices;
		} else {
			$statusRepository = new StatusRepository();
			$states = $statusRepository->getAll();

			$choices = [];
			foreach ($states as $state)
			{
				$choices[] = new ChoiceFieldValue($state->getStep(), $state->getLabel());
			}

			$this->statusChoices = $choices;
		}

		return $this->statusChoices;
	}
}