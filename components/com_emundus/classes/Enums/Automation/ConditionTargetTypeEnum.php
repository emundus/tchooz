<?php
namespace Tchooz\Enums\Automation;
use Joomla\CMS\Language\Text;

enum ConditionTargetTypeEnum: string
{
	case USERDATA = 'user_data';
	case FORMDATA = 'form_data';
	case ATTACHMENTDATA = 'attachment_data';
	case CAMPAIGNDATA = 'campaign_data';
	case PROGRAMDATA = 'program_data';
	case CONTEXTDATA = 'context_data';
	case GROUP_DATA = 'group_data';
	case DATE_RANGE = 'date_range';
	case CALCULATED = 'calculated';
	case FILEDATA = 'file_data';
	case FILEATTACHMENTDATA = 'file_attachment_data';
	case ALIASDATA = 'alias_data';
	case STATICVALUE = 'static_value';

	public function getLabel(): string
	{
		return match($this) {
			self::USERDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_USERDATA'),
			self::FORMDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_FORMDATA'),
			self::ATTACHMENTDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_ATTACHMENTDATA'),
			self::CAMPAIGNDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_CAMPAIGNDATA'),
			self::PROGRAMDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_PROGRAMDATA'),
			self::GROUP_DATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_GROUP_DATA'),
			self::CONTEXTDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_CONTEXTDATA'),
			self::DATE_RANGE => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_DATE_RANGE'),
			self::CALCULATED => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_CALCULATED'),
			self::FILEDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_FILEDATA'),
			self::FILEATTACHMENTDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_FILEATTACHMENTDATA'),
			self::ALIASDATA => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_ALIASDATA'),
			self::STATICVALUE => Text::_('COM_EMUNDUS_ENUM_CONDITION_TARGET_TYPE_STATICVALUE'),
		};
	}
}
