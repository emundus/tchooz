<?php
/**
 * @package     Tchooz\Entities\Emails\Modifiers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails\Modifiers;

use NumberToWords\Exception\NumberToWordsException;
use NumberToWords\NumberToWords;
use Tchooz\Interfaces\TagModifierInterface;

class LettersModifier implements TagModifierInterface
{

	public function getName(): string
	{
		return 'LETTERS';
	}

	public function getLabel(): string
	{
		return 'Letters';
	}

	public function transform(string $value): string
	{
		if (!is_numeric($value)) {
			$value = \EmundusHelperFabrik::extractNumericValue($value);
		}
		$numberToWords = new NumberToWords();

		try
		{
			$numberTransformer = $numberToWords->getNumberTransformer('fr');

			return $numberTransformer->toWords($value);
		}
		catch (NumberToWordsException $e)
		{
			// Handle the exception as needed, e.g., log it or rethrow it
			throw new \RuntimeException('Error transforming number to words: ' . $e->getMessage());
		}

	}
}