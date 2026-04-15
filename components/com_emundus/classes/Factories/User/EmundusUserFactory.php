<?php
/**
 * @package     Tchooz\Factories\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\User;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\User\UserCategoryRepository;

class EmundusUserFactory implements DBFactory
{
	private UserFactoryInterface $userFactory;

	public function __construct()
	{
		$this->userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
	}

	public function fromDbObjects(array $dbObjects, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = $this->fromDbObject($dbObject, $withRelations, $exceptRelations, $db);
		}

		return $entities;
	}

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null): EmundusUserEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		$user         = null;
		$userCategory = null;
		if ($withRelations)
		{
			if (!empty($dbObject->user_id))
			{
				$user = $this->userFactory->loadUserById($dbObject->user_id);
			}
			$userCategoryRepository = new UserCategoryRepository();
			$userCategory           = $userCategoryRepository->getCategoryById((int) $dbObject->user_category);
		}

		// Create a date from dd/mm/yyyy format
		$birthDate = null;
		if(!empty($dbObject->birth_date) && strpos($dbObject->birth_date, '/') !== false)
		{
			if (str_contains($dbObject->birth_date, ':'))
			{
				$birthDate = \DateTimeImmutable::createFromFormat('d/m/Y H:i:s', $dbObject->birth_date);
			}
			else
			{
				$birthDate = \DateTimeImmutable::createFromFormat('d/m/Y', $dbObject->birth_date);
			}

			// if createFromFormat fails, it returns false. In that case, we set birthDate to null
			if ($birthDate === false)
			{
				$birthDate = null;
			}
		}

		return new EmundusUserEntity(
			id: (int) $dbObject->id,
			user: $user,
			firstname: $dbObject->firstname,
			lastname: $dbObject->lastname,
			profile_picture: $dbObject->profile_picture ?? null,
			user_category: $userCategory,
			is_anonym: isset($dbObject->is_anonym) && $dbObject->is_anonym == 1,
			birthDate: $birthDate,
			emailCc: $dbObject->email_cc ?? null
		);
	}
}