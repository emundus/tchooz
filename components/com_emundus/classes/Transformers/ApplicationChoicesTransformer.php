<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Transformers;

use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Interfaces\FabrikTransformerInterface;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;

class ApplicationChoicesTransformer implements FabrikTransformerInterface
{
	public function __construct()
	{}

	public function transform(mixed $value, array $options = []): string
	{
		if (!empty($value))
		{
			$formatted = $this->formatData($value);

			if (empty($formatted->choice))
			{
				return '';
			}

			assert($formatted->choice instanceof ApplicationChoicesEntity);

			return $formatted->choice->getCampaign()->getLabel();
		}
		else
		{
			return '';
		}
	}

	private function formatData($val): object
	{
		$formatted = new \stdClass();
		$formatted->choice = null;

		if (!empty($val) && str_contains($val, '|'))
		{
			$parts  = explode('|', $val);
			$id     = $parts[0];
			$status = $parts[1];
			$status = ChoicesStateEnum::tryFrom($status);

			if (!empty($id) && !empty($status))
			{
				$repository = new ApplicationChoicesRepository();
				$choice     = $repository->getById($id);

				$formatted->choice = $choice;
			}
		}

		return $formatted;
	}
}