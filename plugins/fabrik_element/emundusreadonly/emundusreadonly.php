<?php
/**
 * Fabrik Element - eMundus Read-Only
 *
 * Displays, in read-only mode inside a Fabrik form, the value of a field
 * coming from another form (application, management or profile) related
 * to the current fnum.
 *
 * Security model:
 *  - The source element id is stored as an integer reference.
 *  - The source element must belong to a form whose underlying table
 *    contains an `fnum` column (candidature / gestion) or to the profile
 *    table when `source_category = profile`.
 *  - Access is checked at render time via EmundusHelperAccess::asAccessAction
 *    on the current fnum, and never from the payload alone.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.emundusreadonly
 * @copyright   (C) 2008-present eMundus
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;

require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';

class PlgFabrik_ElementEmundusreadonly extends PlgFabrik_Element
{
	protected $fieldDesc = 'TEXT';

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		Log::addLogger(['text_file' => 'com_emundus.fabrik.readonly.php'], Log::ALL, ['com_emundus.fabrik.readonly']);
	}

	public function setEditable($editable)
	{
		$this->editable = false;
	}

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$layout = $this->getLayout('form');

		$displayData              = new stdClass();
		$displayData->id          = $this->getHTMLId($repeatCounter);
		$displayData->name        = $this->getHTMLName($repeatCounter);
		$displayData->value       = $this->getFormattedValue($data, (int) $repeatCounter);
		$displayData->rawValue    = $this->getRawValue($data, (int) $repeatCounter);
		$displayData->placeholder = (string) $params->get('empty_placeholder', '');

		return $layout->render($displayData);
	}

	public function renderListData($data, stdClass &$thisRow, $opts = [])
	{
		$value = $this->getFormattedValue(ArrayHelper::fromObject($thisRow));

		return parent::renderListData($value, $thisRow, $opts);
	}

	public function elementJavascript($repeatCounter)
	{
		return ['FbEmundusReadonly', $this->getHTMLId($repeatCounter), $this->getElementJSOptions($repeatCounter)];
	}

	/**
	 * Resolve and return the source value using the source plugin's own
	 * formatting (labels for choice elements). Returns '' if access is denied
	 * or if the source configuration is invalid — never raw error output.
	 *
	 * This is the value shown to the user and persisted through the hidden
	 * input rendered by the form layout.
	 */
	public function getFormattedValue($data, int $repeatCounter = 0): ?string
	{
		$value = $this->resolveSourceValue($data, (int) $repeatCounter, ValueFormatEnum::FORMATTED);

		if (is_array($value))
		{
			$value = implode(', ', $value);
		}

		return is_null($value) ? '' : (string) $value;
	}

	/**
	 * Resolve the source's RAW key(s): a scalar key for a single-value source,
	 * an array of keys for a multi-value source. Fed to the client-side
	 * show/hide condition engine (custom_form.js) so conditions compare on
	 * stable keys rather than volatile labels. Nothing is persisted from this —
	 * the stored value stays the formatted label (see getFormattedValue).
	 */
	public function getRawValue($data, int $repeatCounter = 0): mixed
	{
		return $this->resolveSourceValue($data, (int) $repeatCounter, ValueFormatEnum::RAW);
	}

	/**
	 * Shared resolution behind getFormattedValue() and getRawValue(): the only
	 * difference between the two is the requested value format.
	 *
	 * When the element is configured with adapt_to_repetitions = 1, the source
	 * is assumed to live in a joined repeating group; the value returned is
	 * the one at index $repeatCounter (so each evaluator-side repetition shows
	 * exactly one applicant-side entry). The TraitSyncRepeats trait, plugged
	 * into the host form plugin, is responsible for ensuring the evaluator
	 * group has the matching number of repetitions.
	 */
	private function resolveSourceValue($data, int $repeatCounter, ValueFormatEnum $format): mixed
	{
		$value = '';

		$params              = $this->getParams();
		$sourceId            = (int) $params->get('source_element_id', 0);
		$adaptToRepetitions  = (int) $params->get('adapt_to_repetitions', 0) === 1;
		$fnum                = $this->resolveCurrentFnum($data);

		if (empty($sourceId) || $fnum === null)
		{
			return '';
		}

		$user   = Factory::getApplication()->getIdentity();
		$userId = (int) $user->id;

		if ($userId === 0 || (!EmundusHelperAccess::asAccessAction(1, 'r', $userId, $fnum) && !EmundusHelperAccess::isFnumMine($userId, $fnum)))
		{
			Log::add(
				sprintf('access DENIED user=%d fnum=%s source_element=%d', $userId, $fnum, $sourceId),
				Log::WARNING,
				'com_emundus.fabrik.readonly'
			);

			return '';
		}

		$fabrikRepository = new FabrikRepository(true);
		$fabrikFactory = new FabrikFactory($fabrikRepository);
		$fabrikRepository->setFactory($fabrikFactory);
		$fabrikElement = $fabrikRepository->getElementById($sourceId);

		if (empty($fabrikElement))
		{
			Log::add('source element ' . $sourceId . ' not found', Log::ERROR, 'com_emundus.fabrik.readonly');
			return '';
		}

		if (!$this->isTableAllowed($fabrikElement->getDbTableName()))
		{
			Log::add('access DENIED for source ' . $sourceId . ' on fnum ' . $fnum . ': table ' . $fabrikElement->getDbTableName() . ' is not allowed', Log::WARNING, 'com_emundus.fabrik.readonly');
			return '';
		}

		if ($adaptToRepetitions)
		{
			return $this->getRepetitionValue($fabrikElement, $fnum, $repeatCounter, $format);
		}

		$context = new ActionTargetEntity($user, $fnum);
		$fabrikForm = $fabrikRepository->getFormFromElementId($sourceId);
		$fieldName = $fabrikForm->getId() . '.' . $sourceId;

		try
		{
			$value = (new FormDataConditionResolver())->resolveValue($context, $fieldName, $format);
		}
		catch (Throwable $e)
		{
			Log::add('resolver failed for source ' . $sourceId . ' on fnum ' . $fnum . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.fabrik.readonly');
		}

		return $value;
	}

	/**
	 * Pick the value at $repeatCounter from a source element living in a joined
	 * repeating group. Uses LEFT_JOIN export mode so the helper returns the
	 * per-repetition list instead of a CSV string.
	 */
	private function getRepetitionValue($fabrikElement, string $fnum, int $repeatCounter, ValueFormatEnum $format = ValueFormatEnum::FORMATTED): string
	{
		try
		{
			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
			}

			$values = (new \EmundusHelperFabrik())->getFabrikElementValues(
				$fabrikElement->toArray(false),
				[$fnum],
				0,
				$format,
				0,
				ExportModeEnum::LEFT_JOIN
			);

			$bag = $values[$fabrikElement->getId()][$fnum]['val'] ?? null;
			if (!is_array($bag))
			{
				return '';
			}

			return (string) ($bag[$repeatCounter] ?? '');
		}
		catch (Throwable $e)
		{
			Log::add(
				'repetition lookup failed for source ' . $fabrikElement->getId() . ' on fnum ' . $fnum . ' @ idx ' . $repeatCounter . ': ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.fabrik.readonly'
			);
			return '';
		}
	}

	/**
	 * Whitelist check:
	 *  - application / management → the table must expose a `fnum` column.
	 *  - joined repeating sub-table → its parent (via #__fabrik_joins) must
	 *    expose a `fnum` column. Without this, sources living in a repeating
	 *    group would always be rejected even though their parent form is a
	 *    valid candidate/management form.
	 */
	private function isTableAllowed(string $tableName): bool
	{
		$db      = Factory::getContainer()->get('DatabaseDriver');
		$columns = $db->getTableColumns($tableName, false);

		if (!is_array($columns))
		{
			return false;
		}

		if (array_key_exists('fnum', $columns))
		{
			return true;
		}

		if (!array_key_exists('parent_id', $columns))
		{
			return false;
		}

		$query = $db->createQuery()
			->select($db->quoteName('join_from_table'))
			->from($db->quoteName('#__fabrik_joins'))
			->where($db->quoteName('table_join') . ' = :tableName')
			->bind(':tableName', $tableName, ParameterType::STRING);
		$db->setQuery($query);
		$parentTable = $db->loadResult();

		if (empty($parentTable))
		{
			return false;
		}

		$parentColumns = $db->getTableColumns($parentTable, false);

		return is_array($parentColumns) && array_key_exists('fnum', $parentColumns);
	}

	/**
	 * Resolve the current fnum from the form model or the URL — never from a posted payload.
	 */
	private function resolveCurrentFnum(array $data): ?string
	{
		$fnum = null;

		foreach ($data as $key => $value)
		{
			if (str_ends_with($key, '___fnum'))
			{
				$fnum = $value;
				break;
			}
		}

		if (empty($fnum))
		{
			$fnum = Factory::getApplication()->getInput()->getString('fnum', '');
		}

		if (!empty($fnum) && !EmundusHelperAccess::isFnumMine(Factory::getApplication()->getIdentity()->id, $fnum) && !EmundusHelperAccess::asAccessAction(1, 'r', Factory::getApplication()->getIdentity()->id, $fnum))
		{
			$fnum = null;
		}

		return $fnum;
	}
}