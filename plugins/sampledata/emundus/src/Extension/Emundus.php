<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Sampledata.blog
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Plugin\SampleData\Emundus\Extension;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Sampledata - Blog Plugin
 *
 * @since  3.8.0
 */
final class Emundus extends CMSPlugin
{
	use DatabaseAwareTrait;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 *
	 * @since  3.8.0
	 */
	protected $autoloadLanguage = true;

	protected $db;

	/**
	 * Get an overview of the proposed sampledata.
	 *
	 * @return  \stdClass|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onSampledataGetOverview()
	{
		if (!$this->getApplication()->getIdentity()->authorise('core.create', 'com_content'))
		{
			return;
		}

		$data              = new \stdClass();
		$data->name        = $this->_name;
		$data->title       = $this->getApplication()->getLanguage()->_('PLG_SAMPLEDATA_EMUNDUS_OVERVIEW_TITLE');
		$data->description = $this->getApplication()->getLanguage()->_('PLG_SAMPLEDATA_EMUNDUS_OVERVIEW_DESC');
		$data->icon        = 'fa fa-graduation-cap';
		$data->steps       = 4;

		$this->db = Factory::getContainer()->get('DatabaseDriver');

		return $data;
	}

	/**
	 * First step to enter the sampledata. Campaigns and programs.
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep1()
	{
		if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name)
		{
			return;
		}

		$response            = [];
		$response['success'] = true;

		if (!ComponentHelper::isEnabled('com_emundus'))
		{
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_EMUNDUS_STEP_SKIPPED', 1, 'com_emundus');

			return $response;
		}

		try
		{
			// Get some metadata.
			$user                = $this->getApplication()->getIdentity();
			$eMConfig            = ComponentHelper::getParams('com_emundus');
			$all_rights_group_id = $eMConfig->get('all_rights_group', 1);

			$programs = [
				[
					'code'                     => 'comitlocal-65cca2bbbcb02',
					'label'                    => '[TEST] Comité local d\'éthique de la recherche (CLER)',
					'published'                => 1,
					'programmes'               => 'Appels à projets',
					'synthesis'                => '<ul><li><strong>[APPLICANT_NAME]</strong></li><li><a href="mailto:[EMAIL]">[EMAIL]</a></li></ul>',
					'fabrik_group_id'          => 551,
					'fabrik_decision_group_id' => 552,
					'apply_online'             => 1,
					'ordering'                 => 0,
					'campaigns'                => [
						[
							'label'             => '[TEST] Soumettre un dossier au Comité local d\'éthique de la recherche',
							'description'       => '<p><strong><u>Actions éligibles&nbsp;: </u></strong></p><p><br></p><p class="ql-align-justify">Un projet émergent se déroule sur 2 ans maximum et sera estimé entre 50&nbsp;000 € et 150&nbsp;000&nbsp;€ d’intervention régionale. Il est porté par un unique établissement souhaitant qu’un chercheur ou une équipe de recherche puisse développer un projet précurseur ou une nouvelle thématique. Le porteur doit démontrer la cohérence du projet avec la politique scientifique de l’unité de recherche concernée, mais également toute la singularité du projet et la prise de risque encourue.</p><p class="ql-align-justify"><br></p><p class="ql-align-justify">Le projet doit s’inscrire dans un des domaines de la S3 ou dans les thématiques spécifiées dans l’Accord de Partenariat Stratégique de l’établissement.</p><p class="ql-align-justify"><br></p><p class="ql-align-justify">L’établissement pourra mobiliser ce dispositif pour attirer des jeunes talents souhaitant se préparer pour le programme Jeunes chercheuses Jeunes chercheurs de l’Agence nationale de la Recherche.</p><p class="ql-align-justify"><br></p><p class="ql-align-justify">L’établissement bénéficiaire devra s’engager à mettre en place un accompagnement dédié au projet pour que son porteur puisse donner suite à la prise de risque à l’issue du projet.</p><p>La thématique du projet ne doit pas avoir fait l’objet d’un précédent financement.&nbsp;</p>',
							'short_description' => '<p>Questionnaire à remplir pour soumettre un dossier au Comité local d\'éthique de la recherche</p>',
							'start_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
							'end_date'          => date('Y-m-d H:i:s', strtotime('+1 year')),
							'profile_id'        => 1001,
							'training'          => 'comitlocal-65cca2bbbcb02',
							'year'              => date('Y') . '-' . date('Y', strtotime('+1 year')),
							'published'         => 1,
							'pinned'            => 1,
							'alias'             => 'soumettre-un-dossier-au-comite-local-d-ethique-de-la-recherche',
						]
					]
				],
				[
					'code'                     => 'amiexcelle-65ce3049ccc21',
					'label'                    => '[TEST] AMI Excellences',
					'published'                => 1,
					'programmes'               => 'Appels à projets',
					'synthesis'                => '<ul><li><strong>[APPLICANT_NAME]</strong></li><li><a href="mailto:[EMAIL]">[EMAIL]</a></li></ul>',
					'fabrik_group_id'          => 551,
					'fabrik_decision_group_id' => 552,
					'apply_online'             => 1,
					'ordering'                 => 0,
					'campaigns'                => [
						[
							'label'             => '[TEST] AMI Excellences',
							'description'       => '',
							'short_description' => '<p><strong>Appel à Manifestation d’intérêt Projet Excellence CaeSAR dans le cadre de l’axe 2 du projet : stratégie de recherche durable et ressourcement scientifique</strong></p>',
							'start_date'        => date('Y-m-d H:i:s', strtotime('-1 year')),
							'end_date'          => date('Y-m-d H:i:s', strtotime('-20 day')),
							'profile_id'        => 1001,
							'training'          => 'amiexcelle-65ce3049ccc21',
							'year'              => date('Y') . '-' . date('Y', strtotime('+1 year')),
							'published'         => 1,
							'pinned'            => 0,
							'alias'             => 'ami-excellence',
						],
						[
							'label'             => '[TEST] AMI Excellences 2ème session',
							'description'       => '',
							'short_description' => '<p><strong>Appel à Manifestation d’intérêt Projet Excellence CaeSAR dans le cadre de l’axe 2 du projet : stratégie de recherche durable et ressourcement scientifique</strong></p>',
							'start_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
							'end_date'          => date('Y-m-d H:i:s', strtotime('+1 year')),
							'profile_id'        => 1001,
							'training'          => 'comitlocal-65cca2bbbcb02',
							'year'              => date('Y') . '-' . date('Y', strtotime('+1 year')),
							'published'         => 1,
							'pinned'            => 0,
							'alias'             => 'ami-excellence-2eme-session',
						],
					]
				],
				[
					'code'                     => 'm2-eco',
					'label'                    => '[TEST] M2 Economie',
					'published'                => 1,
					'programmes'               => 'Economie',
					'synthesis'                => '<ul><li><strong>[APPLICANT_NAME]</strong></li><li><a href="mailto:[EMAIL]">[EMAIL]</a></li></ul>',
					'fabrik_group_id'          => 551,
					'fabrik_decision_group_id' => 552,
					'apply_online'             => 1,
					'ordering'                 => 0,
					'campaigns'                => [
						[
							'label'             => '[TEST] Master 2 Economie',
							'description'       => '',
							'short_description' => '',
							'start_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
							'end_date'          => date('Y-m-d H:i:s', strtotime('+30 day')),
							'profile_id'        => 1001,
							'training'          => 'm2-eco',
							'year'              => date('Y') . '-' . date('Y', strtotime('+1 year')),
							'published'         => 0,
							'pinned'            => 0,
							'alias'             => 'master-2-economie',
						],
					]
				],
				[
					'code'                     => 'm1-j',
					'label'                    => '[TEST] M1 Journalisme',
					'published'                => 1,
					'programmes'               => 'Journalisme',
					'synthesis'                => '<ul><li><strong>[APPLICANT_NAME]</strong></li><li><a href="mailto:[EMAIL]">[EMAIL]</a></li></ul>',
					'fabrik_group_id'          => 551,
					'fabrik_decision_group_id' => 552,
					'apply_online'             => 1,
					'ordering'                 => 0,
					'campaigns'                => [
						[
							'label'             => '[TEST] Master 1 Journalisme',
							'description'       => '',
							'short_description' => '',
							'start_date'        => date('Y-m-d H:i:s', strtotime('-2 year')),
							'end_date'          => date('Y-m-d H:i:s', strtotime('-1 year')),
							'profile_id'        => 1001,
							'training'          => 'm1-j',
							'year'              => date('Y', strtotime('-1 year')) . '-' . date('Y'),
							'published'         => 0,
							'pinned'            => 0,
							'alias'             => 'master-1-journalisme' . date('Y', strtotime('-1 year')),
						],
						[
							'label'             => '[TEST] Master 1 Journalisme',
							'description'       => '',
							'short_description' => '',
							'start_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
							'end_date'          => date('Y-m-d H:i:s', strtotime('+30 day')),
							'profile_id'        => 1001,
							'training'          => 'm1-j',
							'year'              => date('Y') . '-' . date('Y', strtotime('+1 year')),
							'published'         => 1,
							'pinned'            => 0,
							'alias'             => 'master-1-journalisme-' . date('Y'),
						],
					]
				],
			];

			foreach ($programs as $program)
			{
				$campaigns = $program['campaigns'];
				unset($program['campaigns']);

				$program = (object) $program;
				if (!$this->db->insertObject('#__emundus_setup_programmes', $program))
				{
					throw new \RuntimeException($this->db->getErrorMsg());
				}

				$link_program = [
					'parent_id' => $all_rights_group_id,
					'course'    => $program->code,
				];
				$link_program = (object) $link_program;
				$this->db->insertObject('#__emundus_setup_groups_repeat_course', $link_program);

				foreach ($campaigns as $campaign)
				{
					$campaign['user'] = $user->id;
					$campaign         = (object) $campaign;
					if (!$this->db->insertObject('#__emundus_setup_campaigns', $campaign))
					{
						throw new \RuntimeException($this->db->getErrorMsg());
					}
				}
			}
		}
		catch (\Exception $e)
		{
			$response['success'] = false;
			$response['message'] = $e->getMessage();

			return $response;
		}

		$response['message'] = $this->getApplication()->getLanguage()->_('PLG_SAMPLEDATA_EMUNDUS_STEP1_SUCCESS');

		return $response;
	}

	/**
	 * Second step to enter the sampledata. User and application files
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep2()
	{
		if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name)
		{
			return;
		}

		$response            = [];
		$response['success'] = true;

		if (!ComponentHelper::isEnabled('com_users'))
		{
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_EMUNDUS_STEP_SKIPPED', 2, 'com_users');

			return $response;
		}

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('id')
				->from('#__emundus_setup_campaigns');
			$this->db->setQuery($query);
			$campaigns = $this->db->loadColumn();
			shuffle($campaigns);

			$users = [
				[
					'firstname'     => 'John',
					'lastname'      => 'Doe',
					'name'          => 'John Doe',
					'username'      => 'johndoe@emundus.fr',
					'email'         => 'johndoe@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'Jane',
					'lastname'      => 'Doe',
					'name'          => 'Jane Doe',
					'username'      => 'janedoe@emundus.fr',
					'email'         => 'janedoe@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => array_unique([$campaigns[rand(1, count($campaigns))], $campaigns[rand(1, count($campaigns))]])
				],
				[
					'firstname'     => 'John',
					'lastname'      => 'Smith',
					'name'          => 'John Smith',
					'username'      => 'johnsmith@emundus.fr',
					'email'         => 'johnsmith@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'Alice',
					'lastname'      => 'Johnson',
					'name'          => 'Alice Johnson',
					'username'      => 'alicejohnson@emundus.fr',
					'email'         => 'alicejohnson@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => []
				],
				[
					'firstname'     => 'Robert',
					'lastname'      => 'Brown',
					'name'          => 'Robert Brown',
					'username'      => 'robertbrown@emundus.fr',
					'email'         => 'robertbrown@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'Emily',
					'lastname'      => 'Davis',
					'name'          => 'Emily Davis',
					'username'      => 'emilydavis@emundus.fr',
					'email'         => 'emilydavis@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'Michael',
					'lastname'      => 'Wilson',
					'name'          => 'Michael Wilson',
					'username'      => 'michaelwilson@emundus.fr',
					'email'         => 'michaelwilson@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => array_unique([$campaigns[rand(1, count($campaigns))], $campaigns[rand(1, count($campaigns))], $campaigns[rand(1, count($campaigns))]])
				],
				[
					'firstname'     => 'Laura',
					'lastname'      => 'Martinez',
					'name'          => 'Laura Martinez',
					'username'      => 'lauramartinez@emundus.fr',
					'email'         => 'lauramartinez@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'David',
					'lastname'      => 'Garcia',
					'name'          => 'David Garcia',
					'username'      => 'davidgarcia@emundus.fr',
					'email'         => 'davidgarcia@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'Sophia',
					'lastname'      => 'Anderson',
					'name'          => 'Sophia Anderson',
					'username'      => 'sophiaanderson@emundus.fr',
					'email'         => 'sophiaanderson@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'James',
					'lastname'      => 'Taylor',
					'name'          => 'James Taylor',
					'username'      => 'jamestaylor@emundus.fr',
					'email'         => 'jamestaylor@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]]
				],
				[
					'firstname'     => 'Olivia',
					'lastname'      => 'Moore',
					'name'          => 'Olivia Moore',
					'username'      => 'oliviamoore@emundus.fr',
					'email'         => 'oliviamoore@emundus.fr',
					'password'      => ApplicationHelper::getHash(UserHelper::genRandomPassword()),
					'block'         => 0,
					'sendEmail'     => 0,
					'registerDate'  => date('Y-m-d H:i:s'),
					'lastvisitDate' => date('Y-m-d H:i:s'),
					'activation'    => 1,
					'params'        => '{}',
					'profile'       => 1001,
					'j_groups'      => [2],
					'campaigns'     => [$campaigns[rand(1, count($campaigns))]],
				]
			];

			foreach ($users as $user)
			{
				$user_id = $this->createSampleUser($user);
				if (empty($user_id))
				{
					throw new \RuntimeException('Failed to create sample user');
				}

				$query->clear()
					->select('fnum')
					->from($this->db->quoteName('#__emundus_campaign_candidature'))
					->where($this->db->quoteName('applicant_id') . ' = ' . (int) $user_id);
				$this->db->setQuery($query);
				$fnums = $this->db->loadColumn();

				foreach ($fnums as $fnum)
				{
					$datas = $this->createSampleDatas($user);

					foreach ($datas as $table => $data)
					{
						$data['fnum']      = $fnum;
						$data['user']      = $user_id;
						$data['time_date'] = date('Y-m-d H:i:s');

						$repeat_datas = [];
						foreach ($data as $key => $value)
						{
							if (is_array($value))
							{
								$repeat_datas[$key] = $value;
								unset($data[$key]);
							}
						}

						$data = (object) $data;
						if (!$this->db->insertObject('#__' . $table, $data))
						{
							throw new \RuntimeException($this->db->getErrorMsg());
						}

						$parent_id = $this->db->insertid();

						foreach ($repeat_datas as $repeat_table => $repeat_data)
						{
							foreach ($repeat_data as $repeat)
							{
								$repeat['parent_id'] = $parent_id;
								$repeat              = (object) $repeat;
								if (!$this->db->insertObject('#__' . $repeat_table, $repeat))
								{
									throw new \RuntimeException($this->db->getErrorMsg());
								}
							}
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			$response['success'] = false;
			$response['message'] = $e->getMessage();

			return $response;
		}

		$response['message'] = $this->getApplication()->getLanguage()->_('PLG_SAMPLEDATA_EMUNDUS_STEP2_SUCCESS');

		return $response;
	}

	/**
	 * Third step to enter the sampledata. Add sample files to applications
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep3()
	{
		if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name)
		{
			return;
		}

		$response            = [];
		$response['success'] = true;

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('fnum,applicant_id')
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->order('RAND()')
				->setLimit(8);
			$this->db->setQuery($query);
			$applications = $this->db->loadObjectList();

			foreach ($applications as $application)
			{
				$source      = JPATH_ROOT . '/plugins/sampledata/emundus/src/samples/pdf_emundus.pdf';
				$destination = JPATH_ROOT . '/images/emundus/files/' . $application->applicant_id;
				if (!is_dir($destination))
				{
					mkdir($destination, 0755, true);
				}
				copy($source, $destination . '/pdf_emundus.pdf');

				$upload = [
					'timedate'      => date('Y-m-d H:i:s'),
					'user_id'       => $application->applicant_id,
					'fnum'          => $application->fnum,
					'attachment_id' => 12,
					'filename'      => 'pdf_emundus.pdf',
				];
				$upload = (object) $upload;
				if (!$this->db->insertObject('#__emundus_uploads', $upload))
				{
					throw new \RuntimeException($this->db->getErrorMsg());
				}
			}
		}
		catch (\Exception $e)
		{
			$response['success'] = false;
			$response['message'] = $e->getMessage();

			return $response;
		}

		$response['message'] = $this->getApplication()->getLanguage()->_('PLG_SAMPLEDATA_EMUNDUS_STEP3_SUCCESS');

		return $response;
	}

	/**
	 * Fourth step to enter the sampledata. Add sample files to applications
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep4()
	{
		if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name)
		{
			return;
		}

		$response            = [];
		$response['success'] = true;

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('fnum,applicant_id')
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->order('RAND()');
			$this->db->setQuery($query);
			$applications = $this->db->loadObjectList();

			$query->clear()
				->select('step')
				->from($this->db->quoteName('#__emundus_setup_status'));
			$this->db->setQuery($query);
			$status = $this->db->loadColumn();

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_action_tag'));
			$this->db->setQuery($query);
			$tags = $this->db->loadColumn();

			foreach ($applications as $application)
			{
				$query->clear()
					->update($this->db->quoteName('#__emundus_campaign_candidature'))
					->set($this->db->quoteName('status') . ' = ' . $this->db->quote($status[array_rand($status)]))
					->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($application->fnum));
				$this->db->setQuery($query);
				$this->db->execute();

				$no_tags = rand(0, 2);
				if (!empty($no_tags))
				{
					$tags_key = array_rand($tags, $no_tags);
					if (!is_array($tags_key)) $tags_key = [$tags_key];

					foreach ($tags_key as $key)
					{
						$insert = [
							'fnum'    => $application->fnum,
							'id_tag'  => $tags[$key],
							'user_id' => $application->applicant_id,
						];
						$insert = (object) $insert;
						if (!$this->db->insertObject('#__emundus_tag_assoc', $insert))
						{
							throw new \RuntimeException($this->db->getErrorMsg());
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			$response['success'] = false;
			$response['message'] = $e->getMessage();

			return $response;
		}

		$response['message'] = $this->getApplication()->getLanguage()->_('PLG_SAMPLEDATA_EMUNDUS_STEP4_SUCCESS');

		return $response;
	}

	private function createSampleDatas($user)
	{
		$query = $this->db->getQuery(true);

		// Get options from elements
		$genders = ['Monsieur', 'Madame'];
		$query->select('id')
			->from('data_nationality');
		$this->db->setQuery($query);
		$nationalities     = $this->db->loadColumn();
		$cities            = ['Paris', 'La Rochelle', 'Nantes', 'Marseille', 'Bruxelles', 'New York', 'Madrid', 'Barcelone'];
		$zip_codes         = ['75001', '75002', '75003', '75004', '75005', '75006', '75007', '75008', '75009', '75010', '64505', '17000', '16100', '75014', '75015', '75016', '75017', '75018', '75019', '75020'];
		$fonctions         = ['Développeur', 'Business Développeur', 'Chef de projet', 'Directeur', 'Manager', 'Responsable', 'Consultant', 'Ingénieur', 'Architecte', 'Designer', 'Graphiste', 'Décorateur'];
		$languages         = [1, 2, 3, 4, 5, 6, 7];
		$languages_repeat         = ['Anglais', 'Allemand', 'Arabe', 'Chinois', 'Espagnol', 'Italien', 'Russe'];
		$languages_writing = ['Débutant', 'Intermédiaire', 'Avancé'];
		$survey            = [1, 2, 3, 4];
		$query->clear()
			->select('id')
			->from('data_country');
		$this->db->setQuery($query);
		$countries = $this->db->loadColumn();
		$yes_no    = ['Non', 'Oui'];
		//

		$datas = [
			'emundus_personal_detail' => [
				'gender'        => $genders[array_rand($genders)],
				'first_name'    => $user['firstname'],
				'last_name'     => $user['lastname'],
				'birth_date'    => date('Y-m-d', strtotime('-' . rand(18, 50) . ' years')),
				'birth_place'   => $cities[array_rand($cities)],
				'birth_country' => $countries[array_rand($countries)],
				'nationality'   => $nationalities[array_rand($nationalities)],
				'country_1'     => $countries[array_rand($countries)],
				'city_1'        => $cities[array_rand($cities)],
				'zipcode_1'     => $zip_codes[array_rand($zip_codes)],
				'telephone_1'   => '+33 ' . rand(6, 9) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
				'street_1'      => rand(1, 200) . ' rue de ' . ucfirst($cities[array_rand($cities)]),
			],
			'emundus_qualifications'  => [
				'e_324_7736' => $yes_no[array_rand($yes_no)],
				'e_324_7737' => $yes_no[array_rand($yes_no)],
			],
			'emundus_cv'              => [
				'e_325_7742' => $yes_no[array_rand($yes_no)],
			],
			'emundus_languages'       => [
				'first_language' => $languages[array_rand($languages)],
			],
			'emundus_9_00'            => [
				'e_360_7754' => '["'.$survey[array_rand($survey)].'"]',
			]
		];

		// Repeat datas
		if ($datas['emundus_qualifications']['e_324_7736'] == 'Oui')
		{
			$rand_repeat = rand(1, 3);

			for ($i = 0; $i < $rand_repeat; $i++)
			{
				$datas['emundus_qualifications']['emundus_qualifications_686_repeat'][] = [
					'from'       => date('Y', strtotime('-' . rand(1, 5) . ' years')),
					'grade'      => 'Bac +' . rand(1, 5),
					'diploma'    => 'Baccalauréat',
					'university' => 'Lycée ' . ucfirst($cities[array_rand($cities)]),
					'city'       => $cities[array_rand($cities)],
					'country'    => $countries[array_rand($countries)],
				];
			}
		}
		if ($datas['emundus_qualifications']['e_324_7737'] == 'Oui')
		{
			$rand_repeat = rand(1, 3);

			for ($i = 0; $i < $rand_repeat; $i++)
			{
				$datas['emundus_qualifications']['emundus_qualifications_689_repeat'][] = [
					'e_324_7738' => date('Y-m-d', strtotime('-' . rand(1, 5) . ' years')),
					'e_324_7739' => date('Y-m-d', strtotime('-' . rand(2, 5) . ' years')),
					'e_324_7740' => 'Formation ' . rand(1, 5),
					'e_324_7741' => 'Certification ' . rand(1, 5),
				];
			}
		}
		if ($datas['emundus_cv']['e_325_7742'] == 'Oui')
		{
			$rand_repeat = rand(1, 3);

			for ($i = 0; $i < $rand_repeat; $i++)
			{
				$datas['emundus_cv']['emundus_cv_690_repeat'][] = [
					'start_date'         => date('Y-m-d H:i:s', strtotime('-' . rand(1, 5) . ' years')),
					'end_date'           => date('Y-m-d H:i:s', strtotime('-' . rand(1, 5) . ' years')),
					'Present_employment' => $fonctions[array_rand($fonctions)],
					'Organisation'       => 'eMundus',
					'e_325_7743'         => $cities[array_rand($cities)],
					'country'            => $countries[array_rand($countries)],
					'description'        => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus. Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor. Cras elementum ultrices diam. Maecenas ligula massa, varius a, semper congue, euismod non, mi.'
				];
			}
		}

		$rand_repeat = rand(1, 3);

		for ($i = 0; $i < $rand_repeat; $i++)
		{
			$datas['emundus_languages']['emundus_languages_692_repeat'][] = [
				'language'         => $languages_repeat[array_rand($languages_repeat)],
				'Language_writing' => $languages_writing[array_rand($languages_writing)]
			];
		}

		return $datas;
	}

	private function createSampleUser($user)
	{
		try
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/users.php';
			$m_users = Factory::getApplication()->bootComponent('com_emundus')->getMVCFactory()->createModel('Users', 'EmundusModel');

			$userObject = (object) $user;
			if (!$this->db->insertObject('#__users', $userObject))
			{
				throw new \RuntimeException($this->db->getErrorMsg());
			}

			$user_id = $this->db->insertid();
			if (!empty($user_id))
			{
				if (!empty($user['j_groups']))
				{
					foreach ($user['j_groups'] as $j_group)
					{
						$group_map = [
							'user_id'  => $user_id,
							'group_id' => $j_group
						];
						$group_map = (object) $group_map;
						if (!$this->db->insertObject('#__user_usergroup_map', $group_map))
						{
							throw new \RuntimeException($this->db->getErrorMsg());
						}
					}
				}

				$emundus_user['firstname']    = $user['firstname'];
				$emundus_user['lastname']     = $user['lastname'];
				$emundus_user['profile']      = $user['profile'];
				$emundus_user['em_oprofiles'] = '';
				$emundus_user['univ_id']      = 0;
				$emundus_user['em_groups']    = '';
				$emundus_user['em_campaigns'] = $user['campaigns'];
				$emundus_user['news']         = '';
				$m_users->addEmundusUser($user_id, $emundus_user);
			}
			else
			{
				error_log('Failed to create sample user');
			}
		}
		catch (\Exception $e)
		{
			return false;
		}

		return $user_id;
	}
}
