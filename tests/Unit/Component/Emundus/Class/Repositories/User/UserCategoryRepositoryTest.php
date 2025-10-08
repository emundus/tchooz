<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\User;

use Joomla\Tests\Unit\UnitTestCase;
use Joomla\CMS\Factory;
use stdClass;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Contacts\ContactAddressEntity;
use Tchooz\Entities\User\UserCategoryEntity;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\CurrencyRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Repositories\User\UserCategoryRepository;

require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 */
class UserCategoryRepositoryTest extends UnitTestCase
{
	private array $categoriesFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->model = new UserCategoryRepository();
		$this->initDataSet();
	}

	public function createCategoriesFixtures(): void
	{
		$categoryEntity1 = new UserCategoryEntity(
			id: 0,
			label: 'Test Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);
		$categoryEntity2 = new UserCategoryEntity(
			id: 0,
			label: 'Another Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);
		$categoryEntity3 = new UserCategoryEntity(
			id: 0,
			label: 'Unpublished Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 0
		);
		$categories = [$categoryEntity1, $categoryEntity2, $categoryEntity3];

		foreach ($categories as $category) {
			$this->categoriesFixtures[] = $this->model->save($category);
		}
	}

	public function clearFixtures(): void
	{
		if (!empty($this->categoriesFixtures)) {
			foreach ($this->categoriesFixtures as $category) {
				$this->model->delete($category->getId());
			}
			$this->categoriesFixtures = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\UserCategoryRepository::getAllCategories
	 * @return void
	 */
	public function testGetAllCategories()
	{
		$this->createCategoriesFixtures();

		$user_categories = $this->model->getAllCategories();

		$this->assertIsArray($user_categories, 'The result is an array');
		$this->assertNotEmpty($user_categories, 'The result is not empty');

		// Assert only published categories are returned
		foreach ($user_categories as $category) {
			$this->assertEquals(1, $category->published, 'Only published categories are returned');
		}

		$all_user_categories = $this->model->getAllCategories(false);
		$this->assertIsArray($all_user_categories, 'The result is an array');
		$this->assertGreaterThan($user_categories, $all_user_categories, 'The result is greater when including unpublished categories');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\User\UserCategoryRepository::getCategoryById
	 * @return void
	 */
	public function testGetCategoryById()
	{
		$user_categories = $this->model->getCategoryById(0);

		$this->assertNull($user_categories, 'The result is null when not found');

		$this->createCategoriesFixtures();
		$category_id = $this->categoriesFixtures[0]->getId();
		$user_category = $this->model->getCategoryById($category_id);

		$this->assertInstanceOf(UserCategoryEntity::class, $user_category, 'The result is an instance of UserCategoryEntity');
		$this->assertEquals($category_id, $user_category->getId(), 'The category ID matches');
		$this->assertEquals('Test Category', $user_category->getLabel(), 'The category label matches');
		$this->assertEquals(1, $user_category->isPublished(), 'The category is published');
		$this->assertEquals(1, $user_category->getCreatedBy(), 'The category created_by matches');
		$this->assertNotEmpty($user_category->getCreatedAt(), 'The category created_at is not empty');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\User\UserCategoryRepository::save
	 * @return void
	 */
	public function testSave()
	{
		$categoryEntity = new UserCategoryEntity(
			id: 0,
			label: 'Test Save Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);

		$saved_category = $this->model->save($categoryEntity);

		$this->assertInstanceOf(UserCategoryEntity::class, $saved_category, 'The result is an instance of UserCategoryEntity');
		$this->assertGreaterThan(0, $saved_category->getId(), 'The category ID is greater than 0');
		$this->assertEquals('Test Save Category', $saved_category->getLabel(), 'The category label matches');
		$this->assertEquals(1, $saved_category->isPublished(), 'The category is published');
		$this->assertEquals(1, $saved_category->getCreatedBy(), 'The category created_by matches');
		$this->assertNotEmpty($saved_category->getCreatedAt(), 'The category created_at is not empty');

		// Test saving an existing category (update)
		$saved_category->setLabel('Updated Category Label');
		$updated_category = $this->model->save($saved_category);
		$this->assertInstanceOf(UserCategoryEntity::class, $updated_category, 'The result is an instance of UserCategoryEntity');
		$this->assertEquals($saved_category->getId(), $updated_category->getId(), 'The category ID matches after update');
		$this->assertEquals('Updated Category Label', $updated_category->getLabel(), 'The category label is updated');

		// Clean up
		$this->model->delete($saved_category->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\User\UserCategoryRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$categoryEntity = new UserCategoryEntity(
			id: 0,
			label: 'Test Delete Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);

		$saved_category = $this->model->save($categoryEntity);
		$this->assertGreaterThan(0, $saved_category->getId(), 'The category ID is greater than 0');

		$delete_result = $this->model->delete($saved_category->getId());
		$this->assertTrue($delete_result, 'The delete operation was successful');

		$deleted_category = $this->model->getCategoryById($saved_category->getId());
		$this->assertNull($deleted_category, 'The category is no longer found after deletion');
	}
}