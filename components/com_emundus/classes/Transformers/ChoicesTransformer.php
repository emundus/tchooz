<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Transformers;

use Joomla\CMS\Language\Text;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Interfaces\FabrikTransformerInterface;

class ChoicesTransformer implements FabrikTransformerInterface
{
	protected array $params;

	protected ElementPluginEnum $plugin;

	protected array $translations;

	public function __construct(array $params, ElementPluginEnum $plugin, array $translations = [])
	{
		$this->params = $params;
		$this->plugin = $plugin;
		$this->translations = $translations;
	}

	public function transform(mixed $value, array $options = []): string
	{
		if ($this->plugin === ElementPluginEnum::CHECKBOX || (!empty($this->params['multiple']) && $this->params['multiple'] == 1))
		{
			$decoded = json_decode($value);
			$arr     = is_array($decoded) ? $decoded : [];
		}
		else
		{
			$arr = $value === '' ? [] : explode(',', $value);
			$arr = array_map(function ($item) {return trim($item, '"');}, $arr);
		}

		if (count($arr) > 0 && !empty($this->params['sub_options']))
		{
			if(is_array($this->params['sub_options']))
			{
				$this->params['sub_options'] = (object) $this->params['sub_options'];
			}

			foreach ($arr as $k => $v)
			{
				$index   = array_search($v, $this->params['sub_options']->sub_values);
				$arr[$k] = $this->getTranslation($this->params['sub_options']->sub_labels[$index]);
			}

			return implode(', ', $arr);
		}
		else
		{
			return '';
		}
	}

	private function getTranslation(string $key): string
	{
		return $this->translations[$key] ?? Text::_($key);
	}
}