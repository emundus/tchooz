<?php
/**
 * @package     Tchooz\Services\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Reference;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Reference\InternalReferenceEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Enums\Reference\PositionEnum;
use Tchooz\Enums\Reference\ResetTypeEnum;
use Tchooz\Factories\Reference\InternalReferenceFactory;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;
use Tchooz\Repositories\Settings\ConfigurationRepository;
use Tchooz\Services\Automation\ConditionRegistry;
use Tchooz\Services\Transformation\TransformationsRegistry;

class InternalReferenceService
{
	public function __construct(
		private DateProvider              $dateProvider,
		private readonly ApplicationFileRepository $applicationFileRepository
	)
	{
	}

	public function generatePreviewReferences(array $fnums, User $user = null): array
	{
		if(empty($user))
		{
			$user = Factory::getApplication()->getIdentity();
		}

		$internalReferenceRepository = new InternalReferenceRepository();
		$customReferenceFormatEntity = $this->getCustomReferenceFormatEntity();

		// Search if we have a sequential transformation in the format, if we do, we need to lock the generation to avoid generating the same reference for multiple files
		$sequentialType = null;
		if(!empty($customReferenceFormatEntity->getSequence()))
		{
			$sequentialType = $customReferenceFormatEntity->getSequence()->getResetType();
		}

		$references                = [];
		$referencesSerialized      = [];
		foreach ($fnums as $fnum)
		{
			$applicationFile = $this->applicationFileRepository->getByFnum($fnum);
			$target          = new ActionTargetEntity(
				$user,
				$applicationFile->getFnum(),
				$applicationFile->getUser()->id,
			);
			$reference       = $this->generateReference($customReferenceFormatEntity, $target);

			$oldReference = $internalReferenceRepository->getActiveReference($applicationFile->getId());

			// If we have a sequential transformation, we need to store references by a key depending of sequential type
			$sequentialKey = 'all';
			if (!empty($sequentialType))
			{
				switch ($sequentialType)
				{
					case ResetTypeEnum::YEARLY:
						$sequentialKey = $this->dateProvider->getCurrentYear();
						break;
					case ResetTypeEnum::CAMPAIGN:
						$sequentialKey = $applicationFile->getCampaign()->getId();
						break;
					case ResetTypeEnum::PROGRAM:
						$sequentialKey = $applicationFile->getCampaign()->getProgram()->getId();
						break;
				}

				if (!empty($references) && !empty($references[$sequentialKey]))
				{
					$lastReference = end($references[$sequentialKey]);
					if (!empty($lastReference))
					{
						assert($lastReference instanceof InternalReferenceEntity);

						$oldSequence = $reference->getSequence();
						$sequence    = $lastReference->getSequence();
						if (!empty($sequence))
						{
							$reference->setSequence(str_pad((int) $sequence + 1, strlen($sequence), '0', STR_PAD_LEFT));
							$reference->setReference(str_replace($oldSequence, $reference->getSequence(), $reference->getReference()));
						}
					}
				}
			}

			$references[$sequentialKey][] = $reference;

			$referencesSerialized[]       = [
				'applicant'       => $applicationFile->getUser()->name,
				'old_reference'   => $oldReference?->getReference(),
				'new_reference'   => $reference->getReference(),
				'short_reference' => $applicationFile->getShortReference()
			];
		}

		return $referencesSerialized;
	}

	public function generateReferences(array $fnums, User $user = null): void
	{
		if(empty($user))
		{
			$user = Factory::getApplication()->getIdentity();
		}

		$internalReferenceRepository = new InternalReferenceRepository();
		$customReferenceFormatEntity = $this->getCustomReferenceFormatEntity();

		// We have to generate again references to be sure to have the same references as in the generation step, if we don't do that, we can have different references if another reference with sequential transformation is generated between the generation and the saving
		$applicationFileRepository = new ApplicationFileRepository();
		foreach ($fnums as $fnum)
		{
			$applicationFile = $applicationFileRepository->getByFnum($fnum);
			$target          = new ActionTargetEntity(
				$user,
				$applicationFile->getFnum(),
				$applicationFile->getUser()->id,
			);
			$reference       = $this->generateReference($customReferenceFormatEntity, $target);

			if(!$internalReferenceRepository->flush($reference))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SAVING_REFERENCE'));
			}
		}
	}

	public function generateReference(InternalReferenceFormat $format, ActionTargetEntity $target): InternalReferenceEntity
	{
		$reference       = new InternalReferenceEntity();
		$stringReference = [];

		$conditionRegistry       = new ConditionRegistry();
		$transformationsRegistry = new TransformationsRegistry();
		$applicationFile         = $this->applicationFileRepository->getByFnum($target->getFile());

		$reference->setApplicationFile($applicationFile);
		$reference->setCampaign($applicationFile->getCampaign());
		$reference->setProgram($applicationFile->getCampaign()->getProgram());
		$reference->setApplicantName($applicationFile->getUser()->name);
		$reference->setYear($this->dateProvider->getCurrentYear());

		foreach ($format->getBlocks() as $block)
		{
			if (!empty($block->getType()))
			{
				$resolver = $conditionRegistry->getResolver($block->getType()->value);
				if (!empty($resolver))
				{
					$value = $resolver->resolveValue($target, $block->getValue());
					if (!empty($block->getTransformations()))
					{
						foreach ($block->getTransformations() as $transformation)
						{
							$transformationClass = $transformationsRegistry->getTransformer($transformation->getType());
							$transformationClass->setParametersValues($transformation->getParameters());
							$transformationClass->with($applicationFile);
							$transformationClass->with($this->dateProvider);
							$transformedValue = $transformationClass->transform($value);

							if ($transformationClass->getType() === MappingTransformersEnum::SEQUENTIAL)
							{
								$sequence = str_replace($value, '', $transformedValue);
								$reference->setSequence($sequence);
							}

							$value = $transformedValue;
						}
					}

					$stringReference[] = $value;
				}
				else
				{
					$stringReference[] = '';
				}
			}
		}

		// If the format has a sequence configuration but no sequential transformation, we need to generate the sequence based on existing references
		if (!empty($format->getSequence()))
		{
			$transformationClass = $transformationsRegistry->getTransformer(MappingTransformersEnum::SEQUENTIAL);
			$transformationClass->setParametersValues([
				'reset_type' => $format->getSequence()->getResetType()->value,
				'length'     => $format->getSequence()->getLength(),
			]);
			$transformationClass->with($applicationFile);
			$transformationClass->with($this->dateProvider);
			$sequenceValue = $transformationClass->transform('');
			$reference->setSequence($sequenceValue);

			// We add the sequence value with position defined
			if($format->getSequence()->getPosition() === PositionEnum::START)
			{
				array_unshift($stringReference, $sequenceValue);
			}
			else
			{
				$stringReference[] = $sequenceValue;
			}
		}

		$stringReference = implode($format->getSeparator()->value, $stringReference);
		$reference->setReference($stringReference);

		return $reference;
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFile
	 *
	 * @return string
	 *
	 *  Allow ~1 044 000 random references
	 */
	public function generateShortReference(ApplicationFileEntity $applicationFile): string
	{
		// Exclude confusing characters: O, I, L
		$alphabet = array_diff(range('A', 'Z'), ['O', 'I', 'L']);
		$alphabet = array_values($alphabet);

		// Digits 1-9 (excluding 0 to avoid confusion with O)
		$digits = range('1', '9');

		// Start with a random letter prefix to increase uniqueness
		$prefix = $alphabet[array_rand($alphabet)];

		// First attempt: deterministic suffix based on application file properties
		$deterministicRef = $prefix . $this->generateDeterministicSuffix($applicationFile, $digits, $alphabet);
		if (!$this->applicationFileRepository->checkShortReferenceExists($deterministicRef)) {
			return $deterministicRef;
		}

		// If deterministic reference exists, try random generation with a weighted approach
		$maxAttempts = 20;
		for ($i = 0; $i < $maxAttempts; $i++) {
			$suffix = $this->generateWeightedSuffix($digits, $alphabet);
			$shortRef = $prefix . $suffix;

			if (!$this->applicationFileRepository->checkShortReferenceExists($shortRef)) {
				return $shortRef;
			}
		}

		throw new \RuntimeException('Unable to generate a unique short reference after ' . $maxAttempts . ' attempts');
	}

	private function generateWeightedSuffix(array $digits, array $alphabet): string
	{
		$suffix = '';
		for ($j = 0; $j < 4; $j++) {
			// Create a pool with a 60% chance for digits and 40% for letters
			$pool = array_merge(
				array_fill(0, 3, null),
				array_fill(0, 2, null)
			);
			$index = array_rand($pool);
			if ($index < 3) {
				$suffix .= $digits[array_rand($digits)];
			} else {
				$suffix .= $alphabet[array_rand($alphabet)];
			}
		}
		return $suffix;
	}

	private function generateDeterministicSuffix(ApplicationFileEntity $applicationFile, array $digits, array $alphabet): string
	{
		$seed = implode('-', [
			$applicationFile->getCampaignId() ?? 0,
			$applicationFile->getUser()->id ?? 0,
			$applicationFile->getId() ?? 0,
		]);

		$hash = hash('sha256', $seed);
		$suffix = '';

		for ($j = 0; $j < 4; $j++) {
			$byte = hexdec(substr($hash, $j * 2, 2)); // 0-255

			// Use a weighted approach: 60% chance for digits, 40% for letters based on byte value
			if ($byte < 153) {
				$suffix .= $digits[$byte % count($digits)];
			} else {
				$suffix .= $alphabet[$byte % count($alphabet)];
			}
		}

		return $suffix;
	}

	public function setDateProvider(DateProvider $dateProvider): void
	{
		$this->dateProvider = $dateProvider;
	}

	public function getCustomReferenceFormatEntity(): InternalReferenceFormat
	{
		$customReferenceFactory = new InternalReferenceFactory();
		$customReferenceFormatEntity = new InternalReferenceFormat([]);

		$addonRepository = new ConfigurationRepository();
		$customReferenceFormat = $addonRepository->getByName('custom_reference_format');
		if (!empty($customReferenceFormat))
		{
			$customReferenceFormatEntity = $customReferenceFactory->unserialize($customReferenceFormat->getValue());
		}

		return $customReferenceFormatEntity;
	}
}