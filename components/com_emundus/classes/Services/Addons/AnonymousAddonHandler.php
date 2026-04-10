<?php

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Factories\Field\ChoiceFieldFactory;

class AnonymousAddonHandler implements AddonHandlerInterface
{
	private AddonEntity $addon;

	public function __construct(AddonEntity $addon)
	{
		$this->addon = $addon;
	}

	public function toggle(bool $state): bool
	{
		$updates = [];

		$config['enabled'] = $state;

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$query->clear()
			->update($db->quoteName('#__emundus_setup_config'))
			->set($db->quoteName('value') . ' = ' . $db->quote(json_encode($config)))
			->where($db->quoteName('namekey') . ' = ' . $db->quote($this->addon->getNamekey()));

		$db->setQuery($query);
		$updates[] = $db->execute();

		// Publish/unpublish the anonymization event subscriber plugin
		$query->clear()
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = ' . ($state ? 1 : 0))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('emundus'))
			->where($db->quoteName('element') . ' = ' . $db->quote('anonymization'));

		$db->setQuery($query);
		$updates[] = $db->execute();

		return !in_array(false, $updates, true);
	}

	/**
	 * @return array<Field>
	 */
	public function getParameters(): array
	{
		$cases = array_filter(
			AnonymizationPolicyEnum::cases(),
			fn(AnonymizationPolicyEnum $case) => $case !== AnonymizationPolicyEnum::GLOBAL
		);
		$choices = ChoiceFieldFactory::makeOptionsFromEnum($cases);

		return [
			new ChoiceField('policy', Text::_('COM_EMUNDUS_CAMPAIGN_ANONYMISATION_LABEL'), $choices),
			new YesnoField('display_option_in_campaign_apply', Text::_('COM_EMUNDUS_CAMPAIGN_ANONYMISATION_DISPLAY_OPTION_IN_CAMPAIGN_APPLY_LABEL')),
		];
	}
}