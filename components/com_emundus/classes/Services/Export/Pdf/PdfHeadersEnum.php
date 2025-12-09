<?php
/**
 * @package     Tchooz\Services\Export\Pdf
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Pdf;

enum PdfHeadersEnum: string
{
	case ID = 'id';
	case FNUM = 'fnum';
	case EMAIL = 'email';
	case SUBMITTED_DATE = 'submitted_date';
	case PRINTED_DATE = 'printed_date';
	case STATUS = 'status';
	case STICKERS = 'stickers';
}
