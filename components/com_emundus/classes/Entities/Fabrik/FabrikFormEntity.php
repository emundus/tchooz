<?php
/**
 * @package     Tchooz\Entities\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Fabrik;

use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

class FabrikFormEntity
{
	private int $id;

	private string $label;

	private bool $recordInDatabase = true;

	private string $errorMessage = 'FORM_ERROR';

	private string $intro = '';

	private \DateTime $created;

	private User $createdBy;

	private \DateTime $modified;

	private ?User $modifiedBy = null;

	private string $resetButtonLabel = 'RESET';

	private string $submitButtonLabel = 'SAVE_CONTINUE';

	private string $formTemplate = 'emundus';

	private string $viewOnlyTemplate = 'emundus';

	private bool $published = true;

	private int $private = 0;

	private ?FabrikFormParams $params = null;

	private string $paramsRaw = '';

	/**
	 * @var array<FabrikGroupEntity>
	 */
	private array $groups = [];

	// TODO: Link FabrikListEntity $list;

	public function __construct(
		int $id,
		string $label,
		string $intro,
		\DateTime $created,
		User $createdBy
	)
	{
		$this->id        = $id;
		$this->label     = $label;
		$this->intro     = $intro;
		$this->created   = $created;
		$this->createdBy = $createdBy;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function isRecordInDatabase(): bool
	{
		return $this->recordInDatabase;
	}

	public function setRecordInDatabase(bool $recordInDatabase): void
	{
		$this->recordInDatabase = $recordInDatabase;
	}

	public function getErrorMessage(): string
	{
		return $this->errorMessage;
	}

	public function setErrorMessage(string $errorMessage): void
	{
		$this->errorMessage = $errorMessage;
	}

	public function getIntro(): string
	{
		return $this->intro;
	}

	public function setIntro(string $intro): void
	{
		$this->intro = $intro;
	}

	public function getCreated(): \DateTime
	{
		return $this->created;
	}

	public function setCreated(\DateTime $created): void
	{
		$this->created = $created;
	}

	public function getCreatedBy(): User
	{
		return $this->createdBy;
	}

	public function setCreatedBy(User $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getModified(): \DateTime
	{
		return $this->modified;
	}

	public function setModified(\DateTime $modified): void
	{
		$this->modified = $modified;
	}

	public function getModifiedBy(): ?User
	{
		return $this->modifiedBy;
	}

	public function setModifiedBy(?User $modifiedBy): void
	{
		$this->modifiedBy = $modifiedBy;
	}

	public function getResetButtonLabel(): string
	{
		return $this->resetButtonLabel;
	}

	public function setResetButtonLabel(string $resetButtonLabel): void
	{
		$this->resetButtonLabel = $resetButtonLabel;
	}

	public function getSubmitButtonLabel(): string
	{
		return $this->submitButtonLabel;
	}

	public function setSubmitButtonLabel(string $submitButtonLabel): void
	{
		$this->submitButtonLabel = $submitButtonLabel;
	}

	public function getFormTemplate(): string
	{
		return $this->formTemplate;
	}

	public function setFormTemplate(string $formTemplate): void
	{
		$this->formTemplate = $formTemplate;
	}

	public function getViewOnlyTemplate(): string
	{
		return $this->viewOnlyTemplate;
	}

	public function setViewOnlyTemplate(string $viewOnlyTemplate): void
	{
		$this->viewOnlyTemplate = $viewOnlyTemplate;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getPrivate(): int
	{
		return $this->private;
	}

	public function setPrivate(int $private): void
	{
		$this->private = $private;
	}

	public function getParams(): ?FabrikFormParams
	{
		return $this->params;
	}

	public function setParams(?FabrikFormParams $params): void
	{
		$this->params = $params;
	}

	public function getParamsRaw(): string
	{
		return $this->paramsRaw;
	}

	public function setParamsRaw(string $paramsRaw): void
	{
		$this->paramsRaw = $paramsRaw;
	}

	public function getGroups(): array
	{
		return $this->groups;
	}

	public function setGroups(array $groups): void
	{
		$this->groups = $groups;
	}

	public function __serialize(): array
	{
		$serialized = [
			'id' => $this->id,
			'label' => strip_tags(Text::_($this->label)),
		];

		if(!empty($this->groups))
		{
			foreach($this->groups as $group)
			{
				assert($group instanceof FabrikGroupEntity);
				$serialized['groups'][] = $group->__serialize();
			}
		}

		return $serialized;
	}
}