<?php
/**
 * @package     Tchooz\Entities\List
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\List;

use Joomla\CMS\Language\Text;
use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;

class AdditionalColumnPublished extends AdditionalColumn
{
	public function __construct(bool $isPublished, string $order_by = '', ListDisplayEnum $display = ListDisplayEnum::ALL)
	{
		$values = [];

		if ($isPublished)
		{
			$values[] = new AdditionalColumnTag(
				Text::_('COM_EMUNDUS_APPLICATION_PUBLISH'),
				Text::_('COM_EMUNDUS_APPLICATION_PUBLISHED'),
				'',
				'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm em-bg-main-500 tw-text-white'
			);
		} else
		{
			$values[] = new AdditionalColumnTag(
				Text::_('COM_EMUNDUS_APPLICATION_PUBLISH'),
				Text::_('COM_EMUNDUS_APPLICATION_UNPUBLISHED'),
				'',
				'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-bg-neutral-300 tw-text-neutral-700'
			);
		}

		parent::__construct(
			Text::_('COM_EMUNDUS_APPLICATION_PUBLISH'),
			'',
			$display,
			$order_by,
			'',
			$values,
			ListColumnTypesEnum::TAGS
		);
	}
}