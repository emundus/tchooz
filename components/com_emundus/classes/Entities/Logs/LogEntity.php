<?php
/**
 * @package     Tchooz\Entities\Logs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Logs;

use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;

/**
 * One row of #__emundus_logs, the application file history.
 *
 * The action is kept exactly as the caller declared it — an ActionEnum, an action name or a
 * raw id — and resolved to an id by LogRepository, which owns the actions referential.
 */
class LogEntity
{
	private int $id;

	private int $userFrom;

	private ?int $userTo;

	private ?string $fnum;

	private ActionEnum|string|int $action;

	private ?CrudEnum $crud;

	/**
	 * Language key rendered in the history view.
	 */
	private string $message;

	/**
	 * Root key must be created / updated / deleted, matching the crud verb.
	 * @see EmundusModelLogs::setActionDetails()
	 */
	private array $params;

	private ?string $timestamp;

	private ?string $ip;

	public function __construct(
		int $userFrom,
		ActionEnum|string|int $action,
		?CrudEnum $crud = null,
		string $message = '',
		array $params = [],
		?string $fnum = null,
		?int $userTo = null,
		?string $timestamp = null,
		?string $ip = null,
		int $id = 0
	)
	{
		$this->userFrom  = $userFrom;
		$this->action    = $action;
		$this->crud      = $crud;
		$this->message   = $message;
		$this->params    = $params;
		$this->fnum      = $fnum;
		$this->userTo    = $userTo;
		$this->timestamp = $timestamp;
		$this->ip        = $ip;
		$this->id        = $id;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getUserFrom(): int
	{
		return $this->userFrom;
	}

	public function setUserFrom(int $userFrom): void
	{
		$this->userFrom = $userFrom;
	}

	public function getUserTo(): ?int
	{
		return $this->userTo;
	}

	public function setUserTo(?int $userTo): void
	{
		$this->userTo = $userTo;
	}

	public function getFnum(): ?string
	{
		return $this->fnum;
	}

	public function setFnum(?string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getAction(): ActionEnum|string|int
	{
		return $this->action;
	}

	public function setAction(ActionEnum|string|int $action): void
	{
		$this->action = $action;
	}

	public function getCrud(): ?CrudEnum
	{
		return $this->crud;
	}

	public function setCrud(?CrudEnum $crud): void
	{
		$this->crud = $crud;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function setMessage(string $message): void
	{
		$this->message = $message;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	/**
	 * Merge extra context into the params, without overwriting what the caller already declared.
	 */
	public function addParams(array $params): void
	{
		$this->params = array_merge($params, $this->params);
	}

	public function getTimestamp(): ?string
	{
		return $this->timestamp;
	}

	public function setTimestamp(?string $timestamp): void
	{
		$this->timestamp = $timestamp;
	}

	public function getIp(): ?string
	{
		return $this->ip;
	}

	public function setIp(?string $ip): void
	{
		$this->ip = $ip;
	}
}
