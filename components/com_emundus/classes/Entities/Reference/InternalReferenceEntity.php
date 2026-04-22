<?php
/**
 * @package     Tchooz\Entities\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Reference;

use Joomla\CMS\Language\Text;
use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\Programs\ProgramEntity;

#[Table(name: '#__emundus_internal_reference')]
class InternalReferenceEntity
{
	private int $id;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private \DateTimeImmutable $createdAt;

	#[Column(length: 255)]
	private string $reference;

	#[Column(length: 10)]
	private ?string $sequence;

	#[Column(type: Types::INTEGER)]
	private ?int $sequenceInt;

	#[Column(type: Types::INTEGER)]
	private ?CampaignEntity $campaign;

	#[Column(type: Types::INTEGER)]
	private ?ProgramEntity $program;

	#[Column(length: 20)]
	private string $year;

	#[Column(length: 255)]
	private string $applicantName;

	#[Column(name: 'ccid', type: Types::INTEGER)]
	private ?ApplicationFileEntity $applicationFile;

	#[Column]
	private bool $active = true;

	/**
	 * @param   int                         $id
	 * @param   string                      $reference
	 * @param   string|null                 $sequence
	 * @param   CampaignEntity|null         $campaign
	 * @param   ProgramEntity|null          $program
	 * @param   string                      $year
	 * @param   string                      $applicantName
	 * @param   ApplicationFileEntity|null  $applicationFile
	 * @param   bool                        $active
	 */
	public function __construct(int $id = 0, \DateTimeImmutable $createdAt = new \DateTimeImmutable(), string $reference = '', ?string $sequence = '', ?CampaignEntity $campaign = null, ?ProgramEntity $program = null, string $year = '', string $applicantName = '', ?ApplicationFileEntity $applicationFile = null, bool $active = true)
	{
		$this->id              = $id;
		$this->createdAt       = $createdAt;
		$this->reference       = $reference;
		$this->sequence        = $sequence;
		$this->campaign        = $campaign;
		$this->program         = $program;
		$this->year            = $year;
		$this->applicantName   = $applicantName;
		$this->applicationFile = $applicationFile;
		$this->active          = $active;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeImmutable $createdAt): InternalReferenceEntity
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getReference(): string
	{
		return $this->reference;
	}

	public function setReference(string $reference): void
	{
		$this->reference = $reference;
	}

	public function getSequence(): ?string
	{
		return $this->sequence;
	}

	public function setSequence(?string $sequence): void
	{
		$this->sequence = $sequence;
	}

	public function getSequenceInt(): int
	{
		return $this->sequenceInt;
	}

	public function setSequenceInt(int $sequenceInt): InternalReferenceEntity
	{
		$this->sequenceInt = $sequenceInt;

		return $this;
	}

	public function getCampaign(): ?CampaignEntity
	{
		return $this->campaign;
	}

	public function setCampaign(CampaignEntity $campaign): void
	{
		$this->campaign = $campaign;
	}

	public function getProgram(): ?ProgramEntity
	{
		return $this->program;
	}

	public function setProgram(ProgramEntity $program): void
	{
		$this->program = $program;
	}

	public function getYear(): string
	{
		return $this->year;
	}

	public function setYear(string $year): void
	{
		$this->year = $year;
	}

	public function getApplicantName(): string
	{
		return $this->applicantName;
	}

	public function setApplicantName(string $applicantName): void
	{
		$this->applicantName = $applicantName;
	}

	public function getApplicationFile(): ?ApplicationFileEntity
	{
		return $this->applicationFile;
	}

	public function setApplicationFile(ApplicationFileEntity $applicationFile): void
	{
		$this->applicationFile = $applicationFile;
	}

	public function isActive(): bool
	{
		return $this->active;
	}

	public function setActive(bool $active): void
	{
		$this->active = $active;
	}

	public function getActiveHtml(): string
	{
		if(!$this->active)
		{
			return '';
		}

		$class = 'tw-bg-neutral-800 tw-text-white tw-flex tw-flex-row tw-items-center tw-gap-2 tw-px-2 tw-font-medium tw-text-sm tw-rounded-3xl tw-w-fit';

		return '<span class="'.$class.'">' . Text::_('COM_EMUNDUS_REFERENCE_ACTIVE') . '</span>';
	}

	public function getFullReference(): string
	{
		return $this->reference . '#' . $this->applicationFile->getShortReference();
	}

	public function __serialize(): array
	{
		return [
			'id'              => $this->id,
			'createdAt'       => $this->createdAt->format('Y-m-d H:i:s'),
			'reference'       => $this->reference,
			'sequence'        => $this->sequence,
			'campaign'        => $this->campaign?->__serialize(),
			'program'         => $this->program?->__serialize(),
			'year'            => $this->year,
			'applicantName'   => $this->applicantName,
			'applicationFile' => $this->applicationFile?->__serialize(),
			'active'          => $this->active,
		];
	}


}