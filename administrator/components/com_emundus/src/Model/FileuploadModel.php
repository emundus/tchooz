<?php

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use JUri;
use Tchooz\Repositories\Payment\TransactionRepository;

class FileuploadModel extends AdminModel
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

		$this->typeAlias = Factory::getApplication()->getInput()->getCmd('context', 'com_emundus.fileupload') . '.fileupload';
	}


	public function getItem($pk = null, )
	{
		$item = null;

		if (empty($pk)) {
			$app = Factory::getApplication();
			$pk = $app->input->getInt('id', 0);
		}

		$db    = $this->getDatabase();
		$query = $db->createQuery();
		$user  = $this->getCurrentUser();
		$params = ComponentHelper::getParams('com_emundus');


		$site_url = rtrim(JUri::root(), '/\\') . '/';
		$attachments_folder = 'images/emundus/files/';

		$query->select('upload.id, upload.fnum, upload.filename, upload.local_filename, upload.user_id, attachment.value as attachment_name, attachment.description, CONCAT(' . $db->quote($site_url . $attachments_folder) . ', candidature.applicant_id, "/", upload.filename) as download_url')
			->from($db->quoteName('#__emundus_uploads', 'upload'))
			->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'candidature') . ' ON ' . $db->quoteName('candidature.fnum') . ' = ' . $db->quoteName('upload.fnum'))
			->leftJoin($db->quoteName('#__emundus_setup_attachments', 'attachment') . ' ON ' . $db->quoteName('attachment.id') . ' = ' . $db->quoteName('upload.attachment_id'))
			->where('upload.id = ' . $this->getDatabase()->quote($pk));

		$this->getDatabase()->setQuery($query);
		$record = $this->getDatabase()->loadObject();

		if (!empty($record->id)) {
			$item = new \stdClass();
			$item->id = $record->id;

			// add properties from the record
			foreach ($record as $key => $value) {
				$item->$key = $value;
			}
		}

		return $item;
	}
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return false;
	}
}