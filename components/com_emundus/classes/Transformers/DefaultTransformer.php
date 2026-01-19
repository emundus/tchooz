<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Transformers;

use Tchooz\Interfaces\FabrikTransformerInterface;

class DefaultTransformer implements FabrikTransformerInterface
{
	protected array $params = [];

	public function __construct(array $params)
	{
		$this->params = $params;
	}

	public function transform(mixed $value, array $options = []): mixed
	{
		if(is_string($value) && isset($options['stripTags']) && $options['stripTags'] === true)
		{
			$value = strip_tags($value);
		}

		if(is_string($value) && !empty($this->params) && !empty($this->params['text_input_mask']))
		{
			$value = str_replace('_', '', $value);
		}

		return $value;
	}
}