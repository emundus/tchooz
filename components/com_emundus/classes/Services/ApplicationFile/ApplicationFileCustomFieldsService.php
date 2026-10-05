<?php
/**
 * @package     Tchooz\Services\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\ApplicationFile;

use Joomla\CMS\Factory;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;

/**
 * Platform-specific columns of #__emundus_campaign_candidature: the published elements
 * of the application file form that are not part of the core schema.
 */
class ApplicationFileCustomFieldsService
{
	const FORM_ID = 102;

	const NOT_IMPORTABLE_PLUGINS = [
		ElementPluginEnum::ID,
		ElementPluginEnum::DISPLAY,
		ElementPluginEnum::FILEUPLOAD,
		ElementPluginEnum::EMUNDUS_FILEUPLOAD,
		ElementPluginEnum::PANEL,
		ElementPluginEnum::BUTTON,
		ElementPluginEnum::CAPTCHA,
	];

	/**
	 * @var FabrikElementEntity[]|null
	 */
	private ?array $importableElements = null;

	public function __construct(
		private FabrikRepository $fabrikRepository = new FabrikRepository(true),
		private ?int $userId = null,
		private ApplicationFileRepository $applicationFileRepository = new ApplicationFileRepository(false)
	)
	{
	}

	/**
	 * @return FabrikElementEntity[]
	 */
	public function getImportableElements(): array
	{
		if ($this->importableElements !== null)
		{
			return $this->importableElements;
		}

		// Only elements backed by a real column of the table: a joined group stores its elements elsewhere
		$customColumns = $this->applicationFileRepository->getCustomColumnNames();
		if (empty($customColumns))
		{
			return $this->importableElements = [];
		}

		$elements = $this->fabrikRepository->getElements([
			'form_id'   => self::FORM_ID,
			'published' => 1,
			'name'      => $customColumns,
		], 0);

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}
		$allowedGroups = \EmundusHelperAccess::getUserFabrikGroups($this->userId ?? Factory::getApplication()->getIdentity()->id);

		$this->importableElements = array_values(array_filter($elements, function (FabrikElementEntity $element) use ($allowedGroups) {
			if (in_array($element->getPlugin(), self::NOT_IMPORTABLE_PLUGINS, true))
			{
				return false;
			}

			if ($allowedGroups !== true && is_array($allowedGroups) && !in_array($element->getGroupId(), $allowedGroups))
			{
				return false;
			}

			return !$this->isStoredInJoinTable($element);
		}));

		return $this->importableElements;
	}

	/**
	 * @return string[]
	 */
	public function getImportableColumnNames(): array
	{
		return array_map(fn(FabrikElementEntity $element) => $element->getName(), $this->getImportableElements());
	}

	public function filterImportableValues(array $values): array
	{
		return array_intersect_key($values, array_flip($this->getImportableColumnNames()));
	}

	private function isStoredInJoinTable(FabrikElementEntity $element): bool
	{
		if ((int) ($element->getGroupParams()->repeat_group_button ?? 0) === 1)
		{
			return true;
		}

		return $element->getPlugin() === ElementPluginEnum::DATABASEJOIN
			&& in_array($element->getParams()->database_join_display_type ?? '', ['checkbox', 'multilist'], true);
	}
}
