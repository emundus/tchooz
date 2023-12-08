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

defined('_JEXEC') or die('Restricted access');

/**
 * A cron task to email records to a give set of users (incomplete application)
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.emundusrecall
 * @since       3.0
 */
class PlgEmundusHopitaux_paris_auto_final_grade extends \Joomla\CMS\Plugin\CMSPlugin
{
	private $db;

	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		jimport('joomla.log.log');
		JLog::addLogger(array('text_file' => 'com_emundus.emundushopitaux_paris_auto_final_grade.php'), JLog::ALL, array('com_emundus'));
	}


	function onAfterStatusChange($fnum, $state)
	{
		$query = $this->db->getQuery(true);

		$status_to_check  = explode(',', $this->params->get('final_grade_status_step', ''));
		$elts_to_complete = explode(';', $this->params->get('final_grade_elts_to_complete', ''));
		$elt_values       = explode(';', $this->params->get('final_grade_elts_values', ''));

		$user = Factory::getApplication()->getIdentity();

		$query->select('applicant_id,campaign_id')
			->from($this->db->quoteName('#__emundus_campaign_candidature'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);
		$file = $this->db->loadObject();

		$status = array_search($state, $status_to_check);

		if ($status === false || empty($elts_to_complete)) {
			return false;
		}

		$elts   = explode(',', $elts_to_complete[$status]);
		$values = explode(',', $elt_values[$status]);

		try {
			foreach ($elts as $key => $elt) {
				$table   = explode('___', $elt)[0];
				$element = explode('___', $elt)[1];

				$query->clear()
					->select('id')
					->from($this->db->quoteName($table))
					->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
				$this->db->setQuery($query);
				$final_grade = $this->db->loadResult();

				$value_expected = $values[$key];

				if (strpos($value_expected, '___')) {
					$query->clear()
						->select(explode('___', $value_expected)[1])
						->from($this->db->quoteName(explode('___', $value_expected)[0]))
						->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
					$this->db->setQuery($query);
					$value_expected = $this->db->loadResult();
				}

				if (!empty($final_grade)) {
					$query->clear()
						->select($element)
						->from($table)
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($final_grade));
					$this->db->setQuery($query);
					$value_exist = $this->db->loadResult();

					if (empty($value_exist) || $value_exist == '' || $value_exist == 0.00) {
						$query->clear()
							->update($this->db->quoteName($table))
							->set($this->db->quoteName($element) . ' = ' . $this->db->quote($value_expected))
							->where($this->db->quoteName('id') . ' = ' . $this->db->quote($final_grade));
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}
				else {
					$query->clear()
						->insert($this->db->quoteName($table))
						->set($this->db->quoteName('time_date') . ' = ' . $this->db->quote(date('Y-m-d h:i:s')))
						->set($this->db->quoteName('user') . ' = ' . $this->db->quote($user->id))
						->set($this->db->quoteName('student_id') . ' = ' . $this->db->quote($file->applicant_id))
						->set($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum))
						->set($this->db->quoteName($element) . ' = ' . $this->db->quote($value_expected));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
		}
		catch (Exception $e) {
			JLog::add('plugins/emundus/hopitaux_paris_auto_final_grade | Error when try to complete final grade : ' . $e->getMessage(), JLog::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}
}
