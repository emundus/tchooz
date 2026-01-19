<?php
/**
 * @package     Tchooz\Services\Language\Objects
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Language\Objects;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Services\Language\Objects\Definition\ObjectDefinition;
use Tchooz\Services\Language\Objects\Definition\ObjectDefinitionFields;
use Tchooz\Services\Language\Objects\Definition\ObjectDefinitionTable;

class ObjectStatus implements ObjectInterface
{
	public function getType(): string
	{
		return 'emundus_setup_status';
	}

	public function getName(): string
	{
		return Text::_('COM_EMUNDUS_STATUS');
	}

	public function getDescription(): string
	{
		return '';
	}

	public function getDatas(array $filters = []): array
	{
		try
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('step as id, value as label')
				->from($db->quoteName('#__emundus_setup_status'));
			$db->setQuery($query);
			$datas = $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException("Error fetching data for ObjectCampaigns: " . $e->getMessage());
		}

		return $datas;
	}

	public function getDefinition(): ObjectDefinition
	{
		$tableDefinition = new ObjectDefinitionTable(
			'emundus_setup_status',
			'step',
			'value',
			[],
			true,
			'falang',
			false,
			false
		);

		$fields = [
			[
				'Type' => 'field',
				'Name' => 'value',
				'Label' => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_STATUS_NAME'),
				'Table' => '',
				'Options' => '',
			]
		];

		$indexedFields = [];
		foreach ($fields as $field) {
			$indexedFields[$field['Name']] = $field;
		}
		$sections = [
			[
				'Label' => '',
				'Name' => 'emundus_setup_status',
				'Table' => '',
				'TableJoin' => '',
				'TableJoinColumn' => '',
				'ReferenceColumn' => '',
				'indexedFields' => $indexedFields
			],
		];
		$fieldsDefinition = new ObjectDefinitionFields($fields, $sections);

		return new ObjectDefinition($tableDefinition, $fieldsDefinition);
	}
}