<?php
/**
 * @package     Tchooz\Repositories\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\User;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\User\UserCategoryEntity;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: 'data_user_category')]
class UserCategoryRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.alteration.php'], Log::ALL, ['com_emundus.repository.alteration']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function getAllCategories(bool $only_published = true): array
	{
		$categories = [];

		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->getTableName(self::class));

		if ($only_published) {
			$query->where('published = 1');
		}

		try
		{
			$this->db->setQuery($query);
			$categories = $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add('Error on get all user categories : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.usercategory');
		}

		return $categories;
	}

	public function getCategoryById(int $category_id): ?UserCategoryEntity
	{
		$category = null;

		if (!empty($category_id)) {
			$query = $this->db->createQuery();

			$query->select('*')
				->from($this->getTableName(self::class))
				->where('id = ' . $category_id);

			$this->db->setQuery($query);
			$category_row = $this->db->loadObject();

			if (!empty($category_row)) {
				$category = new UserCategoryEntity($category_row->id, $category_row->label, $category_row->created_by, $category_row->created_at, (bool)$category_row->published);
			}
		}

		return $category;
	}

	public function save(UserCategoryEntity $userCategory): ?UserCategoryEntity
	{
		$category = null;

		if (!empty($userCategory->getLabel())) {
			$insert = [
				'label'      => $userCategory->getLabel(),
				'created_by' => !empty($userCategory->getCreatedBy()) ? (int)$userCategory->getCreatedBy() : 'NULL',
				'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
				'published'  => $userCategory->isPublished() ? 1 : 0
			];
			$insert = (object) $insert;

			try {
				if(!empty($userCategory->getId()))
				{
					$insert->id = $userCategory->getId();
					if($this->db->updateObject($this->getTableName(self::class), $insert, 'id'))
					{
						$category = $userCategory;
					}
				}
				else {
					if($this->db->insertObject($this->getTableName(self::class), $insert))
					{
						$userCategory->setId($this->db->insertid());
						$category = $userCategory;
					}
				}
			} catch (\Exception $e) {
				Log::add('Error on create user category : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.usercategory');
			}
		}

		return $category;
	}

	public function delete(int $category_id): bool
	{
		$deleted = false;

		if (!empty($category_id)) {
			$query = $this->db->createQuery();

			$query->delete($this->getTableName(self::class))
				->where('id = ' . $category_id);

			try
			{
				$this->db->setQuery($query);
				$deleted = (bool)$this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete user category : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.usercategory');
			}
		}

		return $deleted;
	}
}