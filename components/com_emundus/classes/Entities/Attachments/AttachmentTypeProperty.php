<?php
/**
 * @package     Tchooz\Entities\Attachments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Attachments;

class AttachmentTypeProperty
{
	private ?int $videoMaxLength;

	private ?int $minWidth;

	private ?int $minHeight;

	private ?int $maxWidth;

	private ?int $maxHeight;

	private ?int $minPages;

	private ?int $maxPages;

	private bool $sync = false;

	private ?string $syncMethod;

	private ?int $maxFileSize;

	private ?int $imgMinWidth;

	private ?int $imgMinHeight;

	private ?int $imgMaxWidth;

	private ?int $imgMaxHeight;
}