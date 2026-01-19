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
use Tchooz\Interfaces\FabrikTransformerInterface;

class YesNoTransformer implements FabrikTransformerInterface
{
	protected array $translations;

	public function __construct(array $translations = [])
	{
		$this->translations = $translations;
	}

	public function transform(mixed $value, array $options = []): string
	{
		return $value == '1' ? $this->getTranslation('JYES') : $this->getTranslation('JNO');
	}

	private function getTranslation(string $key): string
	{
		return $this->translations[$key] ?? Text::_($key);
	}
}