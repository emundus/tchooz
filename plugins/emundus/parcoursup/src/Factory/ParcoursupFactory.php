<?php
/**
 * @package     Joomla\Plugin\Emundus\Parcoursup\Factory
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup\Factory;

use Joomla\Database\DatabaseInterface;
use Joomla\Plugin\Emundus\Parcoursup\Entity\ParcoursupEntity;

class ParcoursupFactory
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly ?UserFactory      $userFactory = null,
	)
	{
	}

	public function prepareDatas($application)
	{
		$query = $this->database->getQuery(true);

		$importDatas = [
			'campaign_id' => $application['campaign_id'],
			'json'        => json_encode($application)
		];

		$query->select('id')
			->from($this->database->quoteName('#__emundus_campaign_candidature_parcoursup'))
			->where($this->database->quoteName('id_parcoursup') . ' = ' . $this->database->quote($application['id_parcoursup']))
			->where($this->database->quoteName('campaign_id') . ' = ' . $this->database->quote($application['campaign_id']));
		$this->database->setQuery($query);
		$importId = $this->database->loadResult();

		if (empty($importId))
		{
			$importDatas['id_parcoursup'] = $application['id_parcoursup'];
			$importDatas['created_at']    = date('Y-m-d H:i:s');

			$importDatas = (object) $importDatas;
			$stored      = $this->database->insertObject('#__emundus_campaign_candidature_parcoursup', $importDatas);
		}
		else
		{
			$importDatas['id']         = $importId;
			$importDatas['updated_at'] = date('Y-m-d H:i:s');

			$importDatas = (object) $importDatas;
			$stored      = $this->database->updateObject('#__emundus_campaign_candidature_parcoursup', $importDatas, 'id');
		}

		return $stored;
	}

	public function buildDatas($datas, $skipActivation = true): ParcoursupEntity
	{
		$application = new ParcoursupEntity($datas['campaign_id'], $datas['id_parcoursup']);

		foreach ($datas as $elementId => $value)
		{
			$application->addData($elementId, $value);
		}

		// Create User object
		$user = $this->userFactory->buildUser(
			$application->getApplicationFileKey('name'),
			$application->getApplicationFileKey('firstname'),
			$application->getApplicationFileKey('lastname'),
			$application->getApplicationFileKey('username') ?? $application->getApplicationFileKey('email'),
			$application->getApplicationFileKey('email'),
			[2],
			$skipActivation,
			1000
		);
		$application->setUser($user);

		return $application;
	}

	public function getDatasToDelete($delayBeforeDeletion): array
	{
		$query = $this->database->getQuery(true);

		$query->clear()
			->select('id_parcoursup')
			->from($this->database->quoteName('#__emundus_campaign_candidature_parcoursup'))
			->where('json IS NULL')
			->where('updated_at < ' . $this->database->quote(date('Y-m-d H:i:s', strtotime('-' . $delayBeforeDeletion . ' days'))));
		$this->database->setQuery($query);
		return $this->database->loadColumn();
	}
}