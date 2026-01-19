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

class ObjectCampaign implements ObjectInterface
{
	public function getType(): string
	{
		return 'emundus_setup_campaigns';
	}

	public function getName(): string
	{
		return Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_CAMPAIGNS');
	}

	public function getDescription(): string
	{
		return Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_CAMPAIGNS_DESC');
	}

	public function getDatas(array $filters = []): array
	{
		try
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id, label')
				->from($db->quoteName('#__emundus_setup_campaigns'));
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
			'emundus_setup_campaigns',
			'id',
			'label',
			[],
			false,
			'falang',
			true,
			false
		);

		$fields = [
			[
				'Type' => 'field',
				'Name' => 'label',
				'Label' => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_CAMPAIGNS_NAME'),
				'Table' => '',
				'Options' => '',
			],
			[
				'Type' => 'wysiwig',
				'Name' => 'short_description',
				'Label' => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_CAMPAIGNS_RESUME'),
				'Table' => '',
				'Options' => '',
			],
			[
				'Type' => 'wysiwig',
				'Name' => 'description',
				'Label' => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_CAMPAIGNS_DESCRIPTION'),
				'Table' => '',
				'Options' => '',
			],
		];

		$indexedFields = [];
		foreach ($fields as $field) {
			$indexedFields[$field['Name']] = $field;
		}
		$sections = [
			[
				'Label' => Text::_('COM_EMUNDUS_GLOBAL_INFORMATIONS'),
				'Name' => 'emundus_setup_campaigns',
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