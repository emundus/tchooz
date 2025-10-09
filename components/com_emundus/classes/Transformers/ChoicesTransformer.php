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
use Tchooz\Enums\Fabrik\ElementPlugin;
use Tchooz\Interfaces\FabrikTransformerInterface;

class ChoicesTransformer implements FabrikTransformerInterface
{
	protected array $params;

	protected ElementPlugin $plugin;

	public function __construct(array $params, ElementPlugin $plugin)
	{
		$this->params = $params;
		$this->plugin = $plugin;
	}

	public function transform(mixed $value): string
	{
		if ($this->plugin === ElementPlugin::CHECKBOX || (!empty($this->params['multiple']) && $this->params['multiple'] == 1))
		{
			$decoded = json_decode($value);
			$arr     = is_array($decoded) ? $decoded : [];
		}
		else
		{
			$arr = $value === '' ? [] : explode(',', $value);
		}

		if (count($arr) > 0)
		{
			foreach ($arr as $k => $v)
			{
				$index   = array_search($v, $this->params['sub_options']->sub_values);
				$arr[$k] = Text::_($this->params['sub_options']->sub_labels[$index]);
			}

			return implode(', ', $arr);
		}
		else
		{
			return '';
		}
	}
}