<?php
namespace Emundus\Plugin\Console\Tchooz\CliCommand\Services;

use Joomla\Database\DatabaseExporter;

abstract class EmundusDatabaseExporter extends DatabaseExporter {
	protected function buildXmlData()
	{
		$buffer = [];

		foreach ($this->from as $table) {
			// Replace the magic prefix if found.
			$table = $this->getGenericTableName($table);

			// Get the details columns information.
			$fields  = $this->db->getTableColumns($table, false);
			$colblob = [];

			foreach ($fields as $field) {
				// Catch blob for conversion xml
				if ($field->Type == 'mediumblob') {
					$colblob[] = $field->Field;
				}
			}

			$this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName(array_keys($fields)))
					->from($this->db->quoteName($table))
			);

			$rows = $this->db->loadObjectList();

			if (!count($rows)) {
				continue;
			}

			$buffer[] = '  <table_data name="' . $table . '">';

			foreach ($rows as $row) {
				$buffer[] = '   <row>';

				// TODO: handle foreign keys
				foreach ($row as $key => $value) {
					if (!in_array($key, $colblob)) {
						if (is_null($value)) {
							$buffer[] = '    <field name="' . $key . '" value_is_null="true"></field>';
						} else {
							$buffer[] = '    <field name="' . $key . '">' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '</field>';
						}
					} else {
						$buffer[] = '    <field name="' . $key . '">' . base64_encode($value) . '</field>';
					}
				}

				$buffer[] = '   </row>';
			}

			$buffer[] = '  </table_data>';
		}

		return $buffer;
	}
}