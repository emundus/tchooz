<?php

namespace Tchooz\Enums\ApplicationFile;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\StringField;

enum ApplicationFileActionsEnum: string
{
	case RENAME = 'rename';
	case COPY = 'copy';
	case DOCUMENTS = 'documents';
	case HISTORY = 'history';
	case COLLABORATE = 'collaborate';
	case ANONYMOUS = 'anonymous';
	case CUSTOM = 'custom';
	case DELETE = 'delete';

	public function getLabel(): string
	{
		return match($this) {
			self::RENAME => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_RENAME'),
			self::COPY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COPY'),
			self::DOCUMENTS => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DOCUMENTS'),
			self::HISTORY => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_HISTORY'),
			self::COLLABORATE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_COLLABORATE'),
			self::ANONYMOUS => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_ANONYMOUS'),
			self::DELETE => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DELETE'),
			self::CUSTOM => Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_CUSTOM'),
		};
	}

	public function getIcon(): string
	{
		return match($this) {
			self::RENAME => 'drive_file_rename_outline',
			self::COPY => 'file_copy',
			self::DOCUMENTS => 'description',
			self::HISTORY => 'history',
			self::COLLABORATE => 'collaborate',
			self::ANONYMOUS => 'domino_mask',
			self::DELETE => 'delete',
			self::CUSTOM => 'rule_settings'
		};
	}

	public function getParameters(): array
	{
		return match($this)
		{
			self::RENAME => [
				new StringField('name', Text::_('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_RENAME_PARAM'), true),
			],
			default => []
		};
	}

	public function getOrdering(): int
	{
		return match($this)
		{
			self::RENAME => 0,
			self::COPY => 1,
			self::DOCUMENTS => 2,
			self::HISTORY => 3,
			self::COLLABORATE => 4,
			self::ANONYMOUS => 5,
			self::DELETE => 6,
			self::CUSTOM => 7,
		};
	}
}
