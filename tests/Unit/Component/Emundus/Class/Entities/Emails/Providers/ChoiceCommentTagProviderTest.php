<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Emails\Providers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Emails\Providers;

use DateTime;
use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\Comments\CommentEntity;
use Tchooz\Entities\Emails\Modifiers\ChoiceStatusModifier;
use Tchooz\Entities\Emails\Modifiers\IndexModifier;
use Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider;
use Tchooz\Entities\Emails\TagContext;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Services\ApplicationFile\ApplicationChoicesService;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Emails\Providers
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider
 */
class ChoiceCommentTagProviderTest extends TestCase
{
	// -------------------------------------------------------------------------
	// Identity
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::getName
	 * @return void
	 */
	public function testGetNameReturnsChoiceComment(): void
	{
		$provider = new ChoiceCommentTagProvider();

		$this->assertSame('choice_comment', $provider->getName(), 'The provider name must be the stable registry key');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::getProvidedTags
	 * @return void
	 */
	public function testGetProvidedTagsReturnsTheChoiceCommentTag(): void
	{
		$provider = new ChoiceCommentTagProvider();

		$this->assertSame(['VOEU_MOTIF'], $provider->getProvidedTags(), 'The provider must declare exactly the tag the content guard targets');
	}

	// -------------------------------------------------------------------------
	// supports()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::supports
	 * @return void
	 */
	public function testSupportsWhenFnumIsEmptyReturnsFalseWithoutTouchingTheAddon(): void
	{
		$addonRepository = $this->createMock(AddonRepository::class);
		$addonRepository->expects($this->never())->method('getByName');

		$provider = new ChoiceCommentTagProvider(null, null, $addonRepository);

		$this->assertFalse($provider->supports(new TagContext(1, null)), 'Without a fnum there is no file to read choices from');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::supports
	 * @return void
	 */
	public function testSupportsWhenChoicesAddonIsDisabledReturnsFalse(): void
	{
		$addon = $this->createMock(AddonEntity::class);
		$addon->method('isActivated')->willReturn(false);

		$addonRepository = $this->createMock(AddonRepository::class);
		$addonRepository->method('getByName')->willReturn($addon);

		$provider = new ChoiceCommentTagProvider(null, null, $addonRepository);

		$this->assertFalse($provider->supports(new TagContext(1, 'fnum')), 'Choice tags are meaningless when the addon is off');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::supports
	 * @return void
	 */
	public function testSupportsWhenAddonIsMissingReturnsFalse(): void
	{
		$addonRepository = $this->createMock(AddonRepository::class);
		$addonRepository->method('getByName')->willReturn(null);

		$provider = new ChoiceCommentTagProvider(null, null, $addonRepository);

		$this->assertFalse($provider->supports(new TagContext(1, 'fnum')), 'A missing addon row must not raise, only deny');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::supports
	 * @return void
	 */
	public function testSupportsWhenFnumAndAddonAreThereReturnsTrue(): void
	{
		$provider = new ChoiceCommentTagProvider(null, null, $this->activatedAddonRepository());

		$this->assertTrue($provider->supports(new TagContext(1, 'fnum')), 'A file and an active addon are all the provider needs');
	}

	// -------------------------------------------------------------------------
	// provide() — rendering
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideWithoutChoicesReturnsAnEmptyString(): void
	{
		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([]),
			$this->choicesService([]),
			$this->activatedAddonRepository()
		);

		$values = $provider->provide(new TagContext(1, 'fnum'));

		$this->assertSame(['VOEU_MOTIF' => ''], $values, 'A file without choices resolves to an empty value, never to the tag name');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideRendersEveryChoiceWithItsMessage(): void
	{
		$rejected = $this->choice(1, 1, ChoicesStateEnum::REJECTED, 'Master Droit');
		$accepted = $this->choice(2, 2, ChoicesStateEnum::ACCEPTED, 'Master Economie');

		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([$rejected, $accepted]),
			$this->choicesService([
				1 => [$this->comment(1, 'Dossier incomplet')],
				2 => [$this->comment(2, 'Bravo')],
			]),
			$this->activatedAddonRepository()
		);

		$value = $provider->provide(new TagContext(1, 'fnum'))['VOEU_MOTIF'];

		$this->assertStringContainsString('Master Droit', $value, 'Each choice campaign must be named');
		$this->assertStringContainsString('Dossier incomplet', $value, 'Each choice message must be restituted');
		$this->assertStringContainsString('Master Economie', $value, 'The second choice must be there too');
		$this->assertStringContainsString('Bravo', $value, 'The second message must be there too');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideKeepsAChoiceWithoutMessage(): void
	{
		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([$this->choice(1, 1, ChoicesStateEnum::WAITING, 'Master Droit')]),
			$this->choicesService([]),
			$this->activatedAddonRepository()
		);

		$value = $provider->provide(new TagContext(1, 'fnum'))['VOEU_MOTIF'];

		$this->assertStringContainsString('Master Droit', $value, 'A choice without message is still listed');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideRestitutesOnlyTheLastMessageOfAChoice(): void
	{
		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([$this->choice(1, 1, ChoicesStateEnum::REJECTED, 'Master Droit')]),
			$this->choicesService([
				1 => [$this->comment(1, 'Refus definitif'), $this->comment(1, 'Premier passage')],
			]),
			$this->activatedAddonRepository()
		);

		$value = $provider->provide(new TagContext(1, 'fnum'))['VOEU_MOTIF'];

		$this->assertStringContainsString('Refus definitif', $value, 'The most recent message is the one restituted');
		$this->assertStringNotContainsString('Premier passage', $value, 'Older messages stay internal history');
	}

	// -------------------------------------------------------------------------
	// provide() — selection through the occurrence modifiers
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideWithStatusModifierKeepsOnlyThatState(): void
	{
		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([
				$this->choice(1, 1, ChoicesStateEnum::REJECTED, 'Master Droit'),
				$this->choice(2, 2, ChoicesStateEnum::ACCEPTED, 'Master Economie'),
			]),
			$this->choicesService([
				1 => [$this->comment(1, 'Dossier incomplet')],
				2 => [$this->comment(2, 'Bravo')],
			]),
			$this->activatedAddonRepository()
		);

		$context = new TagContext(1, 'fnum', null, '', '', false, [
			['modifier' => new ChoiceStatusModifier(), 'params' => ['rejected']],
		]);

		$value = $provider->provide($context)['VOEU_MOTIF'];

		$this->assertStringContainsString('Dossier incomplet', $value, 'The rejected choice message must be restituted');
		$this->assertStringNotContainsString('Bravo', $value, 'A choice in another state must be filtered out');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideWithUnknownStatusModifierReturnsNothing(): void
	{
		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([$this->choice(1, 1, ChoicesStateEnum::REJECTED, 'Master Droit')]),
			$this->choicesService([1 => [$this->comment(1, 'Dossier incomplet')]]),
			$this->activatedAddonRepository()
		);

		$context = new TagContext(1, 'fnum', null, '', '', false, [
			['modifier' => new ChoiceStatusModifier(), 'params' => ['not_a_state']],
		]);

		$this->assertSame('', $provider->provide($context)['VOEU_MOTIF'], 'An unknown state must filter everything out, never fall back to every choice');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideWithIndexModifierKeepsOnlyThatPosition(): void
	{
		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([
				$this->choice(1, 1, ChoicesStateEnum::REJECTED, 'Master Droit'),
				$this->choice(2, 2, ChoicesStateEnum::ACCEPTED, 'Master Economie'),
			]),
			$this->choicesService([
				1 => [$this->comment(1, 'Dossier incomplet')],
				2 => [$this->comment(2, 'Bravo')],
			]),
			$this->activatedAddonRepository()
		);

		$context = new TagContext(1, 'fnum', null, '', '', false, [
			['modifier' => new IndexModifier(), 'params' => ['2']],
		]);

		$value = $provider->provide($context)['VOEU_MOTIF'];

		$this->assertStringContainsString('Bravo', $value, 'The second choice is the one asked for');
		$this->assertStringNotContainsString('Dossier incomplet', $value, 'The first choice must be filtered out');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider::provide
	 * @return void
	 */
	public function testProvideWithAnIndexOutOfRangeReturnsNothing(): void
	{
		$provider = new ChoiceCommentTagProvider(
			$this->choicesRepository([$this->choice(1, 1, ChoicesStateEnum::REJECTED, 'Master Droit')]),
			$this->choicesService([1 => [$this->comment(1, 'Dossier incomplet')]]),
			$this->activatedAddonRepository()
		);

		$context = new TagContext(1, 'fnum', null, '', '', false, [
			['modifier' => new IndexModifier(), 'params' => ['5']],
		]);

		$this->assertSame('', $provider->provide($context)['VOEU_MOTIF'], 'An index beyond the choices resolves to an empty value');
	}

	// -------------------------------------------------------------------------
	// Fixtures
	// -------------------------------------------------------------------------

	private function activatedAddonRepository(): AddonRepository
	{
		$addon = $this->createMock(AddonEntity::class);
		$addon->method('isActivated')->willReturn(true);

		$addonRepository = $this->createMock(AddonRepository::class);
		$addonRepository->method('getByName')->willReturn($addon);

		return $addonRepository;
	}

	/**
	 * @param   array<ApplicationChoicesEntity>  $choices
	 */
	private function choicesRepository(array $choices): ApplicationChoicesRepository
	{
		$repository = $this->createMock(ApplicationChoicesRepository::class);
		$repository->method('getChoicesByFnum')->willReturn($choices);

		return $repository;
	}

	/**
	 * @param   array<int, array<CommentEntity>>  $commentsByChoice
	 */
	private function choicesService(array $commentsByChoice): ApplicationChoicesService
	{
		$service = $this->createMock(ApplicationChoicesService::class);
		$service->method('getStateCommentsByChoice')->willReturn($commentsByChoice);

		return $service;
	}

	private function choice(int $id, int $order, ChoicesStateEnum $state, string $campaignLabel): ApplicationChoicesEntity
	{
		$campaign = $this->createMock(CampaignEntity::class);
		$campaign->method('getLabel')->willReturn($campaignLabel);

		$choice = $this->createMock(ApplicationChoicesEntity::class);
		$choice->method('getId')->willReturn($id);
		$choice->method('getOrder')->willReturn($order);
		$choice->method('getState')->willReturn($state);
		$choice->method('getCampaign')->willReturn($campaign);

		return $choice;
	}

	private function comment(int $choiceId, string $content): CommentEntity
	{
		return new CommentEntity(
			id: 0,
			targetType: CommentTargetTypeEnum::CHOICE,
			targetId: $choiceId,
			content: $content,
			createdBy: 1,
			createdAt: new DateTime()
		);
	}
}
