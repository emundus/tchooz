<?php
/**
 * @package     Tchooz\Traits
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

use Joomla\CMS\Factory;
use Tchooz\Entities\Logs\LogEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Logs\LogRepository;

/**
 * Gives any class the ability to write its own application file history entry, so that
 * journaling stays a responsibility of what performs the operation rather than of its callers.
 */
trait TraitLoggable
{
	private ?LogRepository $logRepository = null;

	/**
	 * @param   array  $params  Root key must be created / updated / deleted, matching $crud.
	 */
	protected function log(
		ActionEnum|string|int $action,
		CrudEnum $crud,
		string $message,
		array $params = [],
		?string $fnum = null,
		?int $userFrom = null,
		?int $userTo = null
	): bool
	{
		$author  = $this->getLogAuthor($userFrom);
		$context = $this->getLogContext();

		// The author is not always who caused the operation; keep the trigger when they differ.
		if (!empty($userFrom) && $userFrom !== $author)
		{
			$context['triggered_by'] = $userFrom;
		}

		$log = new LogEntity(
			userFrom: $author,
			action: $action,
			crud: $crud,
			message: $message,
			params: $params,
			fnum: $fnum,
			userTo: $userTo
		);

		$log->addParams($context);

		if ($this->logRepository === null)
		{
			$this->logRepository = new LogRepository();
		}

		return $this->logRepository->add($log);
	}

	/**
	 * Who the entry is attributed to. Defaults to the caller's user, then the current identity.
	 * Override when the class acts on its own behalf rather than on a user's.
	 */
	protected function getLogAuthor(?int $userFrom): int
	{
		return $userFrom ?? (int) (Factory::getApplication()->getIdentity()?->id ?? 0);
	}

	/**
	 * Context merged into the params of every entry written by this class. Override to describe
	 * where the operation came from.
	 */
	protected function getLogContext(): array
	{
		return [];
	}
}
