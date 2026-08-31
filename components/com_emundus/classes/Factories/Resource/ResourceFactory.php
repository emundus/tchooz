<?php
/**
 * @package     Tchooz\Factories\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Resource;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Resource\ResourceAccessEntity;
use Tchooz\Entities\Resource\ResourceDisplaySpaceEntity;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Enums\Resource\DisplaySpaceTypeEnum;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Repositories\Profile\ProfileRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\Resource\ResourceAccessRepository;
use Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository;
use Tchooz\Repositories\Resource\ResourceShareRepository;

class ResourceFactory
{
	/**
	 * @param   array<object>  $dbObjects
	 *
	 * @return array<ResourceEntity>
	 */
	public static function fromDbObjects(array $dbObjects, bool $withRelations = true): array
	{
		if (empty($dbObjects))
		{
			return [];
		}

		$accessRepository  = new ResourceAccessRepository();
		$displayRepository = new ResourceDisplaySpaceRepository();
		$shareRepository   = new ResourceShareRepository();

		$resources = [];
		foreach ($dbObjects as $dbObject)
		{
			$resources[] = self::build($dbObject, $withRelations, $accessRepository, $displayRepository, $shareRepository);
		}

		return $resources;
	}

	public static function fromDbObject(object $dbObject, bool $withRelations = true): ResourceEntity
	{
		return self::build(
			$dbObject,
			$withRelations,
			new ResourceAccessRepository(),
			new ResourceDisplaySpaceRepository(),
			new ResourceShareRepository()
		);
	}

	private static function build(
		object $dbObject,
		bool $withRelations,
		ResourceAccessRepository $accessRepository,
		ResourceDisplaySpaceRepository $displayRepository,
		ResourceShareRepository $shareRepository
	): ResourceEntity {
		$resource = new ResourceEntity(
			id: (int) $dbObject->id,
			name: $dbObject->name,
			format: $dbObject->format,
			filename: $dbObject->filename,
			size: (int) $dbObject->size,
			downloadCount: (int) $dbObject->download_count,
			folderId: $dbObject->folder_id !== null ? (int) $dbObject->folder_id : null,
			createdBy: (int) $dbObject->created_by,
			createdAt: new \DateTimeImmutable($dbObject->created_at ?? 'now')
		);

		if ($withRelations)
		{
			$access = $accessRepository->findByResource($resource->getId());
			foreach ($access as $entry)
			{
				$entry->setTargetLabel(self::resolveAccessLabel($entry));
				$entry->setTargetEmail(self::resolveTargetEmail($entry->getType(), $entry->getTargetId()));
			}
			$resource->setAccess($access);

			$displaySpaces = $displayRepository->findByResource($resource->getId());
			foreach ($displaySpaces as $space)
			{
				$space->setTargetLabel(self::resolveDisplaySpaceLabel($space));
			}
			$resource->setDisplaySpaces($displaySpaces);

			$resource->setShare($shareRepository->findByResource($resource->getId()));
		}

		return $resource;
	}

	private static function resolveAccessLabel(ResourceAccessEntity $access): string
	{
		return self::resolveTargetLabel($access->getType(), $access->getTargetId());
	}

	/**
	 * Display label of a share target (user name, group label or profile/role label). Shared by
	 * the file-access relation here and by the folder-access list in ResourceService, so both
	 * resolve labels the same way.
	 */
	public static function resolveTargetLabel(ResourceAccessTypeEnum $type, int $targetId): string
	{
		return match ($type)
		{
			ResourceAccessTypeEnum::USER  => self::resolveUserName($targetId),
			ResourceAccessTypeEnum::GROUP => (new GroupRepository(false))->getById($targetId)?->getLabel() ?? '',
			// A "role" is an eMundus profile (jos_emundus_setup_profiles).
			ResourceAccessTypeEnum::ROLE  => (new ProfileRepository(false))->getById($targetId)?->getLabel() ?? '',
		};
	}

	private static function resolveDisplaySpaceLabel(ResourceDisplaySpaceEntity $space): string
	{
		$targetId = $space->getTargetId();
		if (empty($targetId))
		{
			return '';
		}

		return match ($space->getType())
		{
			DisplaySpaceTypeEnum::CAMPAIGN => (new CampaignRepository(false))->getById($targetId)?->getLabel() ?? '',
			DisplaySpaceTypeEnum::PROGRAM  => (new ProgramRepository(false))->getById($targetId)?->getLabel() ?? '',
			// TODO: resolve form / public page labels once their source is confirmed.
			DisplaySpaceTypeEnum::FORM, DisplaySpaceTypeEnum::PUBLIC_PAGE => '',
		};
	}

	private static function resolveUserName(int $userId): string
	{
		if (empty($userId))
		{
			return '';
		}

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);

		return $user && $user->id ? $user->name : '';
	}

	/**
	 * Email of a share target, only meaningful for a user target (empty for roles/groups).
	 * Shared by the file-access relation and the folder-access list so the chip can show it.
	 */
	public static function resolveTargetEmail(ResourceAccessTypeEnum $type, int $targetId): string
	{
		if ($type !== ResourceAccessTypeEnum::USER || empty($targetId))
		{
			return '';
		}

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($targetId);

		return $user && $user->id ? $user->email : '';
	}
}
