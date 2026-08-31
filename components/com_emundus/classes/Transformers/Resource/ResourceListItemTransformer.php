<?php

namespace Tchooz\Transformers\Resource;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Enums\List\ListDisplayEnum;

/**
 * Build the list-view DTO for a resource/folder union row (id, name, created_at, size):
 * label + computed AdditionalColumns (creation date).
 *
 * Rows come from ResourceService::getResources(), which UNIONs resources and folders,
 * so the input is a plain stdClass — not a ResourceEntity.
 */
class ResourceListItemTransformer
{
	private UserFactoryInterface $userFactory;

	/**
	 * @param bool $fullAccess When true (managers holding the "resource" action), every row
	 *                         gets can_view/can_update/can_manage = true. Otherwise the flags
	 *                         are derived from the row's permission_rank (0..3) coming from
	 *                         the share (view/edit/manage).
	 */
	public function __construct(?UserFactoryInterface $userFactory = null, private bool $fullAccess = false)
	{
		$this->userFactory = $userFactory ?? Factory::getContainer()->get(UserFactoryInterface::class);
	}

	/**
	 * @param   object  $resource
	 *
	 * @return  object  Plain object ready to be JSON-encoded by EmundusResponse.
	 */
	public function transform(object $resource): object
	{
		// Work on a copy: the caller's row (a UNION stdClass) must not be mutated in place.
		$item = clone $resource;

		$rank = isset($resource->permission_rank) ? (int) $resource->permission_rank : 0;

		$item->can_view   = $this->fullAccess || $rank >= 1;
		$item->can_update = $this->fullAccess || $rank >= 2;
		$item->can_manage = $this->fullAccess || $rank >= 3;
		unset($item->permission_rank);

		// A shared row not yet opened by the user carries a "recently shared" badge. Managers
		// browsing the full tree (fullAccess) never see it.
		$item->is_new = !$this->fullAccess && !empty($resource->is_new);
		$item->badge  = $item->is_new
			? (object) ['label' => Text::_('COM_EMUNDUS_RESOURCES_RECENTLY_SHARED'), 'variant' => 'new']
			: null;

		$item->id                 = $resource->type . '-' . $resource->id;
		$item->label              = ['fr' => $resource->name, 'en' => $resource->name];
		$item->icon               = $this->resolveIcon($resource);
		$item->additional_columns = $this->buildAdditionalColumns($resource);

		return $item;
	}

	/**
	 * Resolve the emundus/ui Icon name for a row: a folder icon for folders,
	 * otherwise a file-type icon derived from the extension.
	 */
	private function resolveIcon(object $resource): string
	{
		if (($resource->type ?? '') === 'folder')
		{
			return 'folder';
		}

		$extensionIcons = [
			'pdf'  => 'pdf_file',
			'doc'  => 'word_file',
			'docx' => 'word_file',
			'xls'  => 'excel_file',
			'xlsx' => 'excel_file',
			'ppt'  => 'powerpoint_file',
			'pptx' => 'powerpoint_file',
			'png'  => 'png_file',
			'jpg'  => 'jpg_file',
			'jpeg' => 'jpg_file',
			'svg'  => 'svg_file',
			'zip'  => 'zip_file',
		];

		$extension = strtolower((string) ($resource->format ?? ''));

		return $extensionIcons[$extension] ?? 'draft';
	}

	/**
	 * @return AdditionalColumn[]
	 */
	private function buildAdditionalColumns(object $resource): array
	{
		if (!class_exists('EmundusHelperDate'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/helpers/date.php';
		}

		return [
			new AdditionalColumn(
				Text::_('COM_EMUNDUS_RESOURCES_COL_FORMAT'),
				'',
				ListDisplayEnum::ALL,
				'format',
				!empty($resource->format) ? $resource->format : '-'
			),
			new AdditionalColumn(
				Text::_('COM_EMUNDUS_RESOURCES_COL_CREATED_AT'),
				'',
				ListDisplayEnum::ALL,
				'created_at',
				!empty($resource->created_at)
					? \EmundusHelperDate::displayDate($resource->created_at, 'DATE_FORMAT_LC3', 0)
					: ''
			),
			new AdditionalColumn(
				Text::_('COM_EMUNDUS_RESOURCES_COL_SIZE'),
				'',
				ListDisplayEnum::ALL,
				'size',
				$this->formatBytes((int) ($resource->size ?? 0))
			),
			new AdditionalColumn(
				Text::_('COM_EMUNDUS_RESOURCES_COL_DOWNLOADS'),
				'',
				ListDisplayEnum::ALL,
				'download_count',
				isset($resource->download_count) && is_numeric($resource->download_count)
					? (string) $resource->download_count
					: '-'
			),
		];
	}

	/**
	 * Human-readable byte size (B, KB, MB, GB, TB).
	 */
	private function formatBytes(int $bytes, int $decimals = 2): string
	{
		if ($bytes <= 0)
		{
			return '0 o';
		}

		$units = ['o', 'Ko', 'Mo', 'Go', 'To'];
		$power = min((int) floor(log($bytes, 1024)), count($units) - 1);

		return round($bytes / (1024 ** $power), $decimals) . ' ' . $units[$power];
	}
}

