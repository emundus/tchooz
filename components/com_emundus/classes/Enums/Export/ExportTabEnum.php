<?php
/**
 * @package     Tchooz\Enums\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Export;

use Tchooz\Entities\Fields\FieldGroup;

enum ExportTabEnum: string
{
	case APPLICANT   = 'applicant';
	case MANAGEMENT  = 'management';
	case OTHER       = 'other';
	case ATTACHMENTS = 'attachments';
	case OPTIONS     = 'options';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::APPLICANT   => 'COM_EMUNDUS_EXPORTS_APPLICANTS_TAB',
			self::MANAGEMENT  => 'COM_EMUNDUS_EXPORTS_MANAGEMENTS_TAB',
			self::OTHER       => 'COM_EMUNDUS_EXPORTS_OTHER_TAB',
			self::ATTACHMENTS => 'COM_EMUNDUS_EXPORTS_ATTACHMENT_TAB',
			self::OPTIONS     => '',
		};
	}

	public function toFieldGroup(): FieldGroup
	{
		return new FieldGroup($this->value, $this->getLabel());
	}
}
