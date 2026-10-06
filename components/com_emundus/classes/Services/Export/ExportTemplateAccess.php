<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

use Tchooz\Entities\Automation\Actions\ActionPrintApplication;
use Tchooz\Repositories\Automation\ActionRepository;
use Tchooz\Repositories\Export\ExportRepository;

/**
 * Who may do what with a saved export template. A template belongs to the user who saved it, except
 * for the system ones a sysadmin shares with everybody allowed to export — and sharing a template
 * does not hand its deletion to everyone who can see it.
 */
class ExportTemplateAccess
{
	private ExportRepository $exportRepository;

	private ActionRepository $actionRepository;

	public function __construct(?ExportRepository $exportRepository = null, ?ActionRepository $actionRepository = null)
	{
		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}

		$this->exportRepository = $exportRepository ?? new ExportRepository();
		$this->actionRepository = $actionRepository ?? new ActionRepository();
	}

	/**
	 * @return object|null The template the user may load, null when it does not exist or belongs to
	 *                     somebody else.
	 */
	public function getReadable(int $id, int $userId): ?object
	{
		$exportTemplate = $this->exportRepository->getExportTemplate($id);

		if (empty($exportTemplate))
		{
			return null;
		}

		return $this->isOwner($exportTemplate, $userId) || $this->isSystem($exportTemplate) ? $exportTemplate : null;
	}

	/**
	 * @return object|null The template the user may rename, overwrite or delete, null when it does not
	 *                     exist or they may only read it.
	 */
	public function getWritable(int $id, int $userId): ?object
	{
		$exportTemplate = $this->exportRepository->getExportTemplate($id);

		if (empty($exportTemplate))
		{
			return null;
		}

		if ($this->isOwner($exportTemplate, $userId))
		{
			return $exportTemplate;
		}

		return $this->isSystem($exportTemplate) && $this->canFlagAsSystem($userId) ? $exportTemplate : null;
	}

	/**
	 * Flagging a template as a system one puts it in every user's list and in the parameters of the
	 * print automation: that stays a sysadmin decision.
	 */
	public function canFlagAsSystem(int $userId): bool
	{
		return (bool) \EmundusHelperAccess::isAdministrator($userId);
	}

	/**
	 * Automations printing with this template. Deleting it, or taking its system flag back off, drops it
	 * from the action's choices and the automation silently falls back to the default layout — so both
	 * are refused while it is still referenced.
	 *
	 * @return string[] Names of the automations, empty when the template is used by none.
	 */
	public function getAutomationsUsing(int $templateId): array
	{
		return $this->actionRepository->getAutomationNamesByActionParameter(
			ActionPrintApplication::getType(),
			ActionPrintApplication::TEMPLATE_PARAMETER,
			$templateId
		);
	}

	private function isOwner(object $exportTemplate, int $userId): bool
	{
		return (int) $exportTemplate->user === $userId;
	}

	private function isSystem(object $exportTemplate): bool
	{
		return !empty($exportTemplate->is_system);
	}
}
