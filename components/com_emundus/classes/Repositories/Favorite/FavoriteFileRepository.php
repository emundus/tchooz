<?php
/**
 * @package     Tchooz\Repositories\Favorite
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Favorite;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Favorite\FavoriteFileEntity;
use Tchooz\Factories\Favorite\FavoriteFileFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: self::TABLE,
	alias: 'eff',
	columns: [
		'id',
		'fnum',
		'user_id',
		'created',
	]
)]
class FavoriteFileRepository extends EmundusRepository implements RepositoryInterface
{
	/**
	 * Declared here rather than inlined in the attribute so the raw filter subquery in
	 * EmundusHelperFiles::_moduleBuildWhere() can reference the same declaration.
	 */
	public const TABLE = 'jos_emundus_favorite_files';

	private FavoriteFileFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'favorite_file', self::class);

		$this->factory = new FavoriteFileFactory();
	}

	public function getFactory(): ?object
	{
		return $this->factory;
	}

	/**
	 * Adds the file to the user's favorites when absent, removes it when present.
	 *
	 * @return bool the new favorite state
	 */
	public function toggleFavorite(string $fnum, int $userId): bool
	{
		$this->assertTarget($fnum, $userId);

		// Delete first: it tells us the previous state and removes it in a single atomic
		// statement, avoiding the read-then-write window a separate isFavorite() would open.
		if ($this->removeFavorite($fnum, $userId))
		{
			$this->clearUserCache($userId);

			return false;
		}

		$this->addFavorite($fnum, $userId);
		$this->clearUserCache($userId);

		return true;
	}

	public function isFavorite(string $fnum, int $userId): bool
	{
		if (empty($fnum) || empty($userId))
		{
			return false;
		}

		return in_array($fnum, $this->getAllFnumsByUser($userId), true);
	}

	/**
	 * Among the given file numbers, returns those the user marked as favorite.
	 *
	 * @param   array<string>  $fnums
	 *
	 * @return array<string>
	 */
	public function getFavoriteFnums(array $fnums, int $userId): array
	{
		$fnums = array_values(array_filter(array_unique($fnums)));

		if (empty($fnums) || empty($userId))
		{
			return [];
		}

		return array_values(array_intersect($fnums, $this->getAllFnumsByUser($userId)));
	}

	public function countByUser(int $userId): int
	{
		if (empty($userId))
		{
			return 0;
		}

		return count($this->getAllFnumsByUser($userId));
	}

	/**
	 * Every file number the user marked, cached per user.
	 *
	 * A manager keeps a handful of favorites while a list page shows dozens of rows, so caching the
	 * whole personal set and intersecting in PHP is cheaper than one query per page — and it serves
	 * isFavorite() and countByUser() from the same entry. Invalidated on every write below.
	 *
	 * @return array<string>
	 */
	private function getAllFnumsByUser(int $userId): array
	{
		if (empty($userId))
		{
			return [];
		}

		$cacheKey = $this->getUserCacheKey($userId);

		if ($this->cache?->contains($cacheKey))
		{
			return $this->cache->get($cacheKey) ?: [];
		}

		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName('fnum'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('user_id') . ' = :userId')
			->bind(':userId', $userId, ParameterType::INTEGER);

		$this->db->setQuery($query);
		$fnums = $this->db->loadColumn() ?: [];

		$this->cache?->store($fnums, $cacheKey);

		return $fnums;
	}

	private function getUserCacheKey(int $userId): string
	{
		return 'favorite_fnums_' . $userId;
	}

	private function clearUserCache(int $userId): void
	{
		if (!empty($userId))
		{
			$this->cache?->remove($this->getUserCacheKey($userId));
		}
	}

	public function getById(int $id): ?FavoriteFileEntity
	{
		if (empty($id))
		{
			return null;
		}

		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName($this->columnsNoAlias))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		return !empty($dbObject) ? $this->factory->fromDbObject($dbObject) : null;
	}

	public function delete(int $id): bool
	{
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_FAVORITES_MISSING_ID'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		// Read before deleting: the row is the only thing that knows whose cache to drop.
		$favorite = $this->getById($id);

		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$this->db->setQuery($query);

		if (!$this->db->execute())
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_FAVORITES_TOGGLE_FAILED'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		if (!empty($favorite))
		{
			$this->clearUserCache($favorite->getUserId());
		}

		return true;
	}

	/**
	 * @return bool true when a row was actually deleted
	 */
	private function removeFavorite(string $fnum, int $userId): bool
	{
		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('fnum') . ' = :fnum')
			->andWhere($this->db->quoteName('user_id') . ' = :userId')
			->bind(':fnum', $fnum, ParameterType::STRING)
			->bind(':userId', $userId, ParameterType::INTEGER);

		$this->db->setQuery($query);

		if (!$this->db->execute())
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_FAVORITES_TOGGLE_FAILED'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return $this->db->getAffectedRows() > 0;
	}

	private function addFavorite(string $fnum, int $userId): void
	{
		// INSERT IGNORE, not insertObject: the unique key (user_id, fnum) makes a concurrent
		// double-click a no-op instead of a duplicate-key error, and the user's intent is
		// already satisfied in that case. The query builder cannot express IGNORE.
		$query = 'INSERT IGNORE INTO ' . $this->db->quoteName($this->tableName)
			. ' (' . $this->db->quoteName('fnum')
			. ', ' . $this->db->quoteName('user_id')
			. ', ' . $this->db->quoteName('created') . ')'
			. ' VALUES (' . $this->db->quote($fnum)
			. ', ' . (int) $userId
			. ', ' . $this->db->quote(Factory::getDate()->toSql()) . ')';

		$this->db->setQuery($query);

		if (!$this->db->execute())
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_FAVORITES_TOGGLE_FAILED'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	private function assertTarget(string $fnum, int $userId): void
	{
		if (empty($fnum))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_FAVORITES_MISSING_FNUM'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (empty($userId))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_FAVORITES_MISSING_USER'), EmundusResponse::HTTP_BAD_REQUEST);
		}
	}
}
