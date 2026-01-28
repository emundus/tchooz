<?php

/**
 * @package         Joomla.API
 * @subpackage      com_files
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Api\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use libphonenumber\PhoneNumberUtil;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\ExternalReferenceEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\ExternalReferenceRepository;
use Tobscure\JsonApi\Exception\InvalidParameterException;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The article controller
 *
 * @since  4.0.0
 */
class FilesController extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = 'files';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $default_view = 'files';

	/**
	 * Article list view amended to add filtering of data
	 *
	 * @return  static  A BaseController object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function displayList()
	{
		$filters = $this->input->get('filter', [], 'array');

		if (\array_key_exists('published', $filters))
		{
			$this->modelState->set('filter.published', InputFilter::getInstance()->clean($filters['published'], 'INT'));
		}
		else
		{
			$this->modelState->set('filter.published', 1);
		}

		if (\array_key_exists('status', $filters))
		{
			$this->modelState->set('filter.status', InputFilter::getInstance()->clean($filters['status'], 'INT'));
		}

		return parent::displayList();
	}

	public function displayItem($id = null)
	{
		$item = $this->getModel('file')->getItem($id);

		return parent::displayItem($item);
	}

	public function submit($fnum = null): static
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$applicationFileRepository   = new ApplicationFileRepository();
		$externalReferenceRepository = new ExternalReferenceRepository();
		$campaignRepository          = new CampaignRepository();
		$userFactory                 = Factory::getContainer()->get(UserFactoryInterface::class);
		if (!class_exists('EmundusHelperFabrik')) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
		}
		if (!class_exists('EmundusModelUsers')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
		}

		if ($fnum === null)
		{
			$fnum = $this->input->get('fnum', '', 'string');
		}

		$data = $this->input->get('data', json_decode($this->input->json->getRaw(), true), 'array');

		// if empty fnum create a new application file
		if (empty($fnum))
		{
			if (empty($data['campaign_id']) && empty($data['campaign']))
			{
				throw new InvalidParameterException('The field campaign_id is required.', 400);
			}

			// Check if campaign exists
			$campaign = null;
			if (!empty($data['campaign_id']))
			{
				$campaign = $campaignRepository->getById($data['campaign_id']);
			}
			elseif (!empty($data['campaign']))
			{
				$campaign = $campaignRepository->getByLabel($data['campaign']);
			}

			if (empty($campaign) || empty($campaign->getId()))
			{
				throw new InvalidParameterException('The provided campaign does not exist.', 400);
			}

			// Check user
			if (!empty($data['user_id']))
			{
				// Check that the user exists
				$user = $userFactory->loadUserById($data['user_id']);
				if (empty($user->id))
				{
					throw new InvalidParameterException('The provided user does not exist.', 400);
				}
			}
			else
			{
				if (empty($data['lastname']) || empty($data['firstname']) || empty($data['email']))
				{
					throw new InvalidParameterException('Fields lastname, firstname and email are required to create user or pass a user_id to attach the new application file to it.', 400);
				}

				// Check if a user with the provided email already exists
				$user = $userFactory->loadUserByUsername($data['email']);

				if (empty($user->id))
				{
					// Create it otherwise
					$user               = clone($userFactory->loadUserById(0));
					$user->name         = $data['firstname'] . ' ' . $data['lastname'];
					$user->username     = $data['email'];
					$user->email        = $data['email'];
					$user->block        = 0;
					$user->registerDate = Factory::getDate()->toSql();
					$user->activation   = 1;
					$user->password     = UserHelper::genRandomPassword(30);
					$user->password     = UserHelper::hashPassword($user->password);
					$user->groups       = [2];
					$user->usertype     = 'Registered';

					if (!$user->save())
					{
						throw new InvalidParameterException('Error creating user: ' . implode(' ; ', $user->getErrors()), 400);
					}

					if($data['send_email'])
					{
						if(!class_exists('EmundusModelUsers')) {
							require_once(JPATH_BASE . '/components/com_emundus/models/users.php');
						}
						$m_users = new \EmundusModelUsers();
						$m_users->passwordReset(['email' => $user->email], '', '', true, 'new_account');
					}
				}
			}
			//

			if (in_array('external_reference', array_keys($data)) && in_array('external_key', array_keys($data)))
			{
				if (!empty($data['external_reference']))
				{
					$externalReference = $externalReferenceRepository->getReferenceByExternal('jos_emundus_campaign_candidature.id', $data['external_reference']);
					if (!empty($externalReference))
					{
						$applicationFileEntity = $applicationFileRepository->getById($externalReference->getInternId());
					}
				}
			}

			if (empty($applicationFileEntity))
			{
				// Create the application file
				$applicationFileEntity = new ApplicationFileEntity($user);
				$applicationFileEntity->setCampaignId($campaign->getId());
				$applicationFileEntity->setFnum($applicationFileEntity->generateFnum(0, $user->id));

				if (!$applicationFileRepository->flush($applicationFileEntity))
				{
					throw new InvalidParameterException('Error creating application file', 400);
				}

				if (in_array('external_reference', array_keys($data)) && in_array('external_key', array_keys($data)))
				{
					if (!empty($data['external_reference']))
					{
						$externalReference = new ExternalReferenceEntity('jos_emundus_campaign_candidature.id', $applicationFileEntity->getId(), $data['external_reference']);

						try
						{
							$externalReferenceRepository->flush($externalReference);
						}
						catch (\Exception $e)
						{
							throw new \RuntimeException('Error creating external reference: ' . $e->getMessage(), 400);
						}
					}
				}
				//
			}
		}
		else
		{
			$data['fnum'] = $fnum;
			// Check that the fnum exists
			$applicationFileEntity = $applicationFileRepository->getByFnum($fnum);
			if (empty($applicationFileEntity))
			{
				throw new InvalidParameterException('The provided application file does not exist.', 400);
			}
		}
		//

		// Fill data
		$db_tables = $campaignRepository->getDbTablesByCampaignId($applicationFileEntity->getCampaignId());

		$datas_to_process = [];
		foreach ($data as $key => $value)
		{
			if (str_starts_with($key, 'data[') && str_ends_with($key, ']'))
			{
				$elements = [];
				// Get alias from data[key]
				$data_elt = substr($key, 5, -1);
				if ((int) $data_elt > 0)
				{
					$element = \EmundusHelperFabrik::getElementById($data_elt);
					if (!empty($element))
					{
						$elements = [$element];
					}
				}
				else
				{
					$elements = \EmundusHelperFabrik::getElementsByAlias($data_elt);
				}

				// Remove elements that are not in the campaign db tables
				$elements = array_filter($elements, function ($element) use ($db_tables) {
					return in_array($element->db_table_name, $db_tables);
				});

				foreach ($elements as $element)
				{
					if ($element->plugin === 'databasejoin')
					{
						$params = json_decode($element->params);

						// Search value in db table via join_key_column first
						$query->clear()
							->select($db->quoteName($params->join_key_column))
							->from($db->quoteName($params->join_db_name))
							->where($db->quoteName($params->join_key_column) . ' = ' . $db->quote($value));
						$db->setQuery($query);
						$joined_value = $db->loadResult();

						if (empty($joined_value))
						{
							// Seach via join_val_column
							$query->clear()
								->select($db->quoteName($params->join_key_column))
								->from($db->quoteName($params->join_db_name))
								->where($db->quoteName($params->join_val_column) . ' = ' . $db->quote($value));
							$db->setQuery($query);
							$joined_value = $db->loadResult();
						}

						if (!empty($joined_value))
						{
							$value = $joined_value;
						}
					}
					elseif ($element->plugin === 'emundus_phonenumber')
					{
						$phoneUtil = PhoneNumberUtil::getInstance();

						// If we have a prefix search country code
						if (str_starts_with($value, '+'))
						{
							try
							{
								$phone_number = $phoneUtil->parse($value, null);
								$regionCode   = $phoneUtil->getRegionCodeForNumber($phone_number);
							}
							catch (\Exception $e)
							{
								$regionCode = null;
							}

							if (!empty($regionCode))
							{
								$value = $regionCode . $value;
							}
						}
						else
						{
							// Use default country code
							$defaultRegionCode = 'FR';
							$params            = json_decode($element->params);
							if (!empty($params->default_country))
							{
								$defaultRegionCode = $params->default_country;
							}

							try
							{
								$phone_number = $phoneUtil->parse($value, $params->default_country);
								$value        = $phoneUtil->format($phone_number, \libphonenumber\PhoneNumberFormat::E164);
								$value        = $defaultRegionCode . $value;
							}
							catch (\Exception $e)
							{
								// Do nothing keep original value
							}
						}
					}

					$datas_to_process[$element->db_table_name][$element->name] = $value;
				}
			}
		}

		if (!empty($datas_to_process))
		{
			$applicationFileEntity->setData($datas_to_process);
			if (!$applicationFileRepository->flush($applicationFileEntity))
			{
				throw new InvalidParameterException('Error updating application file data', 400);
			}
		}
		//

		return $this;
	}
}
