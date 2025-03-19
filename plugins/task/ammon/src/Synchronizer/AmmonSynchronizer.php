<?php

namespace Joomla\Plugin\Task\Ammon\Synchronizer;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Plugin\Task\Ammon\Entities\CompanyEntity;
use Joomla\Plugin\Task\Ammon\Entities\RegistrationEntity;
use Joomla\Plugin\Task\Ammon\Entities\UserEntity;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Event\GenericEvent;


require_once(JPATH_SITE . '/components/com_emundus/models/sync.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/filters.php');

class AmmonSynchronizer {
	private \EmundusModelSync $sync_model;
	private $api;

	public function __construct($api)
	{
		$this->sync_model = new \EmundusModelSync();
		$this->api = $api;
		Log::addLogger(['text_file' => 'plugin.emundus.ammon.php'], Log::ALL, array('plugin.emundus.ammon'));
	}

	public function getCompany(string $siret)
	{
		$ammon_company = null;

		if (!empty($siret)) {
			$query = 'SELECT TOP 50 CNOME,CSIRET,CCDP,CBURD,EnTREP.SENTITE ,ENTREP.CENTEXT, ADRESSE.SADRESSE,CENTEXT,ADRESSE.CDESTI, ADRESSE.CADR1,ADRESSE.CADR2,ADRESSE.CADR3,ADRESSE.CADR4,ADRESSE.CCP,ADRESSE.CVILLE, ADRESSE.CPAYS,ADRESSE.CTEL,ADRESSE.CEMAIL,ADRESSE.CADRNATURE,ADRESSE.SADRNATURE
				FROM ENTREP with(nolock)
				JOIN ADRESSE with(nolock) ON ADRESSE.SENTITE=ENTREP.SENTITE
				WHERE CSIRET = \''. $siret . '\'';

			$response = $this->sync_model->callApi($this->api, 'queries/execute', 'post', $query, false);

			if ($response['status'] == 200 && !empty($response['data']->results))
			{
				$ammon_company = $response['data']->results[0];
			}
		}

		return $ammon_company;
	}

	public function createCompany(CompanyEntity $company): bool
	{
		$created = false;

		$body_params = ["ignoreExistingRows" => false, "clearMissingData" => false, "rows" => [json_decode(json_encode($company), true)]];
		$response = $this->sync_model->callApi($this->api, 'companies/bulk/import', 'post', $body_params);

		if (!empty($response) && $response['status'] && $response['data']->status == 'success') {
			$created = true;
		} else {
			Log::add('Failed to create company ' . json_encode($body_params['rows']), Log::ERROR, 'plugin.emundus.ammon');
		}

		return $created;
	}

	public function getUser($lastname, $firstname, $birthdate, bool $force_new_user_if_not_found = false)
	{
		$user = null;

		if (!empty($lastname) && !empty($firstname) && !empty($birthdate)) {
			$get_query = "DECLARE @XNAISS DateTime='$birthdate'
			SELECT PERSONNE.XNAISS, PERSONNE.SENTITE, PERSONNE.CENTEXT, PERSONNE.CNOM, PERSONNE.CPRENOM, PERSONNE.XNAISS, PERSONNE.SADR1 FROM PERSONNE WHERE PERSONNE.XNAISS = @XNAISS;";

			$response = $this->sync_model->callApi($this->api, 'queries/execute', 'post', $get_query, false);

			$current_user_name = $lastname . ' ' . $firstname;
			if (!empty($response) && $response['status'] == 200) {
				$title = 'No match found for user ' . $current_user_name . ' in ammon api. Need to create a new user';

				if (!empty(!empty($response['data']->results))) {
					$found_names = array_map(function($result) {
						$result->cnom = trim($result->cnom);
						$result->cprenom = trim($result->cprenom);

						return $result->cnom . ' ' . $result->cprenom;
					}, $response['data']->results);

					$match = \EmundusHelperFilters::searchClosestWord($current_user_name, $found_names);

					if ($match['lev'] == 0) {
						$title = 'User ' . $current_user_name . ' found in ammon api';
					} else if (!$force_new_user_if_not_found && $match['lev'] > 0 && $match['lev'] <= 4) {
						$title = 'User ' . $current_user_name . ' has a similar name to ' . $found_names[$match['position']] . ' in ammon api. Need a manual check';
					} else {
						$title = 'No match found for user ' . $current_user_name . ' in ammon api. Need to create a new user. Closest name was ' . $found_names[$match['position']] .  ' Lev distance was ' . $match['lev'];
					}

					$onAmmonSync = new GenericEvent(
						'onAmmonSync', ['message_key' => 'PLG_ACTIONLOG_EMUNDUS_AMMON_SEARCH_SIMILAR_NAME', 'title' => $title]
					);
					Factory::getApplication()->getDispatcher()->dispatch('onAmmonSync', $onAmmonSync);

					if ($match['lev'] == 0) {
						$user = $response['data']->results[$match['position']];
					} else if (!$force_new_user_if_not_found && $match['lev'] > 0 && $match['lev'] <= 4) {
						Log::add('User ' . $current_user_name . ' has a similar name to ' . $found_names[$match['position']] . ' in ammon api. Need a manual check', Log::INFO, 'plugin.emundus.ammon');
						throw new \Exception('[SHORT_LEV_DISTANCE] User ' . $current_user_name . ' has a similar name to ' . $found_names[$match['position']] . ' in ammon api. Need a manual check. [CURRENT_USERNAME="' . $current_user_name . '"] [FOUND_USERNAME="' . $found_names[$match['position']] . '"]');
					} else {
						Log::add('No match found for user ' . $current_user_name . ' in ammon api. Need to create a new user. Closest name was ' . $found_names[$match['position']] .  ' Lev distance was ' . $match['lev'], Log::INFO, 'plugin.emundus.ammon');
					}
				} else {
					$onAmmonSync = new GenericEvent(
						'onAmmonSync', ['message_key' => 'PLG_ACTIONLOG_EMUNDUS_AMMON_SEARCH_SIMILAR_NAME', 'title' => $title]
					);
					Factory::getApplication()->getDispatcher()->dispatch('onAmmonSync', $onAmmonSync);

					Log::add('No user found for ' . $current_user_name . ' in ammon api. Need to create a new user', Log::INFO, 'plugin.emundus.ammon');
				}
			} else {
				Log::add('Failed to get user response => ' . json_encode($response), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $user;
	}

	public function getUserFromName($lastname, $firstname) {
		$user = null;

		if (!empty($lastname) && !empty($firstname)) {
			$get_query = "SELECT PERSONNE.XNAISS, PERSONNE.SENTITE, PERSONNE.CENTEXT, PERSONNE.CNOM, PERSONNE.CPRENOM, PERSONNE.XNAISS, PERSONNE.SADR1 FROM PERSONNE WHERE PERSONNE.CNOM = '$lastname' AND PERSONNE.CPRENOM = '$firstname';";

			$response = $this->sync_model->callApi($this->api, 'queries/execute', 'post', $get_query, false);

			if (!empty($response) && $response['status'] == 200 && !empty($response['data']->results)) {
				$user = $response['data']->results[0];

				Log::add('User found for ' . $lastname . ' ' . $firstname . '.', Log::INFO, 'plugin.emundus.ammon');
			} else {
				Log::add('No user found for ' . $lastname . ' ' . $firstname . ' in ammon api. Need to create a new user.', Log::INFO, 'plugin.emundus.ammon');
			}
		}

		return $user;
	}

	public function createUser(UserEntity $user): bool
	{
		$created = false;

		$body_params = ["ignoreExistingRows" => false, "clearMissingData" => false, "rows" => [json_decode(json_encode($user), true)]];
		$response = $this->sync_model->callApi($this->api, 'persons/bulk/import', 'post', $body_params);

		if ($response['status'] == 200 && $response['data']->status == 'success' && !empty($response['data']->results)) {
			$created = true;
			Log::add('Successfully created user ' . $user->lastName . ' ' . $user->firstName, Log::INFO, 'plugin.emundus.ammon');
		} else {
			Log::add('Failed to create user response => ' . json_encode($response), Log::ERROR, 'plugin.emundus.ammon');
		}

		return $created;
	}

	/**
	 * @param   string  $fnum
	 * @param   int     $ammon_session_id
	 *
	 * @return bool
	 */
	public function isRegistered(string $fnum, int $ammon_session_id): bool
	{
		$registered = false;

		if (!empty($fnum) && !empty($ammon_session_id)) {
			$response = $this->sync_model->callApi($this->api, 'courses/' . $ammon_session_id . '/enrolments', 'get', []);

			if (!empty($response) && $response['data']->status == 'success') {
				foreach ($response['data']->results as $enrolment) {
					if ($enrolment->externalReference == $fnum) {
						$registered = true;
						break;
					}
				}
			}
		}

		return $registered;
	}

	public function createRegistration(RegistrationEntity $registration): bool
	{
		$created = false;

		if (!$this->isRegistered($registration->ExternalReference, $registration->CourseId)) {
			$body_params = ["ignoreExistingRows" => false, "clearMissingData" => false, "rows" => [json_decode(json_encode($registration), true)]];
			$response    = $this->sync_model->callApi($this->api, 'enrolments/bulk/import', 'post', $body_params);

			if (!empty($response) && $response['status'] == 200 && $response['data']->status == 'success') {
				$created = true;
			} else {
				Log::add('Failed to create registration ' . $body_params['rows'], Log::ERROR, 'com_emundus.error');
			}
		} else {
			Log::add('Registration for fnum ' . $registration->ExternalReference . ' and session ' . $registration->CourseId . ' already exists', Log::INFO, 'com_emundus.error');
			$created = true;
		}


		return $created;
	}
}
