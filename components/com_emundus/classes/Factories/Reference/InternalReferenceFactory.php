<?php
/**
 * @package     Tchooz\Factories\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Reference;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Reference\InternalReferenceEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Reference\PositionEnum;
use Tchooz\Enums\Reference\ResetTypeEnum;
use Tchooz\Enums\Reference\SeparatorEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\Mapping\MappingRowTransformationFactory;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Services\Reference\InternalReferenceFormat;
use Tchooz\Services\Reference\InternalReferenceFormatBlock;
use Tchooz\Services\Reference\InternalReferenceFormatSequence;

class InternalReferenceFactory implements DBFactory
{
	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];

		if($withRelations)
		{
			$campaignRepository = new CampaignRepository();
			$programRepository = new ProgramRepository();
			$applicationFileRepository = new ApplicationFileRepository();
		}

		foreach ($dbObjects as $dbObject)
		{
			if (is_array($dbObject))
			{
				$dbObject = (object) $dbObject;
			}

			$entities[] = self::buildEntity($dbObject, $campaignRepository ?? null, $programRepository ?? null, $applicationFileRepository ?? null);
		}

		return $entities;
	}

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): InternalReferenceEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		if($withRelations)
		{
			$campaignRepository = new CampaignRepository();
			$programRepository = new ProgramRepository();
			$applicationFileRepository = new ApplicationFileRepository();
		}

		return self::buildEntity($dbObject, $campaignRepository ?? null, $programRepository ?? null, $applicationFileRepository ?? null);
	}

	private static function buildEntity(object $dbObject, ?CampaignRepository $campaignRepository, ?ProgramRepository $programRepository, ?ApplicationFileRepository $applicationFileRepository): InternalReferenceEntity
	{
		// Check that created_at is a valid date before trying to create a DateTimeImmutable object
		$createdAt = new \DateTimeImmutable();
		if (!DateProvider::isNullableDate($dbObject->created_at))
		{
			$createdAt = new \DateTimeImmutable($dbObject->created_at);
		}

		return new InternalReferenceEntity(
			id: $dbObject->id,
			createdAt: $createdAt,
			reference: $dbObject->reference,
			sequence: $dbObject->sequence,
			campaign: $campaignRepository?->getById($dbObject->campaign),
			program: $programRepository?->getById($dbObject->program),
			year: $dbObject->year,
			applicantName: $dbObject->applicant_name,
			applicationFile: $applicationFileRepository?->getById($dbObject->ccid),
			active: $dbObject->active,
		);
	}

	public function unserialize(array $data): InternalReferenceFormat
	{
		$internalReferenceFormat = new InternalReferenceFormat([]);
		$blocks = [];
		if(!empty($data['blocks']))
		{
			$blocks = array_map(function (array $blockData) {
				return new InternalReferenceFormatBlock(
					type: ConditionTargetTypeEnum::tryFrom($blockData['type']),
					value: $blockData['value'],
					transformations: array_map(function (array $transformation) {
						return MappingRowTransformationFactory::fromJson($transformation);
					}, $blockData['transformations'])
				);
			}, $data['blocks']);
		}
		$internalReferenceFormat->setBlocks($blocks);

		$internalReferenceFormat->setSeparator(SeparatorEnum::from($data['separator']));

		$triggeringStatus = null;
		if(!is_null($data['triggering_status']))
		{
			$statusRepository       = new StatusRepository();
			$statusEntity           = $statusRepository->getByStep($data['triggering_status']);
			$triggeringStatus = $statusEntity;
		}
		$internalReferenceFormat->setTriggeringStatus($triggeringStatus);
		$internalReferenceFormat->setShowToApplicant($data['show_to_applicant']);
		$internalReferenceFormat->setShowInFiles($data['show_in_files']);
		if (!empty($data['sequence']))
		{
			$internalReferenceFormat->setSequence(new InternalReferenceFormatSequence(
				position: PositionEnum::from($data['sequence']['position']),
				resetType: ResetTypeEnum::from($data['sequence']['reset_type']),
				length: $data['sequence']['length']
			));
		}

		return $internalReferenceFormat;
	}
}