<?php
/**
 * @package       eMundus
 * @version       6.6.5
 * @author        eMundus.fr
 * @copyright (C) 2019 eMundus SOFTWARE. All rights reserved.
 * @license       GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die('Restricted access');

class plgEmundusAdd_tag extends CMSPlugin
{
	private $_db;

	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->_db = Factory::getContainer()->get('DatabaseDriver');

		jimport('joomla.log.log');
		JLog::addLogger(array('text_file' => 'com_emundus.add_tag.php'), JLog::ALL, array('com_emundus.add_tag'));
	}


	/**
	 * When a file changes to a certain status, we need to generate a zip archive and send it to the user.
	 *
	 * @param $fnum
	 *
	 * @param $state
	 *
	 * @return bool
	 */
	function onAfterStatusChange($fnum, $state)
	{
		if (empty($fnum)) {
			return false;
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		$query = $this->_db->getQuery(true);

		$m_files    = new EmundusModelFiles;
		$assoc_tags = $m_files->getTagsAssocStatus($state);

		$query->clear()
			->select($this->_db->quoteName('eta.id_tag'))
			->from($this->_db->quoteName('#__emundus_tag_assoc', 'eta'))
			->join('LEFT', $this->_db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON cc.fnum = eta.fnum')
			->where($this->_db->quoteName('eta.fnum') . ' LIKE ' . $this->_db->quote($fnum));
		$this->_db->setQuery($query);

		if (array_intersect($assoc_tags, $this->_db->loadColumn())) {
			return false;
		}

		$query->clear()
			->select($this->_db->quoteName('year'))
			->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
			->join('LEFT', $this->_db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON esc.id = cc.campaign_id')
			->where($this->_db->quoteName('cc.fnum') . ' LIKE ' . $this->_db->quote($fnum));
		$this->_db->setQuery($query);
		$schoolyear = $this->_db->loadResult();

		$aid = intval(substr($fnum, 21, 7));
		$query->clear()
			->select($this->_db->quoteName('eta.id_tag'))
			->from($this->_db->quoteName('#__emundus_tag_assoc', 'eta'))
			->join('LEFT', $this->_db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON cc.fnum = eta.fnum')
			->join('LEFT', $this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.id = cc.campaign_id')
			->join('LEFT', $this->_db->quoteName('#__emundus_users', 'eu') . ' ON eu.user_id = cc.applicant_id')
			->where($this->_db->quoteName('eu.user_id') . ' = ' . $this->_db->quote($aid) . ' AND ' . $this->_db->quoteName('esc.year') . ' = ' . $this->_db->quote($schoolyear));
		$this->_db->setQuery($query);

		foreach ($assoc_tags as $key => $assoc_tag) {
			if (in_array($assoc_tag, $this->_db->loadColumn())) {
				unset($assoc_tags[$key]);
				break;
			}
		}

		return $m_files->tagFile([$fnum], [$assoc_tags[array_keys($assoc_tags)[0]]]);
	}

}
