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
 * A cron task to email records to a give set of users (incomplete application)
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.generate_opi_by_status
 * @since       3.0
 */
class PlgEmundusGenerate_opi_by_status extends CMSPlugin
{
	private $db;
	
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		jimport('joomla.log.log');
		JLog::addLogger(array('text_file' => 'com_emundus.emundusreferent_status.php'), JLog::ALL, array('com_emundus'));
	}

	function onAfterStatusChange($fnum, $state)
	{
		$status_to_generate_opi = explode(',', $this->params->get('opi_status_step', ''));
		$opi_prefix             = $this->params->get('opi_prefix', '');

		$user = Factory::getApplication()->getIdentity()->id;

		if (!in_array($state, $status_to_generate_opi)) {
			return false;
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		$m_files = new EmundusModelFiles;
		$fnum_infos   = $m_files->getFnumInfos($fnum);

		$applicant_id = !empty($fnum_infos['applicant_id']) ? $fnum_infos['applicant_id'] : 0;
		$campaign_id  = !empty($fnum_infos['campaign_id']) ? $fnum_infos['campaign_id'] : 0;

		$query = $this->db->getQuery(true);

		try {
			$query->clear()
				->select('code_opi')
				->from($this->db->quoteName('#__emundus_final_grade'))
				->where($this->db->quoteName('code_opi') . ' IS NOT NULL')
				->andWhere($this->db->quoteName('code_opi') . " != ''")
				->order('code_opi desc limit 1');
			$this->db->setQuery($query);
			$lastOpi = $this->db->loadResult();

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_final_grade'))
				->where($this->db->quoteName('student_id') . ' LIKE ' . $this->db->quote($applicant_id));
			$this->db->setQuery($query);
			$checkApplicant = $this->db->loadObject();

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_final_grade'))
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$checkFnum = $this->db->loadObject();

			if (is_null($lastOpi)) {
				$opi_suffix    = '1';
				$opi_full_code = $opi_prefix . str_pad((int) $opi_suffix, 7, '0', STR_PAD_LEFT);
			}
			else {
				$tmp_opi = explode($opi_prefix, $lastOpi);
				$lastOpi       = (int) end($tmp_opi);
				$opi_full_code = $opi_prefix . str_pad($lastOpi += 1, 7, '0', STR_PAD_LEFT);
			}

			if (is_null($checkApplicant)) {
				$_rawData = array('time_date'   => date('Y-m-d H:i:s'),
				                  'user'        => $user,
				                  'student_id'  => $applicant_id,
				                  'campaign_id' => $campaign_id,
				                  'fnum'        => $fnum,
				                  'code_opi'    => $opi_full_code
				);

				$query->clear()
					->insert($this->db->quoteName('#__emundus_final_grade'))
					->columns($this->db->quoteName(array_keys($_rawData)))
					->values(implode(',', $this->db->quote(array_values($_rawData))));
			}
			else {
				$query->clear()
					->select('code_opi')
					->from($this->db->quoteName('#__emundus_final_grade'))
					->where($this->db->quoteName('student_id') . ' LIKE ' . $this->db->quote($applicant_id))
					->andWhere($this->db->quoteName('code_opi') . "IS NOT NULL")
					->andWhere($this->db->quoteName('code_opi') . "<> ''");
				$this->db->setQuery($query);
				$applicant_opi = $this->db->loadResult();

				if (empty($applicant_opi)) {
					$applicant_opi = $opi_full_code;
				}

				if (!($checkApplicant->code_opi)) {
					if (!$checkFnum) {
						$_rawData = array('time_date'   => date('Y-m-d H:i:s'),
						                  'user'        => $user,
						                  'student_id'  => $applicant_id,
						                  'campaign_id' => $campaign_id,
						                  'fnum'        => $fnum,
						                  'code_opi'    => $applicant_opi
						);
						$query->clear()
							->insert($this->db->quoteName('#__emundus_final_grade'))
							->columns($this->db->quoteName(array_keys($_rawData)))
							->values(implode(',', $this->db->quote(array_values($_rawData))));
					}
					else {
						$query->clear()
							->update($this->db->quoteName('#__emundus_final_grade'))
							->set($this->db->quoteName('code_opi') . ' = ' . $this->db->quote($applicant_opi))
							->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
					}
				}
				else {
					if (!$checkFnum) {
						$_rawData = array('time_date'   => date('Y-m-d H:i:s'),
						                  'user'        => $user,
						                  'student_id'  => $applicant_id,
						                  'campaign_id' => $campaign_id,
						                  'fnum'        => $fnum,
						                  'code_opi'    => $applicant_opi
						);
						$query->clear()
							->insert($this->db->quoteName('#__emundus_final_grade'))
							->columns($this->db->quoteName(array_keys($_rawData)))
							->values(implode(',', $this->db->quote(array_values($_rawData))));
					}
					else {
						$query->clear()
							->update($this->db->quoteName('#__emundus_final_grade'))
							->set($this->db->quoteName('code_opi') . ' = ' . $this->db->quote($applicant_opi))
							->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
					}
				}
			}

			$this->db->setQuery($query);
			return $this->db->execute();
		}
		catch (Exception $e) {
			JLog::add('Error generating OPI code : ' . $e->getMessage(), JLog::ERROR, 'com_emundus');

			return false;
		}
	}
}
