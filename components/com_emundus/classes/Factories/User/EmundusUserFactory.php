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

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null): EmundusUserEntity
	{
		if (is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		$user         = null;
		$userCategory = null;
		if ($withRelations)
		{
			if (!empty($dbObject['user_id']))
			{
				$user = $this->userFactory->loadUserById($dbObject['user_id']);
			}
			$userCategoryRepository = new UserCategoryRepository();
			$userCategory           = $userCategoryRepository->getCategoryById((int) $dbObject['user_category']);
		}

		return new EmundusUserEntity(
			id: (int) $dbObject['id'],
			user: $user,
			firstname: $dbObject['firstname'],
			lastname: $dbObject['lastname'],
			profile_picture: $dbObject['profile_picture'] ?? null,
			user_category: $userCategory,
			is_anonym: isset($dbObject['is_anonym']) && $dbObject['is_anonym'] == 1
		);
	}
}