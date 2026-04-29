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
		$displayData->value       = $this->getFormattedValue($data);
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
	 * Resolve and return the value from the source element, rendered using
	 * the source plugin's own formatting. Returns '' if access is denied or
	 * if the source configuration is invalid — never raw error output.
	 */
	public function getFormattedValue($data): ?string
	{
		$value = '';

		$params   = $this->getParams();
		$sourceId = (int) $params->get('source_element_id', 0);
		$fnum       = $this->resolveCurrentFnum($data);

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

		$context = new ActionTargetEntity($user, $fnum);
		$fabrikRepository = new FabrikRepository(true);
		$fabrikFactory = new FabrikFactory($fabrikRepository);
		$fabrikRepository->setFactory($fabrikFactory);
		$fabrikElement = $fabrikRepository->getElementById($sourceId);

		if (!$this->isTableAllowed($fabrikElement->getDbTableName()))
		{
			Log::add('access DENIED for source ' . $sourceId . ' on fnum ' . $fnum . ': table ' . $fabrikElement->getDbTableName() . ' is not allowed', Log::WARNING, 'com_emundus.fabrik.readonly');
			return '';
		}

		$fabrikForm = $fabrikRepository->getFormFromElementId($sourceId);
		$fieldName = $fabrikForm->getId() . '.' . $sourceId;

		try
		{
			$value = (new FormDataConditionResolver())->resolveValue($context, $fieldName, ValueFormatEnum::FORMATTED);
		}
		catch (Throwable $e)
		{
			Log::add('resolver failed for source ' . $sourceId . ' on fnum ' . $fnum . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.fabrik.readonly');
		}

		return $value;
	}

	/**
	 * Whitelist check:
	 *  - application / management → the table must expose a `fnum` column
	 *  - profile → the table must be one of the known profile tables
	 */
	private function isTableAllowed(string $tableName): bool
	{
		$columns = Factory::getContainer()->get('DatabaseDriver')->getTableColumns($tableName, false);

		return is_array($columns) && array_key_exists('fnum', $columns);
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