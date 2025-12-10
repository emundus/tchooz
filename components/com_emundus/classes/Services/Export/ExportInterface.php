<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

use Joomla\CMS\User\User;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Task\TaskEntity;

interface ExportInterface
{
	public function __construct(array $fnums, User $user, object|array|null $options, ExportEntity $exportEntity = null);

	public function export(string $exportPath, ?TaskEntity $task, ?string $langCode = 'fr-FR'): ExportResult;

	public static function getType(): string;
}