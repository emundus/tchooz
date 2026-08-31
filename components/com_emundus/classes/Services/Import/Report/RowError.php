<?php
/**
 * @package     Tchooz\Services\Import\Report
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Report;

use Tchooz\Enums\Import\ImportErrorCodeEnum;

/**
 * A single, language-neutral row failure.
 */
final class RowError
{
	/**
	 * @param string[] $params ordered arguments for the code's translation string
	 */
	public function __construct(
		public readonly ImportErrorCodeEnum $code,
		public readonly ?string             $field,
		public readonly array               $params
	) {}

	public function toArray(): array
	{
		return [
			'code'   => $this->code->value,
			'field'  => $this->field,
			'params' => $this->params,
		];
	}
}
