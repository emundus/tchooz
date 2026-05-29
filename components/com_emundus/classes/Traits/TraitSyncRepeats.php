<?php
/**
 * @copyright   (C) 2008-present eMundus
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Tchooz\Traits;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;

/**
 * Synchronizes the number of repetitions of a local joined repeating group
 * (the one hosting an emundusreadonly element) with the number of repetitions
 * of the source group pointed to by that element.
 *
 * Direction-agnostic: the "source" can live on either side of the candidate /
 * management divide, and the "local" group is just whichever side the form
 * being loaded belongs to. The trait only relies on the current fnum and on
 * the readonly element's source_element_id, not on the kind of form.
 *
 * Hosting plugin must implement getModel() returning the Fabrik form model.
 * Intended to be called from a form plugin's onLoad() hook, after Fabrik has
 * built $formModel->data via setJoinData().
 */
trait TraitSyncRepeats
{
	/**
	 * Walk the form's groups, detect joined repeating groups containing an
	 * emundusreadonly element configured with adapt_to_repetitions = 1, and
	 * pad the form data so the user sees one row per source repetition.
	 *
	 * Never throws: any failure is logged and skipped so the form load is
	 * not impacted.
	 */
	protected function syncReadonlyRepeats(): void
	{
		Log::addLogger(['text_file' => 'com_emundus.fabrik.syncrepeats.php'], Log::ALL, ['com_emundus.fabrik.syncrepeats']);

		try
		{
			$formModel = $this->getModel();
			if (empty($formModel))
			{
				return;
			}

			$fnum = $this->resolveSyncRepeatsFnum($formModel);
			if (empty($fnum))
			{
				return;
			}

			$groups = $formModel->getGroupsHiarachy();
			if (empty($groups))
			{
				return;
			}

			$fabrikRepository = new FabrikRepository(true);
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
			}
			$fabrikHelper = new \EmundusHelperFabrik();

			foreach ($groups as $groupModel)
			{
				if (!$groupModel->isJoin() || !$groupModel->canRepeat())
				{
					continue;
				}

				$sourceElementId = $this->findReadonlySourceInGroup($groupModel);
				if ($sourceElementId === null)
				{
					continue;
				}

				$sourceElement = $fabrikRepository->getElementById($sourceElementId);
				if (empty($sourceElement))
				{
					Log::add(
						sprintf('source element %d not found for group %d', $sourceElementId, $groupModel->getId()),
						Log::WARNING,
						'com_emundus.fabrik.syncrepeats'
					);
					continue;
				}

				$sourceCount = $this->countSourceRepetitions($fabrikHelper, $sourceElement, $fnum);
				if ($sourceCount <= 0)
				{
					continue;
				}

				$this->padLocalGroup($formModel, $groupModel, $sourceCount);
			}
		}
		catch (\Throwable $e)
		{
			Log::add(
				'syncReadonlyRepeats failed: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
				Log::ERROR,
				'com_emundus.fabrik.syncrepeats'
			);
		}
	}

	private function resolveSyncRepeatsFnum($formModel): ?string
	{
		$data = $formModel->data ?? [];

		if (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (!is_string($key) || !str_ends_with($key, '___fnum') || empty($value))
				{
					continue;
				}

				$candidate = is_array($value) ? reset($value) : $value;
				if (!empty($candidate))
				{
					return (string) $candidate;
				}
			}
		}

		$fnum = Factory::getApplication()->getInput()->getString('fnum', '');

		return !empty($fnum) ? $fnum : null;
	}

	private function findReadonlySourceInGroup($groupModel): ?int
	{
		$elementModels = $groupModel->getMyElements();
		if (empty($elementModels))
		{
			return null;
		}

		foreach ($elementModels as $elementModel)
		{
			$element = $elementModel->getElement();

			if ($element->plugin !== ElementPluginEnum::EMUNDUSREADONLY->value || empty($element->published))
			{
				continue;
			}

			$params = $elementModel->getParams();
			if ((int) $params->get('adapt_to_repetitions', 0) !== 1)
			{
				continue;
			}

			$sourceElementId = (int) $params->get('source_element_id', 0);
			if ($sourceElementId > 0)
			{
				return $sourceElementId;
			}
		}

		return null;
	}

	private function countSourceRepetitions(\EmundusHelperFabrik $fabrikHelper, FabrikElementEntity $sourceElement, string $fnum): int
	{
		$sourceArray = $sourceElement->toArray(false);

		$values = $fabrikHelper->getFabrikElementValues(
			$sourceArray,
			[$fnum],
			0,
			ValueFormatEnum::RAW,
			0,
			ExportModeEnum::LEFT_JOIN
		);

		$bag = $values[$sourceElement->getId()][$fnum]['val'] ?? null;
		if (is_array($bag))
		{
			return count($bag);
		}

		return 0;
	}

	private function padLocalGroup($formModel, $groupModel, int $targetCount): void
	{
		$elementModels = $groupModel->getMyElements();
		if (empty($elementModels))
		{
			return;
		}

		$joinModel = $groupModel->getJoinModel();
		if (empty($joinModel))
		{
			return;
		}

		$join = $joinModel->getJoin();
		$repeatTable = $join->table_join ?? null;
		if (empty($repeatTable))
		{
			return;
		}

		$pkKey = $repeatTable . '___id';
		if (isset($formModel->data[$pkKey]) && is_array($formModel->data[$pkKey]))
		{
			$currentCount = count($formModel->data[$pkKey]);
		}
		elseif (isset($formModel->data[$pkKey]))
		{
			$currentCount = 1;
		}
		else
		{
			$currentCount = 0;
		}

		if ($currentCount >= $targetCount)
		{
			return;
		}

		$missing = $targetCount - $currentCount;

		$technicalKeys = [
			$repeatTable . '___id',
			$repeatTable . '___id_raw',
			$repeatTable . '___parent_id',
			$repeatTable . '___parent_id_raw',
		];

		$elementKeys = [];
		foreach ($elementModels as $elementModel)
		{
			$fullName = $elementModel->getFullName(true, false);
			$elementKeys[] = $fullName;
			$elementKeys[] = $fullName . '_raw';
		}

		foreach (array_merge($technicalKeys, $elementKeys) as $key)
		{
			if (!isset($formModel->data[$key]))
			{
				$formModel->data[$key] = [];
			}
			elseif (!is_array($formModel->data[$key]))
			{
				$formModel->data[$key] = [$formModel->data[$key]];
			}
		}

		for ($i = 0; $i < $missing; $i++)
		{
			foreach ($technicalKeys as $key)
			{
				$formModel->data[$key][] = '';
			}
			foreach ($elementKeys as $key)
			{
				$formModel->data[$key][] = '';
			}
		}

		Log::add(
			sprintf(
				'padded group %d on form %d: +%d rows (current %d, target %d)',
				$groupModel->getId(),
				$formModel->getId(),
				$missing,
				$currentCount,
				$targetCount
			),
			Log::INFO,
			'com_emundus.fabrik.syncrepeats'
		);
	}
}
