<?php

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Tchooz\Repositories\Payment\TransactionRepository;

class TransactionModel extends AdminModel
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

		$this->typeAlias = Factory::getApplication()->getInput()->getCmd('context', 'com_emundus.transactions') . '.transaction';
	}


	public function getItem($pk = null, )
	{
		$item = null;

		if (empty($pk)) {
			$app = Factory::getApplication();
			$pk = $app->input->getInt('id', 0);
		}
		$repository = new TransactionRepository();
		$transaction = $repository->getById($pk);

		if (!empty($transaction->getId())) {
			$item = new \stdClass();
			$item->id = $transaction->getId();

			// add properties from the transaction entity apiRender method
			foreach ($repository->apiRender($transaction) as $key => $value) {
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