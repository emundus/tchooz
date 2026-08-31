<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Repositories\Favorite
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Favorite;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Favorite\FavoriteFileEntity;
use Tchooz\Repositories\Favorite\FavoriteFileRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Favorite
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Favorite\FavoriteFileRepository
 */
class FavoriteFileRepositoryTest extends UnitTestCase
{
	private FavoriteFileRepository $repository;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->repository = new FavoriteFileRepository();
		$this->model      = $this->repository;
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->clearFavorites();
	}

	protected function tearDown(): void
	{
		$this->clearFavorites();

		parent::tearDown();
	}

	/**
	 * Leftovers from an interrupted run would make the toggle assertions read the wrong initial state.
	 */
	private function clearFavorites(): void
	{
		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName(FavoriteFileRepository::TABLE))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($this->dataset['fnum']));

		$this->db->setQuery($query);
		$this->db->execute();
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::toggleFavorite
	 */
	public function testToggleFavoriteAddsThenRemoves(): void
	{
		$fnum   = $this->dataset['fnum'];
		$userId = $this->dataset['coordinator'];

		$this->assertFalse($this->repository->isFavorite($fnum, $userId), 'File should not be a favorite before the first toggle.');

		$this->assertTrue($this->repository->toggleFavorite($fnum, $userId), 'First toggle should return the new state: favorite.');
		$this->assertTrue($this->repository->isFavorite($fnum, $userId), 'File should be a favorite after the first toggle.');

		$this->assertFalse($this->repository->toggleFavorite($fnum, $userId), 'Second toggle should return the new state: not favorite.');
		$this->assertFalse($this->repository->isFavorite($fnum, $userId), 'File should no longer be a favorite after the second toggle.');
	}

	/**
	 * A double click must not leave a duplicate row behind.
	 *
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::toggleFavorite
	 */
	public function testToggleFavoriteIsIdempotentOnRepeatedAdd(): void
	{
		$fnum   = $this->dataset['fnum'];
		$userId = $this->dataset['coordinator'];

		$this->repository->toggleFavorite($fnum, $userId);
		$this->assertSame(1, $this->countRows($fnum, $userId), 'A single toggle must create exactly one row.');

		$this->repository->toggleFavorite($fnum, $userId);
		$this->repository->toggleFavorite($fnum, $userId);
		$this->assertSame(1, $this->countRows($fnum, $userId), 'Toggling back and forth must never accumulate rows.');
	}

	/**
	 * Favorites are strictly personal: two users on the same file must not see each other's state.
	 *
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::isFavorite
	 */
	public function testFavoritesAreIsolatedPerUser(): void
	{
		$fnum      = $this->dataset['fnum'];
		$firstUser = $this->dataset['coordinator'];
		$otherUser = $this->dataset['applicant'];

		$this->repository->toggleFavorite($fnum, $firstUser);

		$this->assertTrue($this->repository->isFavorite($fnum, $firstUser));
		$this->assertFalse($this->repository->isFavorite($fnum, $otherUser), 'Another user must not inherit the favorite.');

		$this->repository->toggleFavorite($fnum, $otherUser);

		$this->assertTrue($this->repository->isFavorite($fnum, $firstUser), 'The first user state must survive another user toggling the same file.');
		$this->assertTrue($this->repository->isFavorite($fnum, $otherUser));

		$this->repository->toggleFavorite($fnum, $otherUser);

		$this->assertTrue($this->repository->isFavorite($fnum, $firstUser), 'Removing another user favorite must not remove the first one.');
		$this->assertFalse($this->repository->isFavorite($fnum, $otherUser));
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::getFavoriteFnums
	 */
	public function testGetFavoriteFnumsReturnsOnlyTheUserFavorites(): void
	{
		$fnum   = $this->dataset['fnum'];
		$userId = $this->dataset['coordinator'];

		$batch = [$fnum, 'unknown-fnum-0000000000001', 'unknown-fnum-0000000000002'];

		$this->assertSame([], $this->repository->getFavoriteFnums($batch, $userId), 'No favorite yet: the result must be an empty array, never null.');

		$this->repository->toggleFavorite($fnum, $userId);

		$this->assertSame([$fnum], $this->repository->getFavoriteFnums($batch, $userId));
		$this->assertSame([], $this->repository->getFavoriteFnums($batch, $this->dataset['applicant']), 'Another user must get none of them.');
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::getFavoriteFnums
	 */
	public function testGetFavoriteFnumsWithEmptyInput(): void
	{
		$this->assertSame([], $this->repository->getFavoriteFnums([], $this->dataset['coordinator']));
		$this->assertSame([], $this->repository->getFavoriteFnums([$this->dataset['fnum']], 0));
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::countByUser
	 */
	public function testCountByUser(): void
	{
		$fnum   = $this->dataset['fnum'];
		$userId = $this->dataset['coordinator'];

		$initialCount = $this->repository->countByUser($userId);

		$this->repository->toggleFavorite($fnum, $userId);
		$this->assertSame($initialCount + 1, $this->repository->countByUser($userId));

		$this->repository->toggleFavorite($fnum, $userId);
		$this->assertSame($initialCount, $this->repository->countByUser($userId));

		$this->assertSame(0, $this->repository->countByUser(0), 'An empty user id must count zero, not everything.');
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::getById
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::delete
	 */
	public function testGetByIdAndDelete(): void
	{
		$fnum   = $this->dataset['fnum'];
		$userId = $this->dataset['coordinator'];

		$this->repository->toggleFavorite($fnum, $userId);

		$id = $this->findRowId($fnum, $userId);
		$this->assertNotEmpty($id);

		$entity = $this->repository->getById($id);
		$this->assertInstanceOf(FavoriteFileEntity::class, $entity);
		$this->assertSame($fnum, $entity->getFnum());
		$this->assertSame($userId, $entity->getUserId());
		$this->assertNotEmpty($entity->getCreated()->format('Y-m-d H:i:s'));

		$this->assertTrue($this->repository->delete($id));
		$this->assertNull($this->repository->getById($id));
		$this->assertFalse($this->repository->isFavorite($fnum, $userId));
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::getById
	 */
	public function testGetByIdReturnsNullOnEmptyId(): void
	{
		$this->assertNull($this->repository->getById(0));
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::toggleFavorite
	 */
	public function testToggleFavoriteRejectsEmptyFnum(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->toggleFavorite('', $this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Repositories\Favorite\FavoriteFileRepository::toggleFavorite
	 */
	public function testToggleFavoriteRejectsEmptyUser(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->toggleFavorite($this->dataset['fnum'], 0);
	}

	private function countRows(string $fnum, int $userId): int
	{
		$query = $this->db->getQuery(true);

		$query->select('COUNT(' . $this->db->quoteName('id') . ')')
			->from($this->db->quoteName(FavoriteFileRepository::TABLE))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum))
			->andWhere($this->db->quoteName('user_id') . ' = ' . (int) $userId);

		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}

	private function findRowId(string $fnum, int $userId): int
	{
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName('id'))
			->from($this->db->quoteName(FavoriteFileRepository::TABLE))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum))
			->andWhere($this->db->quoteName('user_id') . ' = ' . (int) $userId);

		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}
}
