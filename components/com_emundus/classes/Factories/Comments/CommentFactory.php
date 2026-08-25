<?php
/**
 * @package     Tchooz\Factories\Comments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Comments;

use DateTime;
use DateTimeImmutable;
use Exception;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Comments\CommentEntity;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\EmundusFactory;

class CommentFactory extends EmundusFactory implements DBFactory
{
	protected const RELATIONS = [];

	/**
	 * @throws Exception
	 */
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ?CommentEntity
	{
		$comment = null;
		if (!empty($dbObject))
		{

			if (is_object($dbObject))
			{
				$dbObject = (array) $dbObject;
			}

			$createdAt = $this->parseDateTime($dbObject['date']) ?? new DateTime();
			$updatedAt = $this->parseDateTime($dbObject['updated'] ?? null);

			$targetType = $dbObject['target_type'] ?? '';
			$targetId   = $dbObject['target_id'] ?? 0;

			// Comments written before the target columns existed only carry the file they belong to, so the
			// file is their target and its ccid the target id.
			if (empty($targetType) && !empty($dbObject['fnum']))
			{
				$targetType = CommentTargetTypeEnum::APPLICATION_FILE;
				$targetId   = empty($targetId) ? ($dbObject['ccid'] ?? 0) : $targetId;
			}

			$comment = new CommentEntity(
				id: $dbObject['id'],
				targetType: $targetType,
				targetId: (int) $targetId,
				content: $dbObject['comment_body'] ?? '',
				createdBy: $dbObject['user_id'] ?? 0,
				createdAt: $createdAt,
				isPublic: isset($dbObject['is_public']) && $dbObject['is_public'] == 1,
				pinned: $dbObject['pinned'] ?? 0,
				updatedBy: $dbObject['updated_by'] ?? 0,
				updatedAt: $updatedAt,
				fnum: $dbObject['fnum'] ?? '',
				parentId: $dbObject['parent_id'] ?? 0,
				ccid: $dbObject['ccid'] ?? 0,
				opened: $dbObject['opened'] ?? 0
			);

			if (isset($dbObject['name']))
			{
				$comment->setAuthorName($dbObject['name']);
			}
		}

		return $comment;
	}

	public function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$comments = [];

		foreach ($dbObjects as $dbObject)
		{
			$comments[] = $this->fromDbObject($dbObject, $withRelations, $exceptRelations, $db);
		}

		return $comments;
	}

	protected function loadRelation(string $relation, object $dbObject): array
	{
		return [];
	}

	/**
	 * @throws Exception
	 */
	private function parseDateTime(mixed $value): ?DateTime
	{
		if (empty($value)) {
			return null;
		}
		if ($value instanceof DateTime) {
			return $value;
		}
		if ($value instanceof DateTimeImmutable) {
			return DateTime::createFromImmutable($value);
		}
		if (is_string($value)) {
			return new DateTime($value);
		}
		return null;
	}
}
