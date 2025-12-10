<?php

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Model\ListModel;
use stdClass;

class StatisticModel extends AdminModel
{
	/**
	 * Constructor
	 *
	 * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   ?MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   3.7.0
	 * @throws  \Exception
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->typeAlias = Factory::getApplication()->getInput()->getCmd('context', 'com_emundus.statistic') . '.statistic';
		Log::addLogger(['text_file' => 'com_emundus.api.log'], Log::ALL, 'com_emundus.api');
	}


	public function getItem($pk = null)
	{
		$item = new stdClass();

		$item->id = 1;

		// Get total number of users
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select('COUNT(id)')
			->from($db->quoteName('#__users'));
		$db->setQuery($query);
		$item->total_users = $db->loadResult();

		// Get total number of application files (campaign_candidature)
		$query->clear()
			->select('COUNT(id)')
			->from($db->quoteName('#__emundus_campaign_candidature'));
		$db->setQuery($query);
		$item->total_files = $db->loadResult();

		// Get total emails sent
		$query->clear()
			->select('COUNT(message_id)')
			->from($db->quoteName('#__messages'))
			->where($db->quoteName('email_to') . ' IS NOT NULL')
			->where($db->quoteName('page') . ' IS NULL')
			->where($db->quoteName('folder_id') . ' != 2');
		$db->setQuery($query);
		$item->total_emails_sent = $db->loadResult();

		return $item;
	}

	public function getForm($data = [], $loadData = true)
	{
		// TODO: Implement getForm() method.
	}
}