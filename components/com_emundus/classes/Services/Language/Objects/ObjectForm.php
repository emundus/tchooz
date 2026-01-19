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

class ObjectForm implements ObjectInterface
{
	public function getType(): string
	{
		return 'emundus_setup_profiles';
	}

	public function getName(): string
	{
		return Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES');
	}

	public function getDescription(): string
	{
		return Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_DESC');
	}

	public function getDatas(array $filters = []): array
	{
		try
		{
			// Keep only allowed filters (published, status)
			$allowedFilters = ['published', 'status'];
			$appliedFilters = array_intersect($filters, $allowedFilters);

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id, label')
				->from($db->quoteName('#__emundus_setup_profiles'));
			foreach ($appliedFilters as $filter)
			{
				$query->where($db->quoteName($filter) . ' = 1');
			}

			$db->setQuery($query);
			$datas = $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException("Error fetching data for ObjectCampaigns: " . $e->getMessage());
		}

		return $datas;
	}

	public function getChildrens(string $table, int $reference_id, string $label, string $parent_table = ''): array
	{
		$childrens = array();

		try
		{
			// Only fabrik tables are supported for now
			$allowedTables = $this->getTablesToExport();
			if (!in_array($table, $allowedTables))
			{
				return $childrens;
			}

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			if ($table == 'fabrik_forms')
			{
				if ($parent_table !== 'fabrik_lists')
				{ // todo: this is not generic
					$forms = array();

					if (!class_exists('EmundusHelperMenu'))
					{
						require_once(JPATH_SITE . DS . '/components/com_emundus/helpers/menu.php');
					}
					$h_menu = new \EmundusHelperMenu;

					$tableuser = $h_menu->buildMenuQuery($reference_id);
					foreach ($tableuser as $menu)
					{
						$forms[] = $menu->form_id;
					}
				}
				else
				{
					$forms = array($reference_id);
				}
			}

			$query->select('id,' . $db->quoteName($label) . ' as label')
				->from($db->quoteName('#__' . $table));

			if (isset($forms))
			{
				$query->where($db->quoteName('id') . ' IN (' . implode(',', $forms) . ')');
				$query->order('field(id,' . implode(',', $forms) . ') ASC');
			}
			$db->setQuery($query);
			$childrens = $db->loadObjectList();

			foreach ($childrens as $children)
			{
				$children->label = Text::_($children->label);
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException("Error fetching children data for ObjectForm: " . $e->getMessage());
		}

		return $childrens;
	}

	public function getTablesToExport(): array
	{
		return [
			'fabrik_forms',
			'fabrik_groups',
			'fabrik_elements',
		];
	}

	public function getReferencesIds(int $profile): array
	{
		try
		{
			$reference_ids = [];
			if (!empty($profile))
			{
				$forms_ids  = [];
				$groups_ids = [];
				$elts_id    = [];
				$forms      = $this->getChildrens('fabrik_forms', $profile, 'label');
				foreach ($forms as $form)
				{
					$forms_ids[] = $form->id;
				}
				foreach ($forms_ids as $form_id)
				{
					$groups = $this->getJoinReferenceId('fabrik_groups', 'group_id', 'fabrik_formgroup', 'form_id', $form_id);
					foreach ($groups as $group)
					{
						$groups_ids[] = $group;

						$elements = $this->getJoinReferenceId('fabrik_elements', 'id', 'fabrik_elements', 'group_id', $group);

						foreach ($elements as $element)
						{
							$elts_id[] = $element;
						}

					}
				}

				$reference_ids = array_merge($forms_ids, $groups_ids, $elts_id);
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException("Error fetching export data for ObjectForm: " . $e->getMessage());
		}

		return $reference_ids;
	}

	public function getJoinReferenceId(string $reference_table, string $reference_column, string $join_table, string $join_column, int|array $reference_id): array
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$allowedTables = $this->getTablesToExport();
			if (!in_array($reference_table, $allowedTables))
			{
				return [];
			}

			$query->select('rt.id')
				->from($db->quoteName('#__' . $reference_table, 'rt'))
				->leftJoin($db->quoteName('#__' . $join_table, 'jt') . ' ON ' . $db->quoteName('rt.id') . ' = ' . $db->quoteName('jt.' . $reference_column));
			if (is_array($reference_id))
			{
				$query->where($db->quoteName('jt.' . $join_column) . ' IN (' . implode(',', $db->quote($reference_id)) . ')');
			}
			else
			{
				$query->where($db->quoteName('jt.' . $join_column) . ' = ' . $db->quote($reference_id));
			}

			if ($reference_table == 'fabrik_groups')
			{
				$query->where('JSON_EXTRACT(rt.params,"$.repeat_group_show_first")' . ' = ' . $db->quote(1))
					->where($db->quoteName('rt.published') . ' = 1');
			}

			if ($reference_table == 'fabrik_elements')
			{
				$query->where($db->quoteName('rt.hidden') . ' <> 1')
					->where($db->quoteName('rt.published') . ' = 1');
			}

			$db->setQuery($query);

			$referencesIds = $db->loadColumn();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException("Error fetching join reference IDs for ObjectForm: " . $e->getMessage());
		}

		return $referencesIds;
	}

	public function getDefinition(): ObjectDefinition
	{
		$tableDefinition = new ObjectDefinitionTable(
			'emundus_setup_profiles',
			'id',
			'label',
			['published', 'status'],
			false,
			'override',
			true,
			true
		);

		$fields = [
			[
				'Type'    => 'children',
				'Name'    => 'label',
				'Label'   => 'fabrik_forms',
				'Table'   => '',
				'Options' => '',
			],
			[
				'Type'    => 'field',
				'Name'    => 'label',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_PAGE_TITLE'),
				'Table'   => 'fabrik_forms',
				'Options' => '',
			],
			[
				'Type'    => 'textarea',
				'Name'    => 'intro',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_PAGE_INTRODUCTION'),
				'Table'   => 'fabrik_forms',
				'Options' => '',
			],
			[
				'Type'    => 'field',
				'Name'    => 'label',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_TITLE'),
				'Table'   => '',
				'Options' => '',
			],
			[
				'Type'    => 'textarea',
				'Name'    => 'intro',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_INTRODUCTION'),
				'Table'   => '',
				'Options' => '',
			],
			[
				'Type'    => 'field',
				'Name'    => 'label',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_TITLE'),
				'Table'   => '',
				'Options' => '',
			],
			[
				'Type'    => 'field',
				'Name'    => 'sub_labels',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_OPTION'),
				'Table'   => '',
				'Options' => '',
			],
			[
				'Type'    => 'wysiwig',
				'Name'    => 'default',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_DEFAULT'),
				'Table'   => '',
				'Options' => '',
			],
			[
				'Type'    => 'field',
				'Name'    => 'rollover',
				'Label'   => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_ROLLOVER'),
				'Table'   => '',
				'Options' => '',
			]
		];

		$indexedFieldsForm    = [
			'label' => $fields[1],
			'intro' => $fields[2],
		];
		$indexedFieldsGroup   = [
			'label' => $fields[3],
			'intro' => $fields[4],
		];
		$indexedFieldsElement = [
			'label'      => $fields[5],
			'sub_labels' => $fields[6],
			'default'    => $fields[7],
			'rollover'   => $fields[8],
		];
		$sections             = [
			[
				'Label'           => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_FABRIK_FORM'),
				'Name'            => 'fabrik_forms',
				'Table'           => 'fabrik_forms',
				'TableJoin'       => '',
				'TableJoinColumn' => '',
				'ReferenceColumn' => '',
				'indexedFields'   => $indexedFieldsForm
			],
			[
				'Label'           => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_FABRIK_GROUPS'),
				'Name'            => 'fabrik_groups',
				'Table'           => 'fabrik_groups',
				'TableJoin'       => 'fabrik_formgroup',
				'TableJoinColumn' => 'form_id',
				'ReferenceColumn' => 'group_id',
				'indexedFields'   => $indexedFieldsGroup
			],
			[
				'Label'           => Text::_('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_FABRIK_ELEMENTS'),
				'Name'            => 'fabrik_elements',
				'Table'           => 'fabrik_elements',
				'TableJoin'       => 'fabrik_elements',
				'TableJoinColumn' => 'group_id',
				'ReferenceColumn' => 'id',
				'indexedFields'   => $indexedFieldsElement
			],
		];
		$fieldsDefinition     = new ObjectDefinitionFields($fields, $sections);

		return new ObjectDefinition($tableDefinition, $fieldsDefinition);
	}
}