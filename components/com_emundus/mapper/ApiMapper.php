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

		foreach($this->configuration->fields as $field) {
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
						continue;
					}
				}

				try {
					$this->db->setQuery($query);
					$result = $this->db->loadResult();

					switch($field->type) {
						case 'list':
							$result = $field->options->{$result} ?? '';
							break;
						default:
							break;
					}

					$this->mapping[$field->attribute] = trim(strip_tags($result));
				} catch (\Exception $e) {
					Log::add('Error: ' . $e->getMessage(), Log::ERROR, 'com_emundus.mapper');
				}
			}
		}

		return $this->mapping;
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