<?php
/**
 * @package     Tchooz\Entities\Emails\Providers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails\Providers;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Entities\Emails\Modifiers\ChoiceStatusModifier;
use Tchooz\Entities\Emails\Modifiers\IndexModifier;
use Tchooz\Entities\Emails\TagContext;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Interfaces\TagProviderInterface;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Services\ApplicationFile\ApplicationChoicesService;

/**
 * Resolves the tag holding the choices of an application file along the message a manager attached to their
 * last state change.
 *
 * Selection is driven by the modifiers written on the tag occurrence, the way the VOEU tag does it:
 * STATUS("rejected") keeps the choices in that state, INDEX("1") keeps the first one.
 */
class ChoiceCommentTagProvider implements TagProviderInterface
{
	public const TAG = 'VOEU_MOTIF';

	/**
	 * Dependencies are injectable so the provider can be unit-tested without a database.
	 */
	public function __construct(
		private ?ApplicationChoicesRepository $applicationChoicesRepository = null,
		private ?ApplicationChoicesService $applicationChoicesService = null,
		private ?AddonRepository $addonRepository = null
	) {}

	public function getName(): string
	{
		return 'choice_comment';
	}

	public function getProvidedTags(): array
	{
		return [self::TAG];
	}

	public function supports(TagContext $context): bool
	{
		if (empty($context->getFnum()))
		{
			return false;
		}

		// Choice tags are only meaningful when the choices addon is active.
		return (bool) $this->getAddonRepository()->getByName(AddonEnum::CHOICES->value)?->isActivated();
	}

	public function provide(TagContext $context): array
	{
		$choices = $this->getApplicationChoicesRepository()->getChoicesByFnum($context->getFnum());
		$choices = $this->filterByModifiers($choices, $context);

		if (empty($choices))
		{
			return [self::TAG => ''];
		}

		$commentsByChoice = $this->getApplicationChoicesService()->getStateCommentsByChoice($choices);

		$lines = [];
		foreach ($choices as $choice)
		{
			$line = Text::sprintf('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO', $choice->getOrder())
				. ' : ' . $choice->getCampaign()?->getLabel();

			// Only the message of the last state change is restituted, the previous ones stay internal history
			$lastComment = $commentsByChoice[$choice->getId()][0] ?? null;
			if (!empty($lastComment))
			{
				$line .= '<br />' . $lastComment->getContent();
			}

			$lines[] = $line;
		}

		return [self::TAG => implode('<br />', $lines)];
	}

	/**
	 * @param   array<ApplicationChoicesEntity>  $choices
	 *
	 * @return array<ApplicationChoicesEntity>
	 */
	private function filterByModifiers(array $choices, TagContext $context): array
	{
		$statusParams = $context->getModifierParams(ChoiceStatusModifier::class);
		if (!empty($statusParams[0]))
		{
			$state = ChoicesStateEnum::isValidState($statusParams[0]);

			$choices = array_filter($choices, function (ApplicationChoicesEntity $choice) use ($state) {
				return !empty($state) && $choice->getState() === $state;
			});
		}

		$indexParams = $context->getModifierParams(IndexModifier::class);
		if (!empty($indexParams[0]))
		{
			$choices = array_values($choices);
			$index   = (int) $indexParams[0];

			$choices = isset($choices[$index - 1]) ? [$choices[$index - 1]] : [];
		}

		return $choices;
	}

	private function getApplicationChoicesRepository(): ApplicationChoicesRepository
	{
		return $this->applicationChoicesRepository ??= new ApplicationChoicesRepository();
	}

	private function getApplicationChoicesService(): ApplicationChoicesService
	{
		return $this->applicationChoicesService ??= new ApplicationChoicesService();
	}

	private function getAddonRepository(): AddonRepository
	{
		return $this->addonRepository ??= new AddonRepository();
	}
}
