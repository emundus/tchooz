<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\ApplicationFile;

use DateTime;
use EmundusModelApplication;
use Joomla\CMS\User\User;
use PHPUnit\Framework\TestCase;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Entities\Comments\CommentEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\Comments\CommentRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Services\ApplicationFile\ApplicationChoicesService;

/**
 * @package     Unit\Component\Emundus\Class\Services\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\ApplicationFile\ApplicationChoicesService
 */
class ApplicationChoicesServiceTest extends TestCase
{
	private const ALL_RIGHTS_GROUP = 1;

	public static function setUpBeforeClass(): void
	{
		require_once JPATH_SITE . '/components/com_emundus/models/application.php';
	}

	// -------------------------------------------------------------------------
	// addStateComment
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::addStateComment
	 * @return void
	 */
	public function testAddStateCommentWithAnEmptyMessageStoresNothing(): void
	{
		$repository = $this->createMock(CommentRepository::class);
		$repository->expects($this->never())->method('flush');

		$service = new ApplicationChoicesService($repository);

		$this->assertNull($service->addStateComment($this->choice(), '   ', 42), 'A blank message is not worth a row');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::addStateComment
	 * @return void
	 */
	public function testAddStateCommentTargetsTheChoiceAndTrimsTheMessage(): void
	{
		$flushed = null;

		$repository = $this->createMock(CommentRepository::class);
		$repository->expects($this->once())
			->method('flush')
			->willReturnCallback(function (CommentEntity $comment) use (&$flushed) {
				$flushed = $comment;

				return true;
			});

		$service = new ApplicationChoicesService($repository);
		$service->addStateComment($this->choice(7, 'fnum-7'), "  Dossier incomplet  ", 42);

		$this->assertSame(CommentTargetTypeEnum::CHOICE, $flushed->getTargetType(), 'The comment targets a choice');
		$this->assertSame(7, $flushed->getTargetId(), 'The target is the choice it justifies');
		$this->assertSame('Dossier incomplet', $flushed->getContent(), 'The message is trimmed before storage');
		$this->assertSame(42, $flushed->getCreatedBy(), 'The author is the manager who wrote it');
	}

	// -------------------------------------------------------------------------
	// updateStateComment — no state is written, so no automation is triggered
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::updateStateComment
	 * @return void
	 */
	public function testUpdateStateCommentWithoutAnExistingMessageCreatesIt(): void
	{
		$flushed = null;

		$repository = $this->createMock(CommentRepository::class);
		$repository->method('getCommentsByTargetIds')->willReturn([]);
		$repository->expects($this->never())->method('delete');
		$repository->expects($this->once())
			->method('flush')
			->willReturnCallback(function (CommentEntity $comment) use (&$flushed) {
				$flushed = $comment;

				return true;
			});

		$service = new ApplicationChoicesService($repository);
		$service->updateStateComment($this->choice(7), 'Premier motif', 42);

		$this->assertSame('Premier motif', $flushed->getContent(), 'A choice without message gets a new one');
		$this->assertSame(0, $flushed->getId(), 'A creation carries no id yet');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::updateStateComment
	 * @return void
	 */
	public function testUpdateStateCommentRewritesTheExistingMessage(): void
	{
		$existing = $this->comment(31, 'Ancien motif');

		$repository = $this->createMock(CommentRepository::class);
		$repository->method('getCommentsByTargetIds')->willReturn([$existing]);
		$repository->expects($this->never())->method('delete');
		$repository->expects($this->once())->method('flush')->willReturn(true);

		$service = new ApplicationChoicesService($repository);
		$updated = $service->updateStateComment($this->choice(7), 'Nouveau motif', 42);

		$this->assertSame(31, $updated->getId(), 'The existing row is rewritten, not duplicated');
		$this->assertSame('Nouveau motif', $updated->getContent(), 'The message is replaced');
		$this->assertSame(42, $updated->getUpdatedBy(), 'The editor is recorded');
		$this->assertNotNull($updated->getUpdatedAt(), 'The edition date is recorded');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::updateStateComment
	 * @return void
	 */
	public function testUpdateStateCommentWithAnEmptyMessageRemovesTheExistingOne(): void
	{
		$repository = $this->createMock(CommentRepository::class);
		$repository->method('getCommentsByTargetIds')->willReturn([$this->comment(31, 'Ancien motif')]);
		$repository->expects($this->never())->method('flush');
		$repository->expects($this->once())->method('delete')->with(31)->willReturn(true);

		$service = new ApplicationChoicesService($repository);

		$this->assertNull($service->updateStateComment($this->choice(7), '', 42), 'Emptying the field removes the message');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::updateStateComment
	 * @return void
	 */
	public function testUpdateStateCommentWithAnEmptyMessageAndNothingStoredDoesNothing(): void
	{
		$repository = $this->createMock(CommentRepository::class);
		$repository->method('getCommentsByTargetIds')->willReturn([]);
		$repository->expects($this->never())->method('flush');
		$repository->expects($this->never())->method('delete');

		$service = new ApplicationChoicesService($repository);

		$this->assertNull($service->updateStateComment($this->choice(7), '', 42), 'Nothing to remove, nothing to write');
	}

	/**
	 * A failed removal must not be reported as a success: the manager would think the message is gone.
	 *
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::updateStateComment
	 * @return void
	 */
	public function testUpdateStateCommentThrowsWhenTheRemovalFails(): void
	{
		$repository = $this->createMock(CommentRepository::class);
		$repository->method('getCommentsByTargetIds')->willReturn([$this->comment(31, 'Ancien motif')]);
		$repository->method('delete')->willReturn(false);

		$service = new ApplicationChoicesService($repository);

		$this->expectException(\RuntimeException::class);

		$service->updateStateComment($this->choice(7), '', 42);
	}

	// -------------------------------------------------------------------------
	// getStateCommentsByChoice
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::getStateCommentsByChoice
	 * @return void
	 */
	public function testGetStateCommentsByChoiceIndexesThemByChoice(): void
	{
		$repository = $this->createMock(CommentRepository::class);
		$repository->expects($this->once())
			->method('getCommentsByTargetIds')
			->with(CommentTargetTypeEnum::CHOICE, [7, 8])
			->willReturn([
				$this->comment(31, 'Motif du voeu 7', 7),
				$this->comment(32, 'Motif du voeu 8', 8),
				$this->comment(33, 'Motif plus ancien du voeu 7', 7),
			]);

		$service = new ApplicationChoicesService($repository);
		$indexed = $service->getStateCommentsByChoice([$this->choice(7), $this->choice(8)]);

		$this->assertCount(2, $indexed[7], 'Every message of a choice is kept');
		$this->assertSame('Motif du voeu 7', $indexed[7][0]->getContent(), 'The most recent message comes first');
		$this->assertCount(1, $indexed[8], 'Messages are not mixed between choices');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::getStateCommentsByChoice
	 * @return void
	 */
	public function testGetStateCommentsByChoiceWithoutChoicesReturnsNothing(): void
	{
		$repository = $this->createMock(CommentRepository::class);
		$repository->method('getCommentsByTargetIds')->willReturn([]);

		$service = new ApplicationChoicesService($repository);

		$this->assertSame([], $service->getStateCommentsByChoice([]), 'No choice, no message');
	}

	// -------------------------------------------------------------------------
	// confirmChoice
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::confirmChoice
	 * @return void
	 */
	public function testConfirmChoiceConfirmsTheChoiceWithTheOutputStatus(): void
	{
		$confirmed = $this->realChoice(1, 'PROG1');

		$repository = $this->createMock(ApplicationChoicesRepository::class);
		$repository->method('getChoicesByFnum')->willReturn([$confirmed]);
		$repository->expects($this->once())->method('flush')->with($confirmed, false, 5)->willReturn(true);

		$service = $this->confirmService($repository, [], $this->createMock(EmundusModelApplication::class));
		$service->confirmChoice($confirmed, 42, 5);

		$this->assertSame(ChoicesStateEnum::CONFIRMED, $confirmed->getState(), 'The choice is confirmed');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::confirmChoice
	 * @return void
	 */
	public function testConfirmChoiceRejectsTheOtherChoices(): void
	{
		$confirmed = $this->realChoice(1, 'PROG1');
		$other     = $this->realChoice(2, 'PROG2', ChoicesStateEnum::ACCEPTED);

		$repository = $this->createMock(ApplicationChoicesRepository::class);
		$repository->method('getChoicesByFnum')->willReturn([$confirmed, $other]);
		$repository->expects($this->exactly(2))->method('flush')->willReturn(true);

		$service = $this->confirmService($repository, [], $this->createMock(EmundusModelApplication::class));
		$service->confirmChoice($confirmed, 42);

		$this->assertSame(ChoicesStateEnum::REJECTED, $other->getState(), 'The other choices are rejected');
		$this->assertSame(ChoicesStateEnum::CONFIRMED, $confirmed->getState(), 'The confirmed choice is not rejected while looping on the file choices');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::confirmChoice
	 * @return void
	 */
	public function testConfirmChoiceDoesNotRewriteAnAlreadyRejectedChoice(): void
	{
		$confirmed = $this->realChoice(1, 'PROG1');
		$rejected  = $this->realChoice(2, 'PROG2', ChoicesStateEnum::REJECTED);

		$repository = $this->createMock(ApplicationChoicesRepository::class);
		$repository->method('getChoicesByFnum')->willReturn([$confirmed, $rejected]);
		$repository->expects($this->once())->method('flush')->with($confirmed)->willReturn(true);

		$service = $this->confirmService($repository, [], $this->createMock(EmundusModelApplication::class));
		$service->confirmChoice($confirmed, 42);
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::confirmChoice
	 * @return void
	 */
	public function testConfirmChoiceRemovesOnlyTheGroupsSpecificToTheRejectedPrograms(): void
	{
		$confirmed = $this->realChoice(1, 'PROG1');
		$other     = $this->realChoice(2, 'PROG2', ChoicesStateEnum::ACCEPTED);

		$repository = $this->createMock(ApplicationChoicesRepository::class);
		$repository->method('getChoicesByFnum')->willReturn([$confirmed, $other]);
		$repository->method('flush')->willReturn(true);

		$applicationModel = $this->createMock(EmundusModelApplication::class);
		$applicationModel->expects($this->once())
			->method('deleteGroupsAccess')
			->with('fnum-test', [12], 42)
			->willReturn(true);

		$service = $this->confirmService($repository, [
			'PROG1' => [10, 11],
			'PROG2' => [11, 12, self::ALL_RIGHTS_GROUP],
		], $applicationModel);
		$service->confirmChoice($confirmed, 42);
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::confirmChoice
	 * @return void
	 */
	public function testConfirmChoiceRemovesTheGroupsOfAnAlreadyRejectedChoiceToo(): void
	{
		$confirmed = $this->realChoice(1, 'PROG1');
		$rejected  = $this->realChoice(2, 'PROG2', ChoicesStateEnum::REJECTED);

		$repository = $this->createMock(ApplicationChoicesRepository::class);
		$repository->method('getChoicesByFnum')->willReturn([$confirmed, $rejected]);
		$repository->method('flush')->willReturn(true);

		$applicationModel = $this->createMock(EmundusModelApplication::class);
		$applicationModel->expects($this->once())
			->method('deleteGroupsAccess')
			->with('fnum-test', [20], 42)
			->willReturn(true);

		$service = $this->confirmService($repository, ['PROG1' => [10], 'PROG2' => [20]], $applicationModel);
		$service->confirmChoice($confirmed, 42);
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationChoicesService::confirmChoice
	 * @return void
	 */
	public function testConfirmChoiceWithoutGroupSpecificToTheRejectedProgramsKeepsEveryAccess(): void
	{
		$confirmed = $this->realChoice(1, 'PROG1');
		$other     = $this->realChoice(2, 'PROG2', ChoicesStateEnum::ACCEPTED);

		$repository = $this->createMock(ApplicationChoicesRepository::class);
		$repository->method('getChoicesByFnum')->willReturn([$confirmed, $other]);
		$repository->method('flush')->willReturn(true);

		$applicationModel = $this->createMock(EmundusModelApplication::class);
		$applicationModel->expects($this->never())->method('deleteGroupsAccess');

		$service = $this->confirmService($repository, [
			'PROG1' => [10, 11],
			'PROG2' => [11, self::ALL_RIGHTS_GROUP],
		], $applicationModel);
		$service->confirmChoice($confirmed, 42);
	}

	// -------------------------------------------------------------------------
	// Fixtures
	// -------------------------------------------------------------------------

	/**
	 * @param   array<string, array<int>>  $groupsByProgram
	 */
	private function confirmService(ApplicationChoicesRepository $repository, array $groupsByProgram, EmundusModelApplication $applicationModel): ApplicationChoicesService
	{
		$groupRepository = $this->createMock(GroupRepository::class);
		$groupRepository->method('get')->willReturnCallback(
			function (array $filters) use ($groupsByProgram) {
				$code = $filters[1]->getValue();

				return array_map(fn(int $id) => (object) ['id' => (string) $id], $groupsByProgram[$code] ?? []);
			}
		);

		return new ApplicationChoicesService(
			$this->createMock(CommentRepository::class),
			$repository,
			$groupRepository,
			$applicationModel,
			self::ALL_RIGHTS_GROUP
		);
	}

	private function realChoice(int $id, string $programCode, ChoicesStateEnum $state = ChoicesStateEnum::DRAFT): ApplicationChoicesEntity
	{
		$program = $this->createMock(ProgramEntity::class);
		$program->method('getCode')->willReturn($programCode);

		$campaign = $this->createMock(CampaignEntity::class);
		$campaign->method('getProgram')->willReturn($program);

		return new ApplicationChoicesEntity('fnum-test', $this->createMock(User::class), $campaign, $id * 100, $id, $state, $id);
	}

	private function choice(int $id = 7, string $fnum = 'fnum-test'): ApplicationChoicesEntity
	{
		$choice = $this->createMock(ApplicationChoicesEntity::class);
		$choice->method('getId')->willReturn($id);
		$choice->method('getFnum')->willReturn($fnum);
		$choice->method('getUser')->willReturn($this->createMock(User::class));
		$choice->method('getApplicationFile')->willReturn(null);

		return $choice;
	}

	private function comment(int $id, string $content, int $targetId = 7): CommentEntity
	{
		return new CommentEntity(
			id: $id,
			targetType: CommentTargetTypeEnum::CHOICE,
			targetId: $targetId,
			content: $content,
			createdBy: 1,
			createdAt: new DateTime()
		);
	}
}
