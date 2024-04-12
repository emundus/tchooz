<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Access Deny');

class modEmundusBookInterviewHelper
{

	private $db;

	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	/** Checks if the user has booked an event.
	 *
	 * @param $userId
	 * @param $campaign_start_date
	 *
	 * @return bool
	 */
	public function hasUserBooked($userId, $campaign_start_date)
	{
		$query = $this->db->getQuery(true);

		try {
			$query->select('COUNT(id)')
				->from($this->db->qn('#__dpcalendar_events'))
				->where($this->db->qn('booking_information') . ' LIKE ' . $this->db->q($userId) . ' AND ' . $this->db->qn('start_date') . ' > ' . $this->db->q($campaign_start_date));
			$this->db->setQuery($query);

			return $this->db->loadResult() > 0;
		}
		catch (Exception $e) {
			Log::add('Error in mod_emundus_book_interview at: hasUserBooked', Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/** Gets all available events for the user.
	 *
	 * @param $user
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getEvents($user, $fnum)
	{

		$offset   = Factory::getApplication()->get('offset');

		$now = date("Y-m-d H:i:s");

		try {

			$query = "SELECT id, title, start_date, description
            FROM #__dpcalendar_events
            WHERE state = 1
            AND (booking_information IS NULL OR booking_information = '')
            AND start_date >= " . $this->db->Quote($now) . "
            AND catid IN (
                SELECT GROUP_CONCAT(id)
                FROM jos_categories
                WHERE extension LIKE \"com_dpcalendar\"
                AND json_extract(params, '$.program') LIKE '%".$user->fnums[$fnum]->training."%'
                GROUP BY id
            )
            ORDER BY catid ASC";

			$this->db->setQuery($query);
			$events = $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error in mod_emundus_book_interview at: getEvents', Log::ERROR, 'com_emundus');
		}

		foreach ($events as $event) {
			$interview_dt = new DateTime($event->start_date, new DateTimeZone('GMT'));
			$interview_dt->setTimezone(new DateTimeZone($offset));
			$event->start_date = $interview_dt->format("Y-m-d H:i:s");
		}

		return $events;
	}


	/** Gets the upcoming interview booked by the user.
	 *
	 * @param $user
	 *
	 * @return bool|mixed
	 */
	public function getNextInterview($user)
	{
		$query    = $this->db->getQuery(true);

		try {
			$query->select($this->db->qn(['dpe.id', 'start_date', 'cat.title']))
				->from($this->db->qn('#__dpcalendar_events', 'dpe'))
				->leftjoin($this->db->qn('#__categories', 'cat') . ' ON ' . $this->db->qn('cat.id') . ' = ' . $this->db->qn('dpe.catid'))
				->where($this->db->qn('booking_information') . ' LIKE ' . $this->db->q($user->id));
			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('Error in mod_emundus_book_interview at: getNextInterview', Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getLastFileInterviewStatus($userid)
	{
		require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
		$query    = $this->db->getQuery(true);

		try {
			$query->select($this->db->qn(['status', 'fnum']))
				->from($this->db->qn('#__emundus_campaign_candidature', 'cc'))
				->leftjoin($this->db->qn('#__emundus_setup_campaigns', 'sc') . '  ON ' . $this->db->qn('cc.campaign_id') . ' = ' . $this->db->qn('sc.id'))
				->where($this->db->qn('applicant_id') . ' = ' . $this->db->q($userid))
				->andwhere($this->db->qn('status') . ' = 5')
				->andwhere($this->db->qn('sc.id') . ' = (SELECT id FROM jos_emundus_setup_campaigns ORDER BY id DESC LIMIT 1)');
			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('Error in mod_emundus_book_interview at: getLastFileInterviewStatus', Log::ERROR, 'com_emundus');

			return false;
		}
	}
}
