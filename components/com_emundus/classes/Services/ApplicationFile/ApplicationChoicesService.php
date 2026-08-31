<?php
/**
 * @package     Tchooz\Services\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\ApplicationFile;

use DateTime;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Entities\Comments\CommentEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Comments\CommentRepository;

/**
 * Business operations around the choices of an application file.
 */
class ApplicationChoicesService
{
	public function __construct(
		private readonly CommentRepository $commentRepository = new CommentRepository()
	)
	{
		Log::addLogger(['text_file' => 'com_emundus.service.application_choices.php'], Log::ALL, ['com_emundus.service.application_choices']);
	}

	/**
	 * Stores the message a manager attached to a state change, as a comment targeting the choice.
	 * The state is not copied on the comment: the choice pointed by target_id carries it.
	 *
	 * @param   ApplicationChoicesEntity  $choice
	 * @param   string                    $comment
	 * @param   int                       $authorId
	 *
	 * @return CommentEntity|null  null when there is nothing to store
	 *
	 * @throws \Exception when the comment cannot be persisted
	 */
	public function addStateComment(ApplicationChoicesEntity $choice, string $comment, int $authorId): ?CommentEntity
	{
		$comment = trim($comment);
		if (empty($comment) || empty($choice->getId()))
		{
			return null;
		}

		$commentEntity = new CommentEntity(
			id: 0,
			targetType: CommentTargetTypeEnum::CHOICE,
			targetId: $choice->getId(),
			content: $comment,
			createdBy: $authorId,
			createdAt: new DateTime(),
			fnum: $choice->getFnum(),
			ccid: $choice->getApplicationFile()?->getId() ?? 0
		);

		$this->commentRepository->flush($commentEntity);

		return $commentEntity;
	}

	/**
	 * Rewrites the message of a choice without touching the choice itself, so no state is written and no
	 * automation is triggered. An empty message removes it rather than storing a blank one.
	 *
	 * @param   ApplicationChoicesEntity  $choice
	 * @param   string                    $comment
	 * @param   int                       $authorId
	 *
	 * @return CommentEntity|null  null once the message has been removed
	 *
	 * @throws \Exception when the message cannot be persisted or removed
	 */
	public function updateStateComment(ApplicationChoicesEntity $choice, string $comment, int $authorId): ?CommentEntity
	{
		$comment  = trim($comment);
		$existing = $this->getLastStateComment($choice);

		if (empty($comment))
		{
			if (!empty($existing) && !$this->commentRepository->delete($existing->getId()))
			{
				throw new \RuntimeException(Text::_('COM_EMUNDUS_COMMENT_DELETE_FAILED'), 500);
			}

			$this->logStateCommentUpdate($choice, $authorId);

			return null;
		}

		if (empty($existing))
		{
			$created = $this->addStateComment($choice, $comment, $authorId);
			$this->logStateCommentUpdate($choice, $authorId);

			return $created;
		}

		$existing->setContent($comment);
		$existing->setUpdatedBy($authorId);
		$existing->setUpdatedAt(new DateTime());

		$this->commentRepository->flush($existing);
		$this->logStateCommentUpdate($choice, $authorId);

		return $existing;
	}

	private function logStateCommentUpdate(ApplicationChoicesEntity $choice, int $authorId): void
	{
		if (!class_exists('EmundusModelLogs'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/logs.php';
		}

		\EmundusModelLogs::log(
			$authorId,
			$choice->getUser()->id,
			$choice->getFnum(),
			ActionEnum::APPLICATION_CHOICES->value,
			CrudEnum::UPDATE->value,
			'COM_EMUNDUS_LOGS_UPDATE_CHOICE_COMMENT'
		);
	}

	/**
	 * Message of the last state change of a choice, null when it has none.
	 */
	public function getLastStateComment(ApplicationChoicesEntity $choice): ?CommentEntity
	{
		return $this->commentRepository->getCommentsByTargetIds(CommentTargetTypeEnum::CHOICE, [$choice->getId()])[0] ?? null;
	}

	/**
	 * Messages attached to the choices of a file, indexed by choice id, most recent first.
	 *
	 * @param   array<ApplicationChoicesEntity>  $choices
	 *
	 * @return array<int, array<CommentEntity>>
	 */
	public function getStateCommentsByChoice(array $choices): array
	{
		$commentsByChoice = [];

		$choiceIds = [];
		foreach ($choices as $choice)
		{
			$choiceIds[] = $choice->getId();
		}

		$comments = $this->commentRepository->getCommentsByTargetIds(CommentTargetTypeEnum::CHOICE, $choiceIds);
		foreach ($comments as $comment)
		{
			$commentsByChoice[$comment->getTargetId()][] = $comment;
		}

		return $commentsByChoice;
	}
}
