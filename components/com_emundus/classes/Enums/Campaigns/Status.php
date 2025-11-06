<?php
/**
 * @package     Tchooz\Enums\Campaigns
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Campaigns;

enum Status: string
{
	case UPCCOMING = 'upcoming';
	case OPEN = 'open';
	case CLOSED = 'closed';
}