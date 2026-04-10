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
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Addons\AddonRepository;

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

		$this->addon->getValue()->setEnabled($state);
		$addonRepository = new AddonRepository();
		$updates[] = $addonRepository->flush($this->addon);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		// Publish/unpublish the anonymization event subscriber plugin
		$query->clear()
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = ' . ($state ? 1 : 0))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('emundus'))
			->where($db->quoteName('element') . ' = ' . $db->quote('anonymization'));

		$db->setQuery($query);
		$updates[] = $db->execute();

		$actionRepository = new ActionRepository(false);
		$anonymousAcl = $actionRepository->getByName('anonymous_reveal');
		if (!empty($anonymousAcl))
		{
			if (!$state)
			{
				$anonymousAcl->setStatus(false);
			}
			else
			{
				$anonymousAcl->setStatus(true);
			}
			$updates[] = $actionRepository->flush($anonymousAcl);
		}
		else
		{
			$updates[] = false;
		}

		$query->clear()
			->update($db->quoteName('#__emundus_plugin_events'))
			->set($db->quoteName('available') . ' = ' . ($state ? 1 : 0))
			->where($db->quoteName('label') . ' = ' . $db->quote('onAskForAnonymousReveal'));

		$db->setQuery($query);
		$updates[] = $db->execute();

		// toggle action menu
		$query->clear()
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('published') . ' = ' . ($state ? 1 : 0))
			->where($db->quoteName('note') . ' = ' . $db->quote('anonymous_reveal|c|1'))
			->andWhere($db->quoteName('menutype') . ' = ' . $db->quote('actions'));

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