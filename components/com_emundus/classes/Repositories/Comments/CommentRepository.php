<?php
/**
 * @package     Tchooz\Repositories\Comments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Comments;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Comments\CommentEntity;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Factories\Comments\CommentFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

if (!class_exists('CommentEntity'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Comments/CommentEntity.php';
}

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_comments', alias: 't', columns: [
	'id',
	'applicant_id',
	'user_id',
	'fnum',
	'reason',
	'date',
	'comment_body',
	'status_from',
	'status_to',
	'ccid',
	'parent_id',
	'opened',
	'updated',
	'updated_by',
	'target_type',
	'target_id',
	'visible_to_applicant',
	'is_public'
])]
class CommentRepository extends EmundusRepository implements RepositoryInterface
{
	private CommentFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'comment', self::class);
		$this->factory = new CommentFactory();
	}

	public function getFactory(): ?CommentFactory
	{
		return $this->factory;
	}

	/**
	 * @throws Exception
	 */
	public function flush(CommentEntity $entity): bool
	{
		$comment_object = $entity->__serialize();
		// 'name' is a display-only field resolved via JOIN, not a persisted column.
		unset($comment_object['name']);
		$comment_object = (object) $comment_object;
		$comment_object->is_public = $comment_object->is_public ? 1 : 0;

		if (empty($entity->getId()))
		{
			if ($this->db->insertObject($this->tableName, $comment_object))
			{
				$comment_id = $this->db->insertid();
				$entity->setId($comment_id);
			}
			else
			{
				throw new Exception(Text::_('COM_EMUNDUS_COMMENT_INSERT_FAILED'), 500);
			}
		}
		else
		{
			if (!$this->db->updateObject($this->tableName, $comment_object, 'id'))
			{
				throw new Exception(Text::_('COM_EMUNDUS_COMMENT_UPDATE_FAILED'), 500);
			}
		}

		// If false, an exception is throw before
		return true;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$query->clear()
				->delete($this->tableName)
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error while deleting comment: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.comment');
			}
		}

		return $deleted;
	}

	public function getById(int $id): ?CommentEntity
	{
		$comment_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->alias . '.id = ' . $this->db->quote($id));

		$this->db->setQuery($query);
		$comment = $this->db->loadAssoc();

		if (!empty($comment))
		{
			$comment_entity = $this->factory->fromDbObject($comment, $this->withRelations, $this->exceptRelations);
		}

		return $comment_entity;
	}

	/**
	 * @param   int                    $targetId
	 * @param   CommentTargetTypeEnum  $targetType
	 * @param   int                    $currentUserId
	 *
	 * @return array<CommentEntity>
	 */
	public function getCommentsByTarget(int $targetId, CommentTargetTypeEnum $targetType, int $currentUserId): array
	{
		$comments = [];

		if (!empty($targetId) && !empty($currentUserId))
		{
			$query = $this->db->createQuery();
			$query->select([$this->alias . '.*', 'u.name'])
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName($this->alias . '.user_id'))
				->where($this->alias . '.target_type = ' . $this->db->quote($targetType->value))
				->where($this->alias . '.target_id = ' . $this->db->quote($targetId))
				->extendWhere('AND',
					[
						$this->alias . '.is_public = ' . $this->db->quote(1),
						$this->alias . '.user_id = ' . $this->db->quote($currentUserId)
					],
					'OR');
			try
			{
				$this->db->setQuery($query);
				$comments = $this->factory->fromDbObjects($this->db->loadObjectList(), $this->withRelations, $this->exceptRelations, $this->db);
			}
			catch (\Exception $e)
			{
				Log::add('Error while fetching comments: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.comment');
			}
		}

		return $comments;
	}
}