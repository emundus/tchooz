<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\ApplicationFile;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\ApplicationTagEntity;
use Tchooz\Factories\ApplicationFile\ApplicationTagFactory;
use Tchooz\Repositories\ApplicationFile\TagsRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\ApplicationFile\TagsRepository
 * @covers      \Tchooz\Factories\ApplicationFile\ApplicationTagFactory
 */
class TagsRepositoryTest extends UnitTestCase
{
	private TagsRepository $repository;

	private array $createdTagIds = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->repository = new TagsRepository();
	}

	protected function tearDown(): void
	{
		foreach ($this->createdTagIds as $tagId)
		{
			$this->repository->delete($tagId);
		}
		$this->createdTagIds = [];

		parent::tearDown();
	}

	private function uniqueLabel(string $prefix = 'TU Tag'): string
	{
		return $prefix . ' ' . uniqid('', true);
	}

	// =====================
	// create tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::create
	 */
	public function testCreateReturnsTagEntity(): void
	{
		$label = $this->uniqueLabel();

		$tag = $this->repository->create($label, 'label-blue-2', 'TU', 5);

		$this->assertNotNull($tag);
		$this->assertInstanceOf(ApplicationTagEntity::class, $tag);
		$this->assertNotEmpty($tag->getId());
		$this->assertSame($label, $tag->getLabel());
		$this->assertSame('label-blue-2', $tag->getColor());
		$this->assertSame('TU', $tag->getCategory());
		$this->assertSame(5, $tag->getOrdering());

		$this->createdTagIds[] = $tag->getId();
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::create
	 */
	public function testCreateUsesDefaultColorWhenNotProvided(): void
	{
		$label = $this->uniqueLabel();

		$tag = $this->repository->create($label);

		$this->assertNotNull($tag);
		$this->assertSame('label-default', $tag->getColor());

		$this->createdTagIds[] = $tag->getId();
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::create
	 */
	public function testCreateThrowsOnEmptyLabel(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->create('   ');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::create
	 */
	public function testCreateThrowsWhenLabelAlreadyExists(): void
	{
		$label = $this->uniqueLabel();

		$first = $this->repository->create($label);
		$this->assertNotNull($first);
		$this->createdTagIds[] = $first->getId();

		$this->expectException(\RuntimeException::class);
		$this->repository->create($label);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::create
	 */
	public function testCreateIsCaseInsensitiveForDuplicateCheck(): void
	{
		$label = $this->uniqueLabel('Étiquette TU');

		$first = $this->repository->create($label);
		$this->assertNotNull($first);
		$this->createdTagIds[] = $first->getId();

		$this->expectException(\RuntimeException::class);
		$this->repository->create(strtoupper($label));
	}

	// =====================
	// existsByLabel tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::existsByLabel
	 */
	public function testExistsByLabelReturnsTrueForExistingTag(): void
	{
		$label = $this->uniqueLabel();
		$tag = $this->repository->create($label);
		$this->createdTagIds[] = $tag->getId();

		$this->assertTrue($this->repository->existsByLabel($label));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::existsByLabel
	 */
	public function testExistsByLabelIsCaseInsensitive(): void
	{
		$label = $this->uniqueLabel('Mixed Case');
		$tag = $this->repository->create($label);
		$this->createdTagIds[] = $tag->getId();

		$this->assertTrue($this->repository->existsByLabel(strtolower($label)));
		$this->assertTrue($this->repository->existsByLabel(strtoupper($label)));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::existsByLabel
	 */
	public function testExistsByLabelReturnsFalseForUnknownLabel(): void
	{
		$this->assertFalse($this->repository->existsByLabel($this->uniqueLabel('Unknown')));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::existsByLabel
	 */
	public function testExistsByLabelReturnsFalseForEmptyLabel(): void
	{
		$this->assertFalse($this->repository->existsByLabel(''));
	}

	// =====================
	// getById tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::getById
	 */
	public function testGetByIdReturnsCreatedTag(): void
	{
		$label = $this->uniqueLabel();
		$tag = $this->repository->create($label, 'label-red-1', 'CatTU', 3);
		$this->createdTagIds[] = $tag->getId();

		$fetched = $this->repository->getById($tag->getId());

		$this->assertNotNull($fetched);
		$this->assertInstanceOf(ApplicationTagEntity::class, $fetched);
		$this->assertSame($tag->getId(), $fetched->getId());
		$this->assertSame($label, $fetched->getLabel());
		$this->assertSame('label-red-1', $fetched->getColor());
		$this->assertSame('CatTU', $fetched->getCategory());
		$this->assertSame(3, $fetched->getOrdering());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::getById
	 */
	public function testGetByIdReturnsNullForNonExistentId(): void
	{
		$this->assertNull($this->repository->getById(999999));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::getById
	 */
	public function testGetByIdReturnsNullForZeroId(): void
	{
		$this->assertNull($this->repository->getById(0));
	}

	// =====================
	// getByLabel tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::getByLabel
	 */
	public function testGetByLabelReturnsMatchingTag(): void
	{
		$label = $this->uniqueLabel();
		$tag = $this->repository->create($label);
		$this->createdTagIds[] = $tag->getId();

		$fetched = $this->repository->getByLabel($label);

		$this->assertNotNull($fetched);
		$this->assertSame($tag->getId(), $fetched->getId());
		$this->assertSame($label, $fetched->getLabel());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::getByLabel
	 */
	public function testGetByLabelReturnsNullForUnknownLabel(): void
	{
		$this->assertNull($this->repository->getByLabel($this->uniqueLabel('Ghost')));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::getByLabel
	 */
	public function testGetByLabelReturnsNullForEmptyLabel(): void
	{
		$this->assertNull($this->repository->getByLabel(''));
	}

	// =====================
	// delete tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::delete
	 */
	public function testDeleteRemovesTag(): void
	{
		$label = $this->uniqueLabel();
		$tag = $this->repository->create($label);
		$this->assertNotNull($tag);

		$result = $this->repository->delete($tag->getId());

		$this->assertTrue($result);
		$this->assertNull($this->repository->getById($tag->getId()));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::delete
	 */
	public function testDeleteReturnsFalseForZeroId(): void
	{
		$this->assertFalse($this->repository->delete(0));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::delete
	 */
	public function testDeleteAllowsRecreatingSameLabel(): void
	{
		$label = $this->uniqueLabel();

		$first = $this->repository->create($label);
		$this->assertNotNull($first);
		$this->repository->delete($first->getId());

		$second = $this->repository->create($label);
		$this->assertNotNull($second, 'Should be able to create a tag again with the same label after deletion.');
		$this->createdTagIds[] = $second->getId();
	}

	// =====================
	// getFactory tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\TagsRepository::getFactory
	 */
	public function testGetFactoryReturnsApplicationTagFactory(): void
	{
		$factory = $this->repository->getFactory();

		$this->assertNotNull($factory);
		$this->assertInstanceOf(ApplicationTagFactory::class, $factory);
	}
}
