<?php

namespace Tchooz\Transformers\Language;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Enums\List\ListDisplayEnum;

class TranslationListItemTransformer
{
	public function transform(object $translation): object
	{
		$tag      = (string) $translation->tag;
		$override = (string) ($translation->override ?? '');

		$dto            = new \stdClass();
		$dto->id        = (int) $translation->id;
		$dto->tag       = $tag;
		$dto->lang_code = (string) $translation->lang_code;
		$dto->override  = $override;
		$dto->published = (int) ($translation->published ?? 1);

		$dto->label = ['fr' => $tag, 'en' => $tag];

		$dto->additional_columns = [
			new AdditionalColumn(
				key: Text::_('COM_EMUNDUS_LANGUAGES_COLUMN_TRANSLATION'),
				classes: 'tw-text-neutral-700',
				display: ListDisplayEnum::ALL,
				order_by: 'override',
				value: $override,
			),
		];

		return $dto;
	}

	/**
	 * @param   array<int, object>  $translations
	 *
	 * @return  array<int, object>
	 */
	public function transformAll(array $translations): array
	{
		return array_map(fn(object $translation) => $this->transform($translation), array_values($translations));
	}
}
