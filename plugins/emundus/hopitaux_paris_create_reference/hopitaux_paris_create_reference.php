<?php
/**
 * @package       eMundus
 * @version       6.6.5
 * @author        eMundus.fr
 * @copyright (C) 2019 eMundus SOFTWARE. All rights reserved.
 * @license       GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die('Restricted access');

/**
 * A cron task to create a reference when status change to CA
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.emundusrecall
 * @since       3.0
 */
class PlgEmundusHopitaux_paris_create_reference extends CMSPlugin
{
	private $db;

	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		jimport('joomla.log.log');
		JLog::addLogger(array('text_file' => 'com_emundus.emundushopitaux_paris_create_reference.php'), JLog::ALL, array('com_emundus'));
	}


	function onAfterStatusChange($fnum, $state)
	{
		$query = $this->db->getQuery(true);

		$status_to_check = explode(',', $this->params->get('reference_status_step', ''));

		if (!in_array($state, $status_to_check)) {
			return false;
		}

		try {
			$query->select('cc.applicant_id,cc.campaign_id,sc.training,sc.year')
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->db->quoteName('sc.id') . ' = ' . $this->db->quoteName('cc.campaign_id'))
				->where($this->db->quoteName('cc.fnum') . ' = ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$file = $this->db->loadObject();

			$query->clear()
				->select('id')
				->from($this->db->quoteName('data_references_dossiers'))
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$fnum_reference = $this->db->loadResult();

			if (empty($fnum_reference)) {
				$reference = null;
				switch ($file->training) {
					case 'AAP_ESPACE_00':
						if ($state == 5) {
							$reference = 'COVID/ES/' . $file->year;
						}
						break;
					case 'PROGRAMME__00':
						if ($state == 5) {
							$reference = 'COVID/PA/' . $file->year;
						}
						break;
					case 'FONDS_D_AI_01':
						if ($state == 17) {
							$reference = 'FAU/AS/' . $file->year;
						}
						break;
				}

				if (!empty($reference)) {
					$query->clear()
						->select('cast(substring_index(reference,' / ',-1) as signed) as reference_number')
						->from($this->db->quoteName('data_references_dossiers'))
						->where($this->db->quoteName('reference') . ' LIKE ' . $this->db->quote($reference . '%'))
						->order('reference_number');
					$this->db->setQuery($query);
					$references = $this->db->loadColumn();

					if (!empty($references)) {
						$last                 = end($references);
						$new_reference_number = (int) $last + 1;
					}
					else {
						$new_reference_number = 1;
					}

					$new_reference = $reference . '/' . $new_reference_number;

					$query->clear()
						->insert($this->db->quoteName('data_references_dossiers'))
						->set($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum))
						->set($this->db->quoteName('reference') . ' = ' . $this->db->quote($new_reference));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

		}
		catch (Exception $e) {
			JLog::add('plugins/emundus/hopitaux_paris_create_reference | Error when try to create the reference : ' . $e->getMessage(), JLog::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}
}
