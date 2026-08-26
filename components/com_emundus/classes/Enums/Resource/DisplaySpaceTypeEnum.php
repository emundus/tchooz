<?php
/**
 * @package     Tchooz\Enums\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Resource;

enum DisplaySpaceTypeEnum: string
{
	case FORM        = 'form';
	case CAMPAIGN    = 'campaign';
	case PROGRAM     = 'program';
	case PUBLIC_PAGE = 'public_page';
}
