<?php
/**
 * Messages controller used for the creation and emission of messages from the platform.
 *
 * @package    Joomla
 * @subpackage Emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Hugo Moracchini
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Entities\Country;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\List\AdditionalColumnList;
use Tchooz\Entities\List\AdditionalColumnPublished;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\List\ListDisplayEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Services\UploadException;
use Tchooz\Services\UploadService;
use Tchooz\Traits\TraitDispatcher;
use Tchooz\Traits\TraitResponse;

class EmundusControllerCrc extends BaseController
{
	use TraitResponse;

	use TraitDispatcher;

	private ?User $user;

	private DatabaseInterface $db;

	private ContactRepository $contactRepository;

	private OrganizationRepository $organizationRepository;

	private ActionEntity $contactAction;

	private ActionEntity $orgAction;

	public function __construct($config = array(), MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->user = $this->app->getIdentity();

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}

		if (!class_exists('ContactRepository'))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/ContactRepository.php';
		}
		$this->contactRepository = new ContactRepository();

		if (!class_exists('OrganizationRepository'))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/OrganizationRepository.php';
		}
		$this->organizationRepository = new OrganizationRepository();

		if (!class_exists('ActionRepository'))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Actions/ActionRepository.php';
		}
		$actionRepository    = new ActionRepository();
		$this->contactAction = $actionRepository->getByName('contact');
		$this->orgAction     = $actionRepository->getByName('organization');

		Log::addLogger(['text_file' => 'com_emundus.error.php'], Log::ERROR, array('com_emundus'));
		Log::addLogger(['text_file' => 'com_emundus.crc.php'], Log::ALL, array('com_emundus.crc'));
	}

	public function getcontacts(): void
	{
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::READ->value, $this->user->id))
		{
			$order_by = $this->input->getString('order_by', 't.id');
			$sort     = $this->input->getString('sort', '');
			$search   = $this->input->getString('recherche', '');
			$lim      = $this->input->getInt('lim', 0);
			$page     = $this->input->getInt('page', 0);

			// Filters
			$published    = $this->input->getString('published', 'all');
			$contact      = $this->input->getInt('contact', 0);
			$phone_number = $this->input->getString('phone_number', '');
			$organization = $this->input->getString('organization', '');
			$nationality  = $this->input->getString('nationality', '');

			try
			{
				$contacts_response = ['datas' => [], 'count' => 0];

				$contactsFilter = $contact ? [$contact] : [];
				$organizationsFilter = $organization ? [$organization] : [];
				$nationalitiesFilter = $nationality ? [$nationality] : [];

				$contacts = $this->contactRepository->getAllContacts($sort, $search, $lim, $page, $order_by, $published, $contactsFilter, $phone_number, $organizationsFilter, $nationalitiesFilter);
				if ($contacts['count'] > 0)
				{
					if (!class_exists('CountryRepository'))
					{
						require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/CountryRepository.php';
					}
					$countryRepository = new CountryRepository();

					$contacts_response['count'] = $contacts['count'];

					foreach ($contacts['datas'] as $contact)
					{
						if (!$contact instanceof ContactEntity)
						{
							continue;
						}

						$contact_reponse = $contact->__serialize();
						$contact_reponse = (object) $contact_reponse;

						$contact_reponse->label = ['fr' => $contact->getFullName(), 'en' => $contact->getFullName()];

						// TODO: Move this to entity method
						if (!empty($contact->getGender()))
						{
							$contact_reponse->gender_icon = $contact->getGender()->getIcon();
						}

						if (!empty($contact->getOrganizations()))
						{
							$contact_reponse->organizations = [];
							$addedOrganizationIds           = [];

							foreach ($contact->getOrganizations() as $organization)
							{
								if ($organization instanceof OrganizationEntity)
								{
									$orgData = $organization->__serialize();

									if (!in_array($orgData['id'], $addedOrganizationIds, true))
									{
										$contact_reponse->organizations[] = $orgData;
										$addedOrganizationIds[]           = $orgData['id'];
									}
								}
							}
						}


						if (!empty($contact->getAddresses()))
						{
							$contact_reponse->addresses = [];
							foreach ($contact->getAddresses() as $address)
							{
								if ($address instanceof AddressEntity)
								{
									$address_object = $address->__serialize();
									if (!empty($address->getCountry()))
									{
										$countryEntity = $countryRepository->getById($address->getCountry());
										if ($countryEntity instanceof Country)
										{
											$address_object['country'] = $countryEntity->__serialize();
										}
									}
									$contact_reponse->addresses[] = $address_object;
								}
							}
						}

						if (!empty($contact->getCountries()))
						{
							$contact_reponse->countries = [];
							foreach ($contact->getCountries() as $country)
							{
								if ($country instanceof Country)
								{
									$contact_reponse->countries[] = $country->__serialize();
								}
							}
						}

						// Keep only published organizations
						$orgIds = array_filter(
							$contact->getOrganizations(),
							function ($org) {
								return !empty($org->isPublished()) && (int) $org->isPublished() === 1;
							}
						);
						$orgIds = array_map(fn($org) => (int) $org->getId(), $orgIds);

						$organizations = [];

						if (!empty($orgIds)) {
							$organizations = $this->organizationRepository->getByIds($orgIds);
						}


						$org_column = new AdditionalColumnList(
							Text::_('COM_EMUNDUS_ONBOARD_ORGS_ASSOCIATED_TITLE'),
							Text::_('COM_EMUNDUS_ONBOARD_ORGS_ASSOCIATED'),
							Text::_('COM_EMUNDUS_ONBOARD_ORGS_ASSOCIATED_NOT'),
							$organizations,
							'name',
							'index.php?option=com_emundus&view=crc&layout=organizationform&id={id}',
							'id',
							true,
							'logo',
							ListDisplayEnum::ALL
						);

						$email_link                          = '<a target="_blank" class="tw-cursor-pointer tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-justify-center hover:tw-underline hover:tw-font-semibold" href="mailto:' . $contact->getEmail() . '" style="line-height: unset;font-size: unset;">' . $contact->getEmail() . '</a>';
						$contact_reponse->additional_columns = [
							new AdditionalColumn(
								Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_EMAIL'),
								'',
								ListDisplayEnum::TABLE,
								't.email',
								$contact->getEmail()
							),
							new AdditionalColumn(
								Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_EMAIL'),
								'',
								ListDisplayEnum::CARDS,
								't.email',
								$email_link
							),
							new AdditionalColumn(
								Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_PHONENUMBER'),
								'',
								ListDisplayEnum::TABLE,
								'',
								$contact->getPhone1() ?? ''
							),
							new AdditionalColumnPublished($contact->isPublished(), 'published'),
						];

						$status = $contact->getStatus();
						if ($status && $status === VerifiedStatusEnum::TO_BE_VERIFIED) {
							$contact_reponse->additional_columns[] = new AdditionalColumn(
								Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_STATUS'),
								'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-text-white ' . $status->getColorClass(),
								ListDisplayEnum::CARDS,
								't.status',
								$status->getLabel()
							);
						}
						$contact_reponse->additional_columns[] = $org_column;


						$contacts_response['datas'][] = $contact_reponse;
					}
				}

				$response['code']    = 200;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_RETRIEVED');
				$response['data']    = $contacts_response;
				$response['status']  = true;
			}
			catch (Exception $e)
			{
				$response['code']    = $e->getCode();
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getcontact(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::READ->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$id = $this->input->getInt('id', 0);

		if (empty($id))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_INVALID_ID');;
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			if ($contact = $this->contactRepository->getById($id))
			{
				$contact_response = $contact->__serialize();
				$contact_response = (object) $contact_response;

				if (!empty($contact->getAddresses()))
				{
					$contact_response->addresses = [];
					foreach ($contact->getAddresses() as $address)
					{
						if ($address instanceof AddressEntity)
						{
							$contact_response->addresses[] = $address->__serialize();
						}
					}
				}

				if (!empty($contact->getCountries()))
				{
					$contact_response->countries = [];
					foreach ($contact->getCountries() as $country)
					{
						if ($country instanceof Country)
						{
							$contact_response->countries[] = $country->__serialize();
						}
					}
				}

				if (!empty($contact->getOrganizations()))
				{
					$contact_response->organizations = [];
					$org_ids                         = [];

					foreach ($contact->getOrganizations() as $organization)
					{
						if ($organization instanceof OrganizationEntity)
						{
							$org_id = $organization->getId();

							if (!in_array($org_id, $org_ids, true))
							{
								$contact_response->organizations[] = $organization->__serialize();
								$org_ids[]                         = $org_id;
							}
						}
					}
				}

				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_RETRIEVED');
				$response['data']    = $contact_response;
			}
			else
			{
				throw new \Exception('Failed to retrieve contact.', 500);
			}
		}
		catch (\Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function unpublishcontact()
	{
		$this->checkToken();

		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::UPDATE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$contacts_ids = [];
		$ids          = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$contacts_ids = explode(',', $ids);
		}
		$id = $this->input->getInt('id', 0);
		if ($id > 0)
		{
			$contacts_ids[] = $id;
		}

		if (empty($contacts_ids))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_INVALID_ID');
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			$tasks = [];
			foreach ($contacts_ids as $contact_id)
			{
				$tasks[] = $this->contactRepository->togglePublished($contact_id, false);
			}

			$response['code']    = !in_array(false, $tasks) ? 200 : 500;
			$response['message'] = !in_array(false, $tasks) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_UNPUBLISHED_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_UNPUBLISHED_FAILED');
			$response['status']  = !in_array(false, $tasks);
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function publishcontact()
	{
		$this->checkToken();

		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::UPDATE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$contacts_ids = [];
		$ids          = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$contacts_ids = explode(',', $ids);
		}
		$id = $this->input->getInt('id', 0);
		if ($id > 0)
		{
			$contacts_ids[] = $id;
		}

		if (empty($contacts_ids))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_INVALID_ID');;
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			$tasks = [];
			foreach ($contacts_ids as $contact_id)
			{
				$tasks[] = $this->contactRepository->togglePublished($contact_id, true);
			}

			$response['code']    = !in_array(false, $tasks) ? 200 : 500;
			$response['message'] = !in_array(false, $tasks) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_PUBLISHED_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_PUBLISHED_FAILED');
			$response['status']  = !in_array(false, $tasks);
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function deletecontact()
	{
		$this->checkToken();

		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::DELETE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$contacts_ids = [];
		$ids          = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$contacts_ids = explode(',', $ids);
		}
		$id = $this->input->getInt('id', 0);
		if ($id > 0)
		{
			$contacts_ids[] = $id;
		}

		if (empty($contacts_ids))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_INVALID_ID');
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			$tasks = [];
			foreach ($contacts_ids as $contact_id)
			{
				$tasks[] = $this->contactRepository->delete($contact_id);
			}

			$response['code']    = !in_array(false, $tasks) ? 200 : 500;
			$response['message'] = !in_array(false, $tasks) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_DELETED_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACTS_DELETED_FAILED');
			$response['status']  = !in_array(false, $tasks);
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function savecontact()
	{
		$this->checkToken();

		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		$id = $this->input->getInt('id', 0);

		if (
			!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::CREATE->value, $this->user->id)
			&&
			($id > 0 && !EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::UPDATE->value, $this->user->id))
		)
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		// Required fields
		$lastname  = $this->input->getString('lastname', '');
		$firstname = $this->input->getString('firstname', '');
		$email     = $this->input->getString('email', '');

		if (empty($lastname) || empty($firstname) || empty($email))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_INVALID_FIELDS');
			$this->sendJsonResponse($response);

			return;
		}

		// Optional fields
		$fonction  = $this->input->getString('fonction', '');
		$service   = $this->input->getString('service', '');
		$gender    = $this->input->getString('gender', '');
		$birthdate = $this->input->getString('birthdate', '');
		$profile_picture   = $this->input->files->get('profile_picture');
		$profile_picture_path  = $this->input->getString('profile_picture');

		if (!empty($birthdate))
		{
			$date = DateTime::createFromFormat('d/m/Y', $birthdate);
			if ($date instanceof DateTime)
			{
				$birthdate = $date->format('Y-m-d');
			}
			else
			{
				$birthdate = '';
			}
		}
		$phone_1 = $this->input->getString('phone_1', '');
		if (!empty($phone_1))
		{
			try
			{
				$phoneUtil = PhoneNumberUtil::getInstance();

				if (preg_match('/^\w{2}/', $phone_1))
				{
					$region       = substr($phone_1, 0, 2);
					$phone_number = substr($phone_1, 2);

					$phone_1 = $phoneUtil->parse($phone_1, $region);
				}
				else
				{
					$phone_1 = $phoneUtil->parse($phone_1, null);
				}

				if ($phoneUtil->isValidNumber($phone_1))
				{
					$phone_1 = $phoneUtil->format($phone_1, PhoneNumberFormat::E164);
				}
			}
			catch (Exception $e)
			{
				$phone_1 = '';
			}
		}

		$published = $this->input->getInt('published', 1);
		$published = $published === 1;

		// Countries
		$countries = $this->input->getString('countries', '');
		if (!empty($countries))
		{
			$countries = explode(',', $countries);
		}
		else
		{
			$countries = [];
		}

		// Organizations
		$organizations = $this->input->getString('organizations', '');
		if (!empty($organizations))
		{
			$organizations = explode(',', $organizations);
		}
		else
		{
			$organizations = [];
		}

		// Addresses
		$addresses = $this->input->getString('addresses', '');
		if (!empty($addresses))
		{
			$addresses = json_decode($addresses, true);
		}
		else
		{
			$addresses = [];
		}

		if (!empty($profile_picture) && $profile_picture['error'] === 0 || $profile_picture_path === 'null' || empty($profile_picture_path))
		{
			// Delete old profile picture if exists
			if ($id > 0)
			{
				$this->contactRepository->deleteProfilePicture($id);
			}

			$upload_dir = 'images/emundus/contacts/';
			$uploader   = new UploadService($upload_dir);

			if((!empty($profile_picture) && $profile_picture['error'] === 0)) {
				try
				{
					$length = rand(5, 10);
					$random = bin2hex(random_bytes($length));
					$random = substr($random, 0, $length);
					$profile_picture_path = $uploader->upload($profile_picture, $lastname . $firstname . '_' . $random, 'contact');
				}
				catch (UploadException $e)
				{
					$this->sendJsonResponse([
						'code'    => 400,
						'message' => $e->getMessage(),
					]);
				}
			}
		}

		try
		{
			$countriesEntities = [];
			if (!empty($countries))
			{
				if (!class_exists('CountryRepository'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/CountryRepository.php';
				}
				$countryRepository = new CountryRepository();
				foreach ($countries as $country)
				{
					$countryEntity = $countryRepository->getById($country);
					if ($countryEntity instanceof Country)
					{
						$countriesEntities[] = $countryEntity;
					}
				}
			}

			$addressesEntities = [];
			if (!empty($addresses))
			{
				if (!class_exists('AddressEntity'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/AddressEntity.php';
				}

				foreach ($addresses as $address)
				{
 					$fields = [
						$address['locality'] ?? '',
						$address['region'] ?? '',
						$address['street_address'] ?? '',
						$address['extended_address'] ?? '',
						$address['postal_code'] ?? '',
						$address['country'] ?? '',
					];

					if (empty(array_filter($fields)))
					{
						continue;
					}

					$addressEntity = new AddressEntity(
						id: !empty($address['id']) ? $address['id'] : 0,
						locality: $address['locality'] ?? '',
						region: $address['region'] ?? '',
						street_address: $address['street_address'] ?? '',
						extended_address: $address['extended_address'] ?? '',
						postal_code: $address['postal_code'] ?? '',
						country: !empty($address['country']) ? $address['country'] : null
					);
					$addressesEntities[] = $addressEntity;
				}
			}

			$organizationsEntities = [];
			if (!empty($organizations))
			{
				if (!class_exists('OrganizationRepository'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/OrganizationRepository.php';
				}
				$orgRepository = new OrganizationRepository();
				foreach ($organizations as $organization)
				{
					$orgEntity = $orgRepository->getById($organization);
					if ($orgEntity instanceof OrganizationEntity)
					{
						$organizationsEntities[] = $orgEntity;
					}
				}
			}

			if (!class_exists('ContactEntity'))
			{
				require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/ContactEntity.php';
			}
			$status = EmundusHelperAccess::asPartnerAccessLevel($this->user->id) ? VerifiedStatusEnum::VERIFIED : VerifiedStatusEnum::TO_BE_VERIFIED;

			$contactEntity = new ContactEntity(
				email: $email,
				lastname: $lastname,
				firstname: $firstname,
				phone_1: !empty($phone_1) ? $phone_1 : null,
				id: $id,
				user_id: 0,
				addresses: $addressesEntities,
				birth: !empty($birthdate) ? $birthdate : null,
				gender: !empty($gender) ? $gender : null,
				fonction: !empty($fonction) ? $fonction : null,
				service: !empty($service) ? $service : null,
				countries: $countriesEntities,
				organizations: $organizationsEntities,
				profile_picture: !empty($profile_picture_path) && $profile_picture_path !== "null" ? $profile_picture_path : null,
				published: $published,
				status: $status,
			);

			$this->contactRepository->flush($contactEntity);

			$response['code']    = !empty($contactEntity->getId()) ? 200 : 500;
			$response['message'] = !empty($contactEntity->getId()) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_SAVED_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_SAVED_FAILED');
			$response['status']  = true;
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function getfilteredcontacts()
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::DELETE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		try {
			$contacts         = $this->contactRepository->getFilteredContacts();
			$response['data'] = $contacts;

			$response['code']    = 200;
			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_FILTERED_CONTACTS_FOUND');
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function getfilteredcontactsbyphonenumber()
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::DELETE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		try {
			$contacts         = $this->contactRepository->getFilteredContactsByPhoneNumber();
			$response['data'] = $contacts;

			$response['code']    = 200;
			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_FILTERED_CONTACTS_PHONE_NUMBERS_FOUND');
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function exportcsvcontacts(): void
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::READ->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}


		$ids = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$ids = explode(',', $ids);
		}
		else
		{
			$ids = [];
		}

		if (!empty($ids))
		{
			$response['code']    = 200;
			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_EXPORT_CSV_CONTACTS_SUCCESS');

			$contacts = $this->contactRepository->getAllContacts('DESC', '', 0, 0, 't.id', null, $ids);

			$excel_filename = 'export_contacts_' . date('Ymd_His') . '.csv';
			$excel_filepath = JPATH_SITE . '/tmp/' . $excel_filename;
			$fp             = fopen($excel_filepath, 'w');

			$columns = [
				Text::_('COM_EMUNDUS_USERNAME'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_EMAIL'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_LASTNAME'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FIRSTNAME'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_PHONENUMBER'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_BIRTHDATE'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER'),
				Text::_('COM_EMUNDUS_PUBLISH'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ROLE'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_DEPARTMENT'),
			];
			fputcsv($fp, $columns, ';');

			$rows = [];
			foreach ($contacts['datas'] as $key => $contact)
			{
				if (!$contact instanceof ContactEntity)
				{
					continue;
				}
				$row    = [
					$contact->getId(),
					$contact->getEmail(),
					$contact->getLastname(),
					$contact->getFirstname(),
					// Escape + to avoid Excel interpreting it as a formula
					'="' . $contact->getPhone1() . '"',
					$contact->getBirthdate(),
					!empty($contact->getGender()) ? $contact->getGender()->getLabel() : '',
					$contact->isPublished() ? Text::_('COM_EMUNDUS_ONBOARD_CONTACTS_PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_CONTACTS_UNPUBLISHED'),
					$contact->getFonction(),
					$contact->getService()
				];
				$rows[] = $row;

				fputcsv($fp, $row, ';');
			}

			fclose($fp);

			$nb_cols = count($columns);
			$nb_rows = count($rows);

			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/models/users.php');
			$m_users  = new EmundusModelUsers();
			$xls_file = $m_users->convertCsvToXls($excel_filename, $nb_cols, $nb_rows, 'export_contacts_' . date('Ymd_His'), ';');

			$excel_filepath = '';
			if (!empty($xls_file))
			{
				$excel_filepath = JPATH_SITE . '/tmp/' . $xls_file;
			}

			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="' . basename($excel_filepath) . '"');
			header('Content-Length: ' . filesize($excel_filepath));

			$response['download_file'] = Uri::root() . 'tmp/' . basename($excel_filepath);
		}
		else
		{
			$response['code']    = 400;
			$response['status']  = false;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_EXPORT_CSV_NO_SELECTION');
		}

		$this->sendJsonResponse($response);
	}

	public function getorganizations(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::READ->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$order_by = $this->input->getString('order_by', 't.id');
		$sort     = $this->input->getString('sort', '');
		$search   = $this->input->getString('recherche', '');
		$lim      = $this->input->getInt('lim', 0);
		$page     = $this->input->getInt('page', 0);

		// Filters
		$published = $this->input->getString('published', 'true');
		$organization = $this->input->getInt('organization', 0);
		$identifier_code = $this->input->getString('identifier_code', '');
		
		try
		{
			$organizations_response = ['datas' => [], 'count' => 0];

			$organizationsFilter = $organization ? [$organization] : [];

			$organizations = $this->organizationRepository->getAllOrganizations($sort, $search, $lim, $page, $order_by, $published, $organizationsFilter, $identifier_code);
			if ($organizations['count'] > 0)
			{
				if (!class_exists('CountryRepository'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/CountryRepository.php';
				}
				$countryRepository = new CountryRepository();

				$organizations_response['count'] = $organizations['count'];

				foreach ($organizations['datas'] as $organization)
				{
					if (!$organization instanceof OrganizationEntity)
					{
						continue;
					}

					$organization_response = $organization->__serialize();
					$organization_response = (object) $organization_response;

					$organization_response->label = ['fr' => $organization->getName(), 'en' => $organization->getName()];
					$logo = $organization->getLogo();

					if (empty($logo)) {
						$organization_response->image = null;
					} else {
						if (preg_match('#^https?://#i', $logo)) {
							$organization_response->image = $logo;
						} else {
							$base = rtrim(Uri::root(), '/') . '/';
							$organization_response->image = $base . ltrim($logo, '/');
						}
					}

					if (!empty($organization->getAddress()))
					{
						if ($organization->getAddress() instanceof AddressEntity)
						{
							$address_object = $organization->getAddress()->__serialize();
							if (!empty($organization->getAddress()->getCountry()))
							{
								$countryEntity = $countryRepository->getById($organization->getAddress()->getCountry());
								if ($countryEntity instanceof Country)
								{
									$address_object['country'] = $countryEntity->__serialize();
								}
							}
							$organization_response->address = $address_object;
						}
					}

					$addedContactIds = [];
					if (!empty($organization->getReferentContacts()))
					{
						$organization_response->referent_contacts = [];

						foreach ($organization->getReferentContacts() as $contact)
						{
							if ($contact instanceof ContactEntity)
							{
								$contactData = $contact->__serialize();

								if (!in_array($contactData['id'], $addedContactIds, true))
								{
									$organization_response->referent_contacts[] = $contactData;
									$addedContactIds[] = $contactData['id'];
								}
							}
						}
					}

					if (!empty($organization->getOtherContacts()))
					{
						$organization_response->other_contacts = [];

						foreach ($organization->getOtherContacts() as $contact)
						{
							if ($contact instanceof ContactEntity)
							{
								$contactData = $contact->__serialize();

								if (!in_array($contactData['id'], $addedContactIds, true))
								{
									$organization_response->other_contacts[] = $contactData;
									$addedContactIds[] = $contactData['id'];
								}
							}
						}
					}

					$organization_response->additional_columns = [
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_ONBOARD_ADD_ORG_DESCRIPTION'),
							'',
							ListDisplayEnum::TABLE,
							'',
							$organization->getDescription(),
						),
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_ONBOARD_ADD_ORG_IDENTIFIER_CODE'),
							'',
							ListDisplayEnum::TABLE,
							't.identifier_code',
							$organization->getIdentifierCode()
						),
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_ONBOARD_EDIT_ORG_ADDRESS'),
							'',
							ListDisplayEnum::TABLE,
							'',
							!empty($organization->getAddress()) ? ($organization->getAddress()->getStreetAddress() . ', ' . $organization->getAddress()->getPostalCode() . ' ' . $organization->getAddress()->getLocality()) : '',
						),
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_ONBOARD_ADD_ORG_URL_WEBSITE'),
							'',
							ListDisplayEnum::TABLE,
							'',
							'<a target="_blank" class="tw-cursor-pointer tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-justify-center hover:tw-underline hover:tw-font-semibold" href="' . $organization->getUrlWebsite() . '" style="line-height: unset;font-size: unset;">' . $organization->getUrlWebsite() . '</a>'

				),
						new AdditionalColumnPublished($organization->isPublished(), 'published'),
					];

					$status = $organization->getStatus();
					if ($status && $status === VerifiedStatusEnum::TO_BE_VERIFIED) {
						$organization_response->additional_columns[] = new AdditionalColumn(
							Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_STATUS'),
							'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-text-white ' . $status->getColorClass(),
							ListDisplayEnum::CARDS,
							't.status',
							$status->getLabel()
						);
					}

					$organizations_response['datas'][] = $organization_response;
				}
			}

			$response['code']    = 200;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_GET_ORGANIZATIONS_SUCCESS');
			$response['data']    = $organizations_response;
			$response['status']  = true;
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function getorganization(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		if (!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::READ->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$id = $this->input->getInt('id', 0);

		if (empty($id))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_GET_ORGANIZATION_NO_ID');
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			if ($organization = $this->organizationRepository->getById($id))
			{
				$organization_response = $organization->__serialize();
				$organization_response = (object) $organization_response;

				if (!empty($organization->getAddress()))
				{
					if ($organization->getAddress() instanceof AddressEntity)
					{
						$organization_response->address = $organization->getAddress()->__serialize();
					}
				}

				if (!empty($organization->getReferentContacts()))
				{
					$organization_response->referent_contacts = [];
					foreach ($organization->getReferentContacts() as $contact)
					{
						if ($contact instanceof ContactEntity)
						{
							$organization_response->referent_contacts[] = $contact->__serialize();
						}
					}
				}

				if (!empty($organization->getOtherContacts()))
				{
					$organization_response->other_contacts = [];
					foreach ($organization->getOtherContacts() as $contact)
					{
						if ($contact instanceof ContactEntity)
						{
							$organization_response->other_contacts[] = $contact->__serialize();
						}
					}
				}

				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_GET_ORGANIZATION_SUCCESS');
				$response['data']    = $organization_response;
			}
			else
			{
				throw new \Exception('Failed to retrieve organization.', 500);
			}
		}
		catch (\Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function unpublishorganization()
	{
		$this->checkToken();

		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::UPDATE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$contacts_ids = [];
		$ids          = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$contacts_ids = explode(',', $ids);
		}
		$id = $this->input->getInt('id', 0);
		if ($id > 0)
		{
			$orgs_ids[] = $id;
		}

		if (empty($orgs_ids))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_UNPUBLISH_ORGANIZATIONS_NO_ID');
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			$tasks = [];
			foreach ($orgs_ids as $org_id)
			{
				$tasks[] = $this->organizationRepository->togglePublished($org_id, false);
			}

			$response['code']    = !in_array(false, $tasks) ? 200 : 500;
			$response['message'] = !in_array(false, $tasks) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_UNPUBLISH_ORGANIZATIONS_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_UNPUBLISH_ORGANIZATIONS_FAILED');
			$response['status']  = !in_array(false, $tasks);
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function publishorganization()
	{
		$this->checkToken();

		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::UPDATE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$orgs_ids = [];
		$ids      = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$orgs_ids = explode(',', $ids);
		}
		$id = $this->input->getInt('id', 0);
		if ($id > 0)
		{
			$orgs_ids[] = $id;
		}

		if (empty($orgs_ids))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_PUBLISH_ORGANIZATIONS_NO_ID');
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			$tasks = [];
			foreach ($orgs_ids as $org_id)
			{
				$tasks[] = $this->organizationRepository->togglePublished($org_id, true);
			}

			$response['code']    = !in_array(false, $tasks) ? 200 : 500;
			$response['message'] = !in_array(false, $tasks) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_PUBLISH_ORGANIZATIONS_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_PUBLISH_ORGANIZATIONS_FAILED');
			$response['status']  = !in_array(false, $tasks);
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function deleteorganization()
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::DELETE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		$orgs_ids = [];
		$ids      = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$orgs_ids = explode(',', $ids);
		}
		$id = $this->input->getInt('id', 0);
		if ($id > 0)
		{
			$orgs_ids[] = $id;
		}

		if (empty($orgs_ids))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_DELETE_ORGANIZATIONS_NO_ID');
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			$tasks = [];
			foreach ($orgs_ids as $org_id)
			{
				$tasks[] = $this->organizationRepository->delete($org_id);
			}

			$response['code']    = !in_array(false, $tasks) ? 200 : 500;
			$response['message'] = !in_array(false, $tasks) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_DELETE_ORGANIZATIONS_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_DELETE_ORGANIZATIONS_FAILED');
			$response['status']  = !in_array(false, $tasks);
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function saveorganization()
	{
		$this->checkToken();

		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		$id = $this->input->getInt('id', 0);

		if (
			!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::CREATE->value, $this->user->id)
			&&
			($id > 0 && !EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::UPDATE->value, $this->user->id))
		)
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		// Required fields
		$name = $this->input->getString('name', '');

		if (empty($name))
		{
			$response['code']    = 400;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_SAVE_ORGANIZATION_NO_NAME');
			$this->sendJsonResponse($response);

			return;
		}

		// Optional fields
		$description     = $this->input->getString('description', '');
		$identifier_code = $this->input->getString('identifier_code', '');
		$url_website     = $this->input->getString('url_website', '');

		// Address
		$address_id       = $this->input->getInt('address_id', 0);
		$street_address   = $this->input->getString('street_address', '');
		$extended_address = $this->input->getString('extended_address', '');
		$locality         = $this->input->getString('locality', '');
		$region           = $this->input->getString('region', '');
		$postal_code      = $this->input->getString('postal_code', '');
		$country          = $this->input->getInt('country', '');
		$logo             = $this->input->files->get('logo');
		$logo_path        = $this->input->getString('logo');

		$referent_contacts = $this->input->getString('referent_contacts', '');
		if (!empty($referent_contacts))
		{
			$referent_contacts = explode(',', $referent_contacts);
		}
		else
		{
			$referent_contacts = [];
		}

		$other_contacts = $this->input->getString('other_contacts', '');
		if (!empty($other_contacts))
		{
			$other_contacts = explode(',', $other_contacts);
		}
		else
		{
			$other_contacts = [];
		}

		if (!empty($url_website)) {
			$url_website = trim($url_website);

			if (str_starts_with($url_website, 'http://')) {
				$url_website = 'https://' . substr($url_website, 7);
			}
			elseif (!str_starts_with($url_website, 'https://')) {
				$url_website = 'https://' . $url_website;
			}
		}

		if ((!empty($logo) && $logo['error'] === 0) || $logo_path === 'null' || empty($logo_path))
		{
			// Delete old logo if exists
			if ($id > 0)
			{
				$this->organizationRepository->deleteLogo($id);
			}

			$upload_dir = 'images/emundus/organizations/';
			$uploader   = new UploadService($upload_dir);

			if((!empty($logo) && $logo['error'] === 0)) {
				try
				{
					$logo_path = $uploader->upload($logo, $name, 'org');
				}
				catch (UploadException $e)
				{
					$this->sendJsonResponse([
						'code'    => 400,
						'message' => $e->getMessage(),
					]);
				}
			}
		}

		try
		{
			if (!empty($street_address) || !empty($extended_address) || !empty($locality) || !empty($region) || !empty($postal_code) || !empty($country))
			{
				if (!class_exists('AddressEntity'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/AddressEntity.php';
				}

				$addressEntity = new AddressEntity(
					id: $address_id ?? 0,
					locality: $locality ?? '',
					region: $region ?? '',
					street_address: $street_address ?? '',
					extended_address: $extended_address ?? '',
					postal_code: $postal_code ?? '',
					country: $country ?? '',
				);
			}

			$referent_contacts_entities = [];
			$other_contacts_entities    = [];
			if (!empty($referent_contacts) || !empty($other_contacts))
			{
				if (!class_exists('ContactRepository'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/ContactRepository.php';
				}
				$contact_repository = new ContactRepository();
				foreach ($referent_contacts as $referent_contact)
				{
					$contact_entity = $contact_repository->getById($referent_contact);
					if ($contact_entity instanceof ContactEntity)
					{
						$referent_contacts_entities[] = $contact_entity;
					}
				}
				foreach ($other_contacts as $other_contact)
				{
					$contact_entity = $contact_repository->getById($other_contact);
					if ($contact_entity instanceof ContactEntity)
					{
						$other_contacts_entities[] = $contact_entity;
					}
				}
			}

			if (!class_exists('OrganizationEntity'))
			{
				require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/OrganizationEntity.php';
			}
			$status = EmundusHelperAccess::asPartnerAccessLevel($this->user->id) ? VerifiedStatusEnum::VERIFIED : VerifiedStatusEnum::TO_BE_VERIFIED;

			$orgEntity = new OrganizationEntity(
				id: $id,
				name: $name,
				description: $description,
				url_website: $url_website,
				address: $addressEntity ?? null,
				identifier_code: $identifier_code,
				logo: !empty($logo_path) && $logo_path !== "null" ? $logo_path : null,
				referent_contacts: $referent_contacts_entities,
				other_contacts: $other_contacts_entities,
				status: $status,
			);

			$this->organizationRepository->flush($orgEntity);

			$response['code']    = !empty($orgEntity->getId()) ? 200 : 500;
			$response['message'] = !empty($orgEntity->getId()) ? Text::_('COM_EMUNDUS_ONBOARD_CRC_SAVE_ORGANIZATION_SUCCESSED') : Text::_('COM_EMUNDUS_ONBOARD_CRC_SAVE_ORGANIZATION_FAILED');
			$response['status']  = true;
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function getfilteredorganizations()
	{
		if (!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::DELETE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}

		try {
			$organizations    = $this->organizationRepository->getFilteredOrganizations();
			$response['data'] = $organizations;

			$response['code']    = 200;
			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_GET_FILTERED_ORGANIZATIONS_SUCCESS');
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}


		$this->sendJsonResponse($response);
	}

	public function getfilteredorganizationsbyidentifiercode()
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->orgAction->getId(), CrudEnum::DELETE->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}
		try {
			$contacts         = $this->organizationRepository->getFilteredOrganizationsByIdentifierCode();
			$response['data'] = $contacts;

			$response['code']    = 200;
			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_IDENTIFIER_CODE_SUCCESS');
		}
		catch (Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function exportcsvorganizations(): void
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (!EmundusHelperAccess::asAccessAction($this->contactAction->getId(), CrudEnum::READ->value, $this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = Text::_('ACCESS_DENIED');
			$this->sendJsonResponse($response);

			return;
		}


		$ids = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$ids = explode(',', $ids);
		}
		else
		{
			$ids = [];
		}

		if (!empty($ids))
		{
			$response['code']    = 200;
			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_EXPORT_CSV_ORGANIZATIONS_SUCCESS');

			$organizations = $this->organizationRepository->getAllOrganizations('ASC', '', 0, 0, 't.id', null, $ids);

			$excel_filename = 'export_organizations_' . date('Ymd_His') . '.csv';
			$excel_filepath = JPATH_SITE . '/tmp/' . $excel_filename;
			$fp             = fopen($excel_filepath, 'w');

			$columns = [
				Text::_('COM_EMUNDUS_USERNAME'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_ORG_NAME'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_ORG_DESCRIPTION'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_ORG_IDENTIFIER_CODE'),
				Text::_('COM_EMUNDUS_ONBOARD_ADD_ORG_URL_WEBSITE'),
				Text::_('COM_EMUNDUS_PUBLISH'),
				Text::_('COM_EMUNDUS_ONBOARD_CONTACTS_STATUS')
			];
			fputcsv($fp, $columns, ';');

			$rows = [];
			foreach ($organizations['datas'] as $key => $organization)
			{
				if (!$organization instanceof OrganizationEntity)
				{
					continue;
				}
				$row    = [
					$organization->getId(),
					$organization->getName(),
					$organization->getDescription(),
					$organization->getIdentifierCode(),
					$organization->getUrlWebsite(),
					$organization->isPublished() ? Text::_('COM_EMUNDUS_ONBOARD_CONTACTS_PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_CONTACTS_UNPUBLISHED'),
					$organization->getStatus()->getLabel()
				];
				$rows[] = $row;

				fputcsv($fp, $row, ';');
			}

			fclose($fp);

			$nb_cols = count($columns);
			$nb_rows = count($rows);

			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/models/users.php');
			$m_users  = new EmundusModelUsers();
			$xls_file = $m_users->convertCsvToXls($excel_filename, $nb_cols, $nb_rows, 'export_organizations_' . date('Ymd_His'), ';');

			$excel_filepath = '';
			if (!empty($xls_file))
			{
				$excel_filepath = JPATH_SITE . '/tmp/' . $xls_file;
			}

			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="' . basename($excel_filepath) . '"');
			header('Content-Length: ' . filesize($excel_filepath));

			$response['download_file'] = Uri::root() . 'tmp/' . basename($excel_filepath);
		}
		else
		{
			$response['code']    = 400;
			$response['status']  = false;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_CRC_EXPORT_CSV_NO_SELECTION');
		}

		$this->sendJsonResponse($response);
	}
}
