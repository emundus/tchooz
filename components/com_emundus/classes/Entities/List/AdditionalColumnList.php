<?php
/**
 * @package     Tchooz\Entities\List
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\List;

use Tchooz\Enums\List\ListDisplayEnum;

class AdditionalColumnList extends AdditionalColumn
{
	const INITIAL_CLASSES = [
		'tw-flex',
		'tw-items-center',
		'tw-justify-center',
		'tw-w-6',
		'tw-h-6',
		'tw-rounded-full',
		'tw-bg-neutral-600',
		'tw-text-white',
		'tw-font-bold',
		'tw-mr-2',
		'tw-text-sm'
	];

	const IMG_CLASSES = [
		'tw-w-6',
		'tw-h-6',
		'tw-rounded-full',
		'tw-object-contain',
		'tw-mr-1'
	];

	const ITEM_CLASSES = [
		'tw-cursor-pointer',
		'tw-flex',
		'tw-items-center',
		'tw-mr-2',
		'tw-mb-2',
		'tw-h-max',
		'tw-font-semibold',
		'hover:tw-font-semibold',
		'hover:tw-underline',
		'tw-text-sm',
		'tw-bg-neutral-400',
		'tw-border',
		'tw-border-neutral-500',
		'tw-rounded-full',
		'tw-py-1',
		'tw-px-2'
	];

	public function __construct(
		string          $label,
		string          $multiple_label,
		string          $empty_label,
		array           $items,
		string          $nameKey,
		string          $editLink = '',
		string          $idKey = 'id',
		bool            $displayImage = false,
		?string         $imageKey = null,
		ListDisplayEnum $display = ListDisplayEnum::ALL
	)
	{
		$value = '';


		if (!empty($items))
		{
			if (!class_exists('EmundusHelperMenu'))
			{
				require_once JPATH_SITE . '/components/com_emundus/helpers/menu.php';
			}

			if (count($items) < 2)
			{
				$name = ItemAccessor::getAccessorValue($items[0], $nameKey);

				$img_tag = null;
				if ($displayImage)
				{
					$image = ItemAccessor::getAccessorValue($items[0], $imageKey);
					$img_tag = $this->renderImage($image ?? null, $name ?? '');
				}

				$end_tag = !empty($editLink) ? '</a>' : '</div>';
				if (!empty($editLink))
				{
					$menu_link = str_replace('{id}', ItemAccessor::getAccessorValue($items[0], $idKey), $editLink);
					$value     = '<a href="' . $menu_link . '" class="' . implode(' ', self::ITEM_CLASSES) . '">';
				}
				else
				{
					$value = '<div class="' . implode(' ', self::ITEM_CLASSES) . '">';
				}

				if (!empty($img_tag))
				{
					$value .= $img_tag;
				}

				$value .= $name . $end_tag;
			}
			else
			{
				$values = '<div>';

				$values .= '<h2 class="tw-mb-8 tw-text-center">' . $label . '</h2>';
				$values .= '<div class="tw-flex tw-flex-wrap">';
				foreach ($items as $item)
				{
					$id   = ItemAccessor::getAccessorValue($item, $idKey);
					$name = ItemAccessor::getAccessorValue($item, $nameKey);

					$img_tag = null;
					if ($displayImage)
					{
						$image = ItemAccessor::getAccessorValue($item, $imageKey);
						$img_tag = $this->renderImage($image ?? null, $name);
					}

					$end_tag = !empty($editLink) ? '</a>' : '</div>';
					if (!empty($editLink))
					{
						$menu_link = str_replace('{id}', $id, $editLink);
						$values    .= '<a href="' . $menu_link . '" class="' . implode(' ', self::ITEM_CLASSES) . '">';
					}
					else
					{
						$values .= '<div class="' . implode(' ', self::ITEM_CLASSES) . '">';
					}

					if (!empty($img_tag))
					{
						$values .= $img_tag;
					}

					$values .= $name . $end_tag;
				}
				$values .= '</div></div>';

				$value .= '<div><span class="' . implode(' ', self::ITEM_CLASSES) . '">' . count($items) . $multiple_label . '</span></div>';
			}
		}
		else
		{
			$value = $empty_label;
		}

		parent::__construct(
			$label,
			'',
			$display,
			'',
			$value,
			[],
			null,
			!empty($values) ? $values : null
		);
	}

	private function renderImage(?string $image, string $name): string
	{
		if (empty($image))
		{
			$initials = strtoupper(substr($name, 0, 1));
			return '<div class="' . implode(' ', self::INITIAL_CLASSES) . '">' . $initials . '</div>';
		}

		if (!preg_match('#^https?://#i', $image)) {
			$image = rtrim(\JUri::root(), '/') . '/' . ltrim($image, '/');
		}

		return '<img class="' . implode(' ', self::IMG_CLASSES) . '" src="' . $image . '" alt="' . $name . '">';
	}
}