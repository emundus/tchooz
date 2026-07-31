<?php

namespace Tchooz\Services\Addons\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Services\Addons\EmundusAddonConfiguration;

class ChoicesAddonConfiguration extends EmundusAddonConfiguration
{
	/**
	 * @var array<ChoiceFieldValue>
	 */
	private array $statusChoices = [];

	public function getParameters(): array
	{
		$configGroup = new FieldGroup('configuration', Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONFIGURATION_GROUP_LABEL'));

		return [
			new ChoiceField('status_when_refused', Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_STATUS_REFUSED_LABEL'), $this->getStatusChoices(), false, false, $configGroup),
			new ChoiceField('status_when_accepted', Text::_('COM_EMUNDUS_CHOICES_ADDON_PARAMETER_STATUS_ACCEPTED_LABEL'), $this->getStatusChoices(), false, false, $configGroup)
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