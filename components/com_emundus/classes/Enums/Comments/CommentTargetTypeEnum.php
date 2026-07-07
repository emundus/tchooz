<?php
/**
 * @package     Tchooz\Enums\Comments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Comments;

enum CommentTargetTypeEnum: string
{
	case CONTACT = 'contact';
	case ORGANIZATION = 'organization';
	case APPLICATION_FILE = 'application_file';
}
