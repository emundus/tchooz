<?php
/**
 * Messages model used for the new message dialog.
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class EmundusModelWorkflow extends JModelList
{
	private $app;

	private $db;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app = Factory::getApplication();
		$this->db = $this->app->getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => 'com_emundus.formbuilder.php'], Log::ALL, array('com_emundus.workflow'));
	}

	public function getWorkflows($ids = []) {
		$workflows = [];

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_setup_workflows'))
			->where($this->db->quoteName('published') . ' = 1');

		if (!empty($ids)) {
			$query->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
		}

		try {
			$this->db->setQuery($query);
			$workflows = $this->db->loadObjectList();
		} catch (Exception $e) {
			Log::add('Error while fetching workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $workflows;
	}
}