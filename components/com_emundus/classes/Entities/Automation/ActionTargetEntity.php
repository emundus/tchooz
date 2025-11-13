<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\User\User;

class ActionTargetEntity
{
	private User $triggeredBy;

	private ?string $file;

	private ?int $userId;

	private mixed $custom;

	private array $parameters;

	/**
	 * Original context that triggered the action, if any. before the action tried to access new target entities.
	 * @var ActionTargetEntity|null $originalContext
	 */
	private ?ActionTargetEntity $originalContext = null;

	public function __construct(?User $triggeredBy, ?string $file = null, ?int $userId = null, array $parameters = [], mixed $custom = null, ?ActionTargetEntity $originalContext = null)
	{
		$this->triggeredBy = $triggeredBy;
		$this->file = $file;
		$this->userId = $userId;
		$this->custom = $custom;
		$this->parameters = $parameters;
		$this->originalContext = $originalContext;
	}

	public function getTriggeredBy(): User
	{
		return $this->triggeredBy;
	}

	public function getFile(): ?string
	{
		return $this->file;
	}

	public function getUserId(): ?int
	{
		if (empty($this->userId)) {
			$this->userId = $this->getUserIdFromFile();
		}

		return $this->userId;
	}

	public function getUserIdFromFile(): ?int
	{
		if (!empty($this->getFile())) {
			if (!class_exists('EmundusHelperFiles')) {
				require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';
			}

			return \EmundusHelperFiles::getApplicantIdFromFileId($this->getFile(), 'fnum');
		}
		return null;
	}

	public function getCustom(): mixed
	{
		return $this->custom;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * @param   string  $key
	 * @param   mixed   $value
	 *
	 * @return void
	 */
	public function updateParameter(string $key, mixed $value): void
	{
		$this->parameters[$key] = $value;
	}

	/**
	 * @param   array  $parameters
	 *
	 * @return void
	 */
	public function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}

	/**
	 * @return ActionTargetEntity|null
	 */
	public function getOriginalContext(): ?ActionTargetEntity
	{
		return $this->originalContext;
	}

	public function setOriginalContext(?ActionTargetEntity $originalContext): void
	{
		$this->originalContext = $originalContext;
	}

	public function serialize(): array
	{
		return [
			'triggeredBy' => $this->getTriggeredBy()->id,
			'file' => $this->getFile(),
			'user' => $this->getUserId(),
			'custom' => $this->getCustom(),
			'parameters' => $this->getParameters(),
			'originalContext' => $this->originalContext && $this->originalContext !== $this ? $this->originalContext->serialize() : null,
		];
	}
}