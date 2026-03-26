<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Factories\ApplicationFile\StatusFactory;
use Tchooz\Repositories\ApplicationFile\StatusRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\ApplicationFile\StatusRepository
 * @covers      \Tchooz\Factories\ApplicationFile\StatusFactory
 */
class StatusRepositoryTest extends UnitTestCase
{
	private Registry $config;

	private StatusRepository $repository;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('cache_handler', 'file');
		$this->config->set('caching', 1);

		$this->repository = new StatusRepository();
	}

	// =====================
	// getAll tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getAll
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObjects
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testGetAllReturnsArray(): void
	{
		$statuses = $this->repository->getAll();

		$this->assertIsArray($statuses);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getAll
	 */
	public function testGetAllReturnsStatusEntities(): void
	{
		$statuses = $this->repository->getAll();

		$this->assertNotEmpty($statuses, 'There should be at least one status in the database');
		foreach ($statuses as $status) {
			$this->assertInstanceOf(StatusEntity::class, $status);
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getAll
	 */
	public function testGetAllEntitiesHaveRequiredProperties(): void
	{
		$statuses = $this->repository->getAll();
		$this->assertNotEmpty($statuses);

		$first = $statuses[0];
		$this->assertNotEmpty($first->getId());
		$this->assertIsInt($first->getStep());
		$this->assertIsString($first->getLabel());
		$this->assertIsInt($first->getOrdering());
		$this->assertIsString($first->getColor());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getAll
	 */
	public function testGetAllIsOrderedByOrdering(): void
	{
		$statuses = $this->repository->getAll();

		if (count($statuses) > 1) {
			for ($i = 1; $i < count($statuses); $i++) {
				$this->assertGreaterThanOrEqual(
					$statuses[$i - 1]->getOrdering(),
					$statuses[$i]->getOrdering(),
					'Statuses should be ordered by ordering ASC'
				);
			}
		}
	}

	// =====================
	// getById tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getById
	 */
	public function testGetByIdReturnsStatusEntity(): void
	{
		$statuses = $this->repository->getAll();
		$this->assertNotEmpty($statuses);

		$expected = $statuses[0];
		$fetched = $this->repository->getById($expected->getId());

		$this->assertNotNull($fetched);
		$this->assertInstanceOf(StatusEntity::class, $fetched);
		$this->assertEquals($expected->getId(), $fetched->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getById
	 */
	public function testGetByIdReturnsCorrectProperties(): void
	{
		$statuses = $this->repository->getAll();
		$this->assertNotEmpty($statuses);

		$expected = $statuses[0];
		$fetched = $this->repository->getById($expected->getId());

		$this->assertEquals($expected->getStep(), $fetched->getStep());
		$this->assertEquals($expected->getLabel(), $fetched->getLabel());
		$this->assertEquals($expected->getOrdering(), $fetched->getOrdering());
		$this->assertEquals($expected->getColor(), $fetched->getColor());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getById
	 */
	public function testGetByIdReturnsNullForNonExistentId(): void
	{
		$status = $this->repository->getById(999999);

		$this->assertNull($status);
	}

	// =====================
	// getByStep tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getByStep
	 */
	public function testGetByStepReturnsStatusEntity(): void
	{
		$statuses = $this->repository->getAll();
		$this->assertNotEmpty($statuses);

		$expected = $statuses[0];
		$fetched = $this->repository->getByStep($expected->getStep());

		$this->assertNotNull($fetched);
		$this->assertInstanceOf(StatusEntity::class, $fetched);
		$this->assertEquals($expected->getStep(), $fetched->getStep());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getByStep
	 */
	public function testGetByStepReturnsCorrectProperties(): void
	{
		$statuses = $this->repository->getAll();
		$this->assertNotEmpty($statuses);

		$expected = $statuses[0];
		$fetched = $this->repository->getByStep($expected->getStep());

		$this->assertEquals($expected->getId(), $fetched->getId());
		$this->assertEquals($expected->getLabel(), $fetched->getLabel());
		$this->assertEquals($expected->getOrdering(), $fetched->getOrdering());
		$this->assertEquals($expected->getColor(), $fetched->getColor());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getByStep
	 */
	public function testGetByStepReturnsNullForNonExistentStep(): void
	{
		$status = $this->repository->getByStep(999999);

		$this->assertNull($status);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getByStep
	 */
	public function testGetByStepAndGetByIdReturnSameEntity(): void
	{
		$statuses = $this->repository->getAll();
		$this->assertNotEmpty($statuses);

		$expected = $statuses[0];
		$byId = $this->repository->getById($expected->getId());
		$byStep = $this->repository->getByStep($expected->getStep());

		$this->assertNotNull($byId);
		$this->assertNotNull($byStep);
		$this->assertEquals($byId->getId(), $byStep->getId());
		$this->assertEquals($byId->getStep(), $byStep->getStep());
		$this->assertEquals($byId->getLabel(), $byStep->getLabel());
	}

	// =====================
	// getFactory tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getFactory
	 */
	public function testGetFactoryReturnsStatusFactory(): void
	{
		$factory = $this->repository->getFactory();

		$this->assertNotNull($factory);
		$this->assertInstanceOf(StatusFactory::class, $factory);
	}

	// =====================
	// getAll consistency tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getAll
	 */
	public function testGetAllReturnsConsistentResultsOnMultipleCalls(): void
	{
		$first = $this->repository->getAll();
		$second = $this->repository->getAll();

		$this->assertCount(count($first), $second);

		for ($i = 0; $i < count($first); $i++) {
			$this->assertEquals($first[$i]->getId(), $second[$i]->getId());
			$this->assertEquals($first[$i]->getStep(), $second[$i]->getStep());
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\StatusRepository::getAll
	 */
	public function testGetAllCountMatchesGetCount(): void
	{
		$statuses = $this->repository->getAll();
		$count = $this->repository->getCount();

		$this->assertEquals($count, count($statuses));
	}
}