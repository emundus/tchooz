<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class ApiMapper
{
	private $configuration;
	private array $mapping = [];
	private $db;

	private string $fnum = '';

	public function __construct($configuration, $fnum)
	{
		Log::addLogger(['text_file' => 'com_emundus.mapper.php'], Log::ALL, ['com_emundus.mapper']);

		if (empty($fnum)) {
			Log::add('Error: Empty fnum', Log::ERROR, 'com_emundus.mapper');
			throw new \Exception('Invalid fnum ' . $fnum);
		} else {
			$this->fnum = $fnum;
		}

		if (empty($configuration->event) || empty($configuration->fields)) {
			Log::add('Error: Invalid configuration', Log::ERROR, 'com_emundus.mapper');
			throw new \Exception('Invalid configuration');
		}

		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$query = $this->db->createQuery();

		if (!empty($configuration->programs)) {
			$query->select('esp.code')
				->from($this->db->quoteName('#__emundus_setup_programs', 'esp'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('esp.code'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quoteName('esc.id'))
				->where($this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$program = $this->db->loadResult();

				if (!in_array($program, $configuration->programs)) {
					Log::add('INFO: Applicant file ' . $fnum  . ' is not in programs targeted by this configuration ' . $configuration->event, Log::INFO, 'com_emundus.mapper');
					throw new \Exception('Applicant file is not in programs targeted by this configuration');
				}
			} catch (\Exception $e) {
				Log::add('Error: ' . $e->getMessage(), Log::ERROR, 'com_emundus.mapper');
			}
		}

		$this->configuration = $configuration;
	}


	public function setMappingFromFnum(): array
	{
		$this->mapping = [];

		$query = $this->db->createQuery();
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
		$h_files = new EmundusHelperFiles();

		if (!empty($this->configuration->fields)) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');

			foreach($this->configuration->fields as $field) {
				if (!isset($field->element_type)) {
					$field->element_type = '';
				}

				switch ($field->element_type) {
					case 'alias':
						$fabrik_value = EmundusHelperFabrik::getValueByAlias($field->elementId, $this->fnum);

						if (!empty($fabrik_value) && !empty($fabrik_value['raw'])) {
							$fabrik_element_type = '';
							$fabrik_elements = EmundusHelperFabrik::getElementsByAlias($field->elementId);
							if (!empty($fabrik_elements)) {
								$fabrik_element_type = $fabrik_elements[0]->plugin;
							}


							$this->mapping[$field->attribute] = $this->sanitizeValue($field, $fabrik_value['raw'], $fabrik_element_type);
						} else {
							$this->mapping[$field->attribute] = '';
						}
						break;
					default:
						list($table, $column) = explode('___', $field->elementId);

						if (!empty($table)) {
							$linked = $h_files->isTableLinkedToCampaignCandidature($table);
							$query->clear();

							if ($linked) {
								$query->select($this->db->quoteName($column))
									->from($this->db->quoteName($table))
									->where('fnum LIKE ' . $this->db->quote($this->fnum));
							} else {
								$found = true;
								switch ($table) {
									case 'jos_emundus_setup_programmes':
										$query->select($this->db->quoteName('esp.' . $column))
											->from($this->db->quoteName($table, 'esp'))
											->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.training = esp.code')
											->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.campaign_id = esc.id')
											->where($this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quote($this->fnum));
										break;
									case 'jos_emundus_setup_campaigns':
										$query->select($this->db->quoteName('esc.' . $column))
											->from($this->db->quoteName($table, 'esc'))
											->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.campaign_id = esc.id')
											->where($this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quote($this->fnum));
										break;
									case 'jos_emundus_users':
										$query->select($this->db->quoteName('table.' . $column))
											->from($this->db->quoteName($table, 'table'))
											->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.applicant_id = table.user_id')
											->where($this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quote($this->fnum));
										break;
									case 'jos_users':
										$query->select($this->db->quoteName('table.' . $column))
											->from($this->db->quoteName($table, 'table'))
											->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.applicant_id = table.id')
											->where($this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quote($this->fnum));
										break;
									default:
										$found = false;
										break;
								}

								if (!$found) {
									break 2;
								}
							}

							try {
								$this->db->setQuery($query);
								$result = $this->db->loadResult();

								if (!empty($result)) {
									$this->mapping[$field->attribute] = $this->sanitizeValue($field, $result);
								}
							} catch (\Exception $e) {
								Log::add('Failed to get value : ' . $e->getMessage(), Log::ERROR, 'com_emundus.mapper');
							}
						}
						break;
				}

				if (!empty($field->transformation)) {
					$this->mapping[$field->attribute] = $this->transformValue($this->mapping[$field->attribute], $field->transformation->origin_column, $field->transformation->table, $field->transformation->targeted_column);
				}

				if (!isset($this->mapping[$field->attribute])) {
					$this->mapping[$field->attribute] = '';
				}
			}
		}

		return $this->mapping;
	}


	/**
	 * @param   object  $field
	 * @param   string  $value
	 *
	 * @return string
	 */
	private function sanitizeValue(object $field, string $value, string $fabrik_element_type = ''): string
	{
		$transformed_value = $value;

		if (!empty($field->type)) {
			switch($field->type) {
				case 'list':
					$transformed_value = $field->options->{$value} ?? '';
					break;
				default:
					break;
			}
		}

		if (!empty($fabrik_element_type)) {
			switch($fabrik_element_type) {
				case 'emundus_phonenumber':
					$transformed_value = preg_replace('/^[a-zA-Z]{2}/', '', $transformed_value);
					break;
			}
		}

		return trim(strip_tags($transformed_value));
	}

	/**
	 * @param   string  $value
	 * @param   string  $original_column
	 * @param   string  $table
	 * @param   string  $targeted_column
	 *
	 * @return string
	 */
	private function transformValue(string $value, string $original_column, string $table, string $targeted_column): string
	{
		$transformed_value = $value;

		if (!empty($original_column) && !empty($table) && !empty($targeted_column)) {
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName($targeted_column))
				->from($this->db->quoteName($table))
				->where($this->db->quoteName($original_column) . ' = ' . $this->db->quote($value));

			try {
				$this->db->setQuery($query);
				$transformed_value = $this->db->loadResult();
			} catch (\Exception $e) {
				Log::add('Failed to transform value : ' . $e->getMessage(), Log::ERROR, 'com_emundus.mapper');
			}

			if (empty($transformed_value)) {
				$transformed_value = '';
			}
		}

		return $transformed_value;
	}

	public function setMappingFromJSON($json): array
	{
		return $this->mapping;
	}

	public function getMapping(): array
	{
		return $this->mapping;
	}
}