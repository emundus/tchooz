<?php
/**
 * @package     Tchooz\Enums\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Actions;

use Joomla\CMS\Language\Text;

enum ActionEnum: string
{
	// File actions
	case FILE = 'file';
	case ATTACHMENT = 'attachment';
	case STATUS = 'status';
	case TAG = 'tag';
	case PUBLISH = 'publish';
	case MAIL_APPLICANT = 'mail_applicant';
	case COMMENT_FILE = 'comment_file';
	case MESSENGER = 'messenger';
	case ACCESS_FILE = 'access_file';
	case ACCESS_FILE_USERS = 'access_file_users';
	case COPY_FILE = 'copy_file';
	case SHARE_FILTERS = 'share_filters';
	case BOOKING = 'booking';
	case EXPORT_EXCEL = 'export_excel';
	case EXPORT_ZIP = 'export_zip';
	case EXPORT_PDF = 'export_pdf';
	case EXPORT = 'export';
	case EXPORT_TROMBINOSCOPE = 'export_trombinoscope';
	case EXPORT_DOC = 'export_doc';
	case EXTERNAL_EXPORT = 'external_export';
	case EXPORT_FICHE_DE_SYNTHESE = 'export fiche de synthese';
	case MAIL_EXPERT = 'mail_expert';
	case MAIL_EVALUATOR = 'mail_evaluator';
	case DECISION = 'decision';
	case ADMISSION = 'admission';
	case EVALUATION = 'evaluation';

	// Platform actions
	case CAMPAIGN = 'campaign';
	case PROGRAM = 'program';
	case EMAIL = 'email';
	case SMS = 'sms';
	case FORM = 'form';
	case WORKFLOW = 'workflow';
	case AUTOMATION = 'automation';
	case EVENT = 'event';
	case SIGN_REQUEST = 'sign_request';
	case PAYMENT = 'payment';
	case IMPORT = 'import';
	case CRM = 'crm';
	case CONTACT = 'contact';
	case ORGANIZATION = 'organization';
	case APPLICATION_CHOICES = 'application_choices';
	case LOGS = 'logs';

	// Users actions
	case USER = 'user';
	case ADD_USER = 'add_user';
	case EDIT_USER = 'edit_user';
	case EDIT_USER_ROLE = 'edit_user_role';
	case ACTIVATE_USER = 'activate_user';
	case DEACTIVATE_USER = 'deactivate_user';
	case SHOW_RIGHT_USER = 'show_right_user';
	case DELETE_USER = 'delete_user';
	case ADD_GROUP = 'add_group';
	case AFFECT_GROUP = 'affect_group';
	case MAIL_GROUP = 'mail_group';

	case UPDATE_OWNER = 'update_owner';

	public function getLabel(): string
	{
		return match ($this)
		{
			// File actions
			self::FILE => Text::_('COM_EMUNDUS_ACCESS_FILE_RIGHT'),
			self::ATTACHMENT => Text::_('COM_EMUNDUS_ACCESS_ATTACHMENT'),
			self::STATUS => Text::_('COM_EMUNDUS_ACCESS_STATUS'),
			self::TAG => Text::_('COM_EMUNDUS_ACCESS_TAGS'),
			self::PUBLISH => Text::_('COM_EMUNDUS_PUBLISH'),
			self::MAIL_APPLICANT => Text::_('COM_EMUNDUS_ACCESS_MAIL_APPLICANT'),
			self::COMMENT_FILE => Text::_('COM_EMUNDUS_ACCESS_COMMENT_FILE'),
			self::MESSENGER => Text::_('COM_EMUNDUS_MESSENGER'),
			self::ACCESS_FILE => Text::_('COM_EMUNDUS_ACCESS_ACCESS_FILE'),
			self::ACCESS_FILE_USERS => Text::_('COM_EMUNDUS_ACL_ACCESS_FILE_USERS'),
			self::COPY_FILE => Text::_('COM_EMUNDUS_COPY_FILE'),
			self::SHARE_FILTERS => Text::_('COM_EMUNDUS_SHARE_FILTERS'),
			self::BOOKING => Text::_('COM_EMUNDUS_ACL_BOOKING'),
			self::EXPORT_EXCEL => Text::_('COM_EMUNDUS_ACCESS_EXPORT_EXCEL'),
			self::EXPORT_ZIP => Text::_('COM_EMUNDUS_ACCESS_EXPORT_ZIP'),
			self::EXPORT_PDF => Text::_('COM_EMUNDUS_ACCESS_EXPORT_PDF'),
			self::EXPORT => Text::_('COM_EMUNDUS_ACL_EXPORT'),
			self::EXPORT_TROMBINOSCOPE => Text::_('COM_EMUNDUS_ACCESS_MULTI_LETTERS'),
			self::EXPORT_DOC => Text::_('COM_EMUNDUS_ACCESS_LETTERS'),
			self::EXTERNAL_EXPORT => Text::_('COM_EMUNDUS_EXTENAL_EXPORT'),
			self::EXPORT_FICHE_DE_SYNTHESE => Text::_('COM_EMUNDUS_FICHE_DE_SYNTHESE'),
			self::MAIL_EXPERT => Text::_('COM_EMUNDUS_ACCESS_MAIL_EXPERT'),
			self::MAIL_EVALUATOR => Text::_('COM_EMUNDUS_ACCESS_MAIL_EVALUATOR'),
			self::LOGS => Text::_('COM_EMUNDUS_ACCESS_LOGS'),
			self::DECISION => Text::_('COM_EMUNDUS_DECISION'),
			self::ADMISSION => Text::_('COM_EMUNDUS_ADMISSION'),
			self::EVALUATION => Text::_('COM_EMUNDUS_ACCESS_EVALUATION_RIGHT'),
			self::UPDATE_OWNER => Text::_('COM_EMUNDUS_ACL_UPDATE_OWNER'),

			// Platform actions
			self::CAMPAIGN => Text::_('COM_EMUNDUS_ACL_CAMPAIGN'),
			self::PROGRAM => Text::_('COM_EMUNDUS_ACL_PROGRAM'),
			self::EMAIL => Text::_('COM_EMUNDUS_ACL_EMAIL'),
			self::SMS => Text::_('COM_EMUNDUS_ACCESS_SMS'),
			self::FORM => Text::_('COM_EMUNDUS_ACL_FORM'),
			self::WORKFLOW => Text::_('COM_EMUNDUS_ACL_WORKFLOW'),
			self::AUTOMATION => Text::_('COM_EMUNDUS_AUTOMATION'),
			self::EVENT => Text::_('COM_EMUNDUS_ACL_EVENT'),
			self::SIGN_REQUEST => Text::_('COM_EMUNDUS_ACL_SIGN_REQUEST'),
			self::PAYMENT => Text::_('COM_EMUNDUS_ACL_PAYMENT'),
			self::IMPORT => Text::_('COM_EMUNDUS_ACL_IMPORT'),
			self::CRM => Text::_('CRM'),
			self::CONTACT => Text::_('COM_EMUNDUS_ACL_CONTACT'),
			self::ORGANIZATION => Text::_('COM_EMUNDUS_ACL_ORGANIZATION'),
			self::APPLICATION_CHOICES => Text::_('COM_EMUNDUS_ACL_APPLICATION_CHOICES'),

			// Users actions
			self::USER => Text::_('COM_EMUNDUS_ACCESS_USER'),
			self::ADD_USER => Text::_('COM_EMUNDUS_ADD_USER'),
			self::EDIT_USER => Text::_('COM_EMUNDUS_EDIT_USER'),
			self::EDIT_USER_ROLE => Text::_('COM_EMUNDUS_EDIT_USER_ROLE'),
			self::ACTIVATE_USER => Text::_('COM_EMUNDUS_ACTIVATE'),
			self::DEACTIVATE_USER => Text::_('COM_EMUNDUS_DEACTIVATE'),
			self::SHOW_RIGHT_USER => Text::_('COM_EMUNDUS_SHOW_RIGHT'),
			self::DELETE_USER => Text::_('COM_EMUNDUS_DELETE_USER'),
			self::ADD_GROUP => Text::_('COM_EMUNDUS_ACCESS_GROUPS'),
			self::AFFECT_GROUP => Text::_('COM_EMUNDUS_AFFECT'),
			self::MAIL_GROUP => Text::_('COM_EMUNDUS_ACCESS_MAIL_GROUP'),
		};
	}

	public function getDescription(): string
	{
		return match ($this)
		{
			// File actions
			self::FILE => Text::_('COM_EMUNDUS_ACCESS_FILE_DESC'),
			self::ATTACHMENT => Text::_('COM_EMUNDUS_ACCESS_ATTACHMENT_DESC'),
			self::STATUS => Text::_('COM_EMUNDUS_ACCESS_STATUS_DESC'),
			self::TAG => Text::_('COM_EMUNDUS_ACCESS_TAGS_DESC'),
			self::PUBLISH => Text::_('COM_EMUNDUS_PUBLISH_DESC'),
			self::MAIL_APPLICANT => Text::_('COM_EMUNDUS_ACCESS_MAIL_APPLICANT_DESC'),
			self::COMMENT_FILE => Text::_('COM_EMUNDUS_ACCESS_COMMENT_FILE_DESC'),
			self::MESSENGER => Text::_('COM_EMUNDUS_MESSENGER_DESC'),
			self::ACCESS_FILE => Text::_('COM_EMUNDUS_ACCESS_ACCESS_FILE_DESC'),
			self::ACCESS_FILE_USERS => Text::_('COM_EMUNDUS_ACL_ACCESS_FILE_USERS_DESC'),
			self::COPY_FILE => Text::_('COM_EMUNDUS_COPY_FILE_DESC'),
			self::SHARE_FILTERS => Text::_('COM_EMUNDUS_ACL_SHARE_FILTERS_DESC'),
			self::BOOKING => Text::_('COM_EMUNDUS_ACL_BOOKING_DESC'),
			self::EXPORT_EXCEL => Text::_('COM_EMUNDUS_ACCESS_EXPORT_EXCEL_DESC'),
			self::EXPORT_ZIP => Text::_('COM_EMUNDUS_ACCESS_EXPORT_ZIP_DESC'),
			self::EXPORT_PDF => Text::_('COM_EMUNDUS_ACCESS_EXPORT_PDF_DESC'),
			self::EXPORT => Text::_('COM_EMUNDUS_ACL_EXPORT_DESC'),
			self::EXPORT_TROMBINOSCOPE => Text::_('COM_EMUNDUS_ACCESS_MULTI_LETTERS_DESC'),
			self::EXPORT_DOC => Text::_('COM_EMUNDUS_ACCESS_LETTERS_DESC'),
			self::EXTERNAL_EXPORT => Text::_('COM_EMUNDUS_EXTENAL_EXPORT_DESC'),
			self::EXPORT_FICHE_DE_SYNTHESE => Text::_('COM_EMUNDUS_FICHE_DE_SYNTHESE_DESC'),
			self::MAIL_EXPERT => Text::_('COM_EMUNDUS_ACCESS_MAIL_EXPERT_DESC'),
			self::MAIL_EVALUATOR => Text::_('COM_EMUNDUS_ACCESS_MAIL_EVALUATOR_DESC'),
			self::LOGS => Text::_('COM_EMUNDUS_ACCESS_LOGS_DESC'),
			self::DECISION => Text::_('COM_EMUNDUS_DECISION_DESC'),
			self::ADMISSION => Text::_('COM_EMUNDUS_ADMISSION_DESC'),
			self::EVALUATION => Text::_('COM_EMUNDUS_ACCESS_EVALUATION_DESC'),
			self::UPDATE_OWNER => '',

			// Platform actions
			self::CAMPAIGN => Text::_('COM_EMUNDUS_ACL_CAMPAIGN_DESC'),
			self::PROGRAM => Text::_('COM_EMUNDUS_ACL_PROGRAM_DESC'),
			self::EMAIL => Text::_('COM_EMUNDUS_ACL_EMAIL_DESC'),
			self::SMS => Text::_('COM_EMUNDUS_ACCESS_SMS_DESC'),
			self::FORM => Text::_('COM_EMUNDUS_ACL_FORM_DESC'),
			self::WORKFLOW => Text::_('COM_EMUNDUS_ACL_WORKFLOW_DESC'),
			self::AUTOMATION => Text::_('COM_EMUNDUS_AUTOMATION_DESC'),
			self::EVENT => Text::_('COM_EMUNDUS_ACL_EVENT_DESC'),
			self::SIGN_REQUEST => '',
			self::PAYMENT => Text::_('COM_EMUNDUS_ACCESS_PAYMENT_DESC'),
			self::IMPORT => Text::_('COM_EMUNDUS_ACL_IMPORT_DESC'),
			self::CRM => '',
			self::CONTACT => Text::_('COM_EMUNDUS_ACL_CONTACT_DESC'),
			self::ORGANIZATION => Text::_('COM_EMUNDUS_ACL_ORGANIZATION_DESC'),
			self::APPLICATION_CHOICES => Text::_('COM_EMUNDUS_ACL_APPLICATION_CHOICES_DESC'),

			// Users actions
			self::USER => Text::_('COM_EMUNDUS_ACCESS_USER_DESC'),
			self::ADD_USER => Text::_('COM_EMUNDUS_ADD_USER_DESC'),
			self::EDIT_USER => Text::_('COM_EMUNDUS_EDIT_USER_DESC'),
			self::EDIT_USER_ROLE => '',
			self::ACTIVATE_USER => Text::_('COM_EMUNDUS_ACTIVATE_DESC'),
			self::DEACTIVATE_USER => Text::_('COM_EMUNDUS_DEACTIVATE_DESC'),
			self::SHOW_RIGHT_USER => Text::_('COM_EMUNDUS_SHOW_RIGHT_DESC'),
			self::DELETE_USER => Text::_('COM_EMUNDUS_DELETE_USER_DESC'),
			self::ADD_GROUP => Text::_('COM_EMUNDUS_ACCESS_GROUPS_DESC'),
			self::AFFECT_GROUP => Text::_('COM_EMUNDUS_AFFECT_DESC'),
			self::MAIL_GROUP => Text::_('COM_EMUNDUS_ACCESS_MAIL_GROUP_DESC'),
		};
	}

	public function getType(): ActionTypeEnum
	{
		return match ($this)
		{
			self::FILE,
			self::ATTACHMENT,
			self::STATUS,
			self::TAG,
			self::PUBLISH,
			self::MAIL_APPLICANT,
			self::COMMENT_FILE,
			self::MESSENGER,
			self::ACCESS_FILE,
			self::ACCESS_FILE_USERS,
			self::COPY_FILE,
			self::SHARE_FILTERS,
			self::BOOKING,
			self::EXPORT_EXCEL,
			self::EXPORT_ZIP,
			self::EXPORT_PDF,
			self::EXPORT,
			self::EXPORT_TROMBINOSCOPE,
			self::EXPORT_DOC,
			self::EXTERNAL_EXPORT,
			self::EXPORT_FICHE_DE_SYNTHESE,
			self::MAIL_EXPERT,
			self::MAIL_EVALUATOR,
			self::DECISION,
			self::ADMISSION,
			self::EVALUATION,
			self::LOGS,
			self::UPDATE_OWNER => ActionTypeEnum::FILE,

			self::CAMPAIGN,
			self::PROGRAM,
			self::EMAIL,
			self::SMS,
			self::FORM,
			self::WORKFLOW,
			self::AUTOMATION,
			self::EVENT,
			self::SIGN_REQUEST,
			self::PAYMENT,
			self::IMPORT,
			self::CRM,
			self::CONTACT,
			self::ORGANIZATION,
			self::APPLICATION_CHOICES => ActionTypeEnum::PLATFORM,

			self::USER,
			self::ADD_USER,
			self::EDIT_USER,
			self::EDIT_USER_ROLE,
			self::ACTIVATE_USER,
			self::DEACTIVATE_USER,
			self::SHOW_RIGHT_USER,
			self::DELETE_USER,
			self::ADD_GROUP,
			self::AFFECT_GROUP,
			self::MAIL_GROUP => ActionTypeEnum::USERS,
		};
	}

	public function getOrdering(): int
	{
		return match ($this)
		{
			// File actions
			self::FILE => 0,
			self::ATTACHMENT => 1,
			self::STATUS => 2,
			self::TAG => 3,
			self::PUBLISH => 4,
			self::MAIL_APPLICANT => 5,
			self::COMMENT_FILE => 6,
			self::MESSENGER => 7,
			self::ACCESS_FILE => 8,
			self::ACCESS_FILE_USERS => 9,
			self::COPY_FILE, self::UPDATE_OWNER => 10,
			self::SHARE_FILTERS => 11,
			self::BOOKING => 12,
			self::EXPORT_EXCEL => 14,
			self::EXPORT_ZIP => 15,
			self::EXPORT_PDF => 16,
			self::EXPORT => 17,
			self::EXPORT_TROMBINOSCOPE => 18,
			self::EXPORT_DOC => 19,
			self::EXTERNAL_EXPORT => 20,
			self::EXPORT_FICHE_DE_SYNTHESE => 21,
			self::LOGS => 22,
			self::MAIL_EXPERT => 23,
			self::MAIL_EVALUATOR => 24,
			self::DECISION => 25,
			self::ADMISSION => 26,
			self::EVALUATION => 27,

			// Platform actions
			self::CAMPAIGN => 100,
			self::PROGRAM => 101,
			self::EMAIL => 102,
			self::SMS => 103,
			self::FORM => 104,
			self::WORKFLOW => 105,
			self::AUTOMATION => 106,
			self::EVENT => 107,
			self::SIGN_REQUEST => 108,
			self::PAYMENT => 109,
			self::IMPORT => 110,
			self::CRM => 111,
			self::CONTACT => 112,
			self::ORGANIZATION => 113,
			self::APPLICATION_CHOICES => 114,

			// Users actions
			self::USER => 200,
			self::ADD_USER => 201,
			self::EDIT_USER => 202,
			self::EDIT_USER_ROLE => 203,
			self::ACTIVATE_USER => 204,
			self::DEACTIVATE_USER => 205,
			self::SHOW_RIGHT_USER => 206,
			self::DELETE_USER => 207,
			self::ADD_GROUP => 208,
			self::AFFECT_GROUP => 209,
			self::MAIL_GROUP => 210,
		};
	}

	/**
	 * Get all actions filtered by type.
	 *
	 * @param   ActionTypeEnum  $type
	 *
	 * @return  array<ActionEnum>
	 */
	public static function getByType(ActionTypeEnum $type): array
	{
		return array_filter(self::cases(), fn(self $action) => $action->getType() === $type);
	}

	/**
	 * Get all actions sorted by ordering.
	 *
	 * @return  array<ActionEnum>
	 */
	public static function sorted(): array
	{
		$cases = self::cases();
		usort($cases, fn(self $a, self $b) => $a->getOrdering() <=> $b->getOrdering());

		return $cases;
	}
}
