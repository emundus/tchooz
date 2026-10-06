<?php
/**
 * @package     Tchooz\Fixtures\SampleData\Data
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\SampleData\Data;

\defined('_JEXEC') or die;

/**
 * Static showcase programs + campaigns used to seed the demo catalogue.
 *
 * Kept as data-only (no DB access) so it stays trivially reviewable and the
 * seeder owns all persistence logic.
 */
final class ProgramFixtures
{
	/**
	 * @return array<int, array<string, mixed>> Programs, each with a nested `campaigns` list.
	 */
	public static function programs(): array
	{
		$nextYear = date('Y', strtotime('+1 year'));
		$lastYear = date('Y', strtotime('-1 year'));

		return [
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
						'description'       => '<p class="ql-align-justify">Un projet émergent se déroule sur 2 ans maximum et sera estimé entre 50&nbsp;000 € et 150&nbsp;000&nbsp;€ d’intervention régionale.</p>',
						'short_description' => '<p>Questionnaire à remplir pour soumettre un dossier au Comité local d\'éthique de la recherche</p>',
						'start_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
						'end_date'          => date('Y-m-d H:i:s', strtotime('+1 year')),
						'profile_id'        => 1001,
						'training'          => 'comitlocal-65cca2bbbcb02',
						'year'              => date('Y') . '-' . $nextYear,
						'published'         => 1,
						'pinned'            => 1,
						'alias'             => 'soumettre-un-dossier-au-comite-local-d-ethique-de-la-recherche',
					],
				],
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
						'short_description' => '<p><strong>Appel à Manifestation d’intérêt Projet Excellence CaeSAR dans le cadre de l’axe 2 du projet.</strong></p>',
						'start_date'        => date('Y-m-d H:i:s', strtotime('-1 year')),
						'end_date'          => date('Y-m-d H:i:s', strtotime('-20 day')),
						'profile_id'        => 1001,
						'training'          => 'amiexcelle-65ce3049ccc21',
						'year'              => date('Y') . '-' . $nextYear,
						'published'         => 1,
						'pinned'            => 0,
						'alias'             => 'ami-excellence',
					],
					[
						'label'             => '[TEST] AMI Excellences 2ème session',
						'description'       => '',
						'short_description' => '<p><strong>Appel à Manifestation d’intérêt Projet Excellence CaeSAR dans le cadre de l’axe 2 du projet.</strong></p>',
						'start_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
						'end_date'          => date('Y-m-d H:i:s', strtotime('+1 year')),
						'profile_id'        => 1001,
						'training'          => 'comitlocal-65cca2bbbcb02',
						'year'              => date('Y') . '-' . $nextYear,
						'published'         => 1,
						'pinned'            => 0,
						'alias'             => 'ami-excellence-2eme-session',
					],
				],
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
						'year'              => date('Y') . '-' . $nextYear,
						'published'         => 0,
						'pinned'            => 0,
						'alias'             => 'master-2-economie',
					],
				],
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
						'year'              => $lastYear . '-' . date('Y'),
						'published'         => 0,
						'pinned'            => 0,
						'alias'             => 'master-1-journalisme' . $lastYear,
					],
					[
						'label'             => '[TEST] Master 1 Journalisme',
						'description'       => '',
						'short_description' => '',
						'start_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
						'end_date'          => date('Y-m-d H:i:s', strtotime('+30 day')),
						'profile_id'        => 1001,
						'training'          => 'm1-j',
						'year'              => date('Y') . '-' . $nextYear,
						'published'         => 1,
						'pinned'            => 0,
						'alias'             => 'master-1-journalisme-' . date('Y'),
					],
				],
			],
		];
	}
}
