<?php
/**
 * @package     Tchooz\Entities\List
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\List;

use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;

class AdditionalColumn
{
	public function __construct(
		public string $key,
		public string $classes,
		public ListDisplayEnum $display = ListDisplayEnum::TABLE,
		public string $order_by = '',
		public string $value = '',
		public array $values = [],
		public ?ListColumnTypesEnum $type = null,
		public ?string $long_value = null,
	)
	{
	}

	public function setLongValue(?string $long_value): void
	{
		$this->long_value = $long_value;
	}
}