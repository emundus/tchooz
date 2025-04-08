<?php

namespace scripts\src;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';

class VotingInstall
{
	private DatabaseInterface $db;

	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function install(): array
	{
		$result = ['status' => false, 'message' => ''];

		// Voting module
		$columns      = [
			[
				'name'    => 'created_at',
				'type'    => 'datetime',
				'null'    => 0
			],
			[
				'name'    => 'created_by',
				'type'    => 'integer',
				'length'  => 10,
				'null'    => 0,
			],
			[
				'name'    => 'updated_at',
				'type'    => 'datetime',
				'null'    => 1,
			],
			[
				'name'    => 'updated_by',
				'type'    => 'integer',
				'length'  => 10,
				'null'    => 1,
			],
			[
				'name'    => 'campaign_id',
				'type'    => 'integer',
				'length'  => 10,
				'null'    => 0,
			],
			[
				'name'    => 'list_id',
				'type'    => 'integer',
				'length'  => 10,
				'null'    => 0,
			],
			[
				'name'    => 'is_voting',
				'type'    => 'tinyint',
				'length'  => 3,
				'null'    => 0,
				'default' => 0,
			],
			[
				'name'    => 'voting_access',
				'type'    => 'int',
				'default' => 1,
				'null'    => 0,
			],
			[
				'name'    => 'max',
				'type'    => 'integer',
				'length'  => 10,
			],
			[
				'name'    => 'title',
				'type'    => 'varchar',
				'length'  => 255,
				'null'    => 1,
			],
			[
				'name'    => 'subtitle',
				'type'    => 'varchar',
				'length'  => 255,
				'null'    => 1,
			],
			[
				'name'    => 'subtitle_icon',
				'type'    => 'varchar',
				'length'  => 50,
				'null'    => 1,
			],
			[
				'name'    => 'image',
				'type'    => 'integer',
				'length'  => 10,
				'null'    => 1,
			],
			[
				'name'    => 'tags',
				'type'    => 'varchar',
				'length'  => 255,
				'null'    => 1,
			],
			[
				'name'    => 'resume',
				'type'    => 'varchar',
				'length'  => 255,
				'null'    => 1,
			],
			[
				'name'    => 'banner',
				'type'    => 'integer',
				'length'  => 10,
				'null'    => 1,
			],
			[
				'name'    => 'logo',
				'type'    => 'integer',
				'length'  => 10,
				'null'    => 1,
			],
			[
				'name'    => 'start_date',
				'type'    => 'datetime',
				'null'    => 1,
			],
			[
				'name'    => 'end_date',
				'type'    => 'datetime',
				'null'    => 1,
			],
			[
				'name'    => 'published',
				'type'    => 'tinyint',
				'length'  => 3,
				'default' => 0,
				'null'    => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_fabrik_lists_fk_list_id',
				'from_column'    => 'list_id',
				'ref_table'      => 'jos_fabrik_lists',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true,
			]
		];
		$setup_gallery = EmundusHelperUpdate::createTable('jos_emundus_setup_gallery', $columns, $foreign_keys, 'Configuration des galeries de dossiers');

		$columns      = [
			[
				'name'    => 'parent_id',
				'type'    => 'int',
				'null'    => 0
			],
			[
				'name'    => 'title',
				'type'    => 'varchar',
				'length'  => 255,
				'null'    => 0,
			],
			[
				'name'    => 'fields',
				'type'    => 'text',
				'null'    => 1,
			],
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_setup_gallery_fk_parent_id',
				'from_column'    => 'parent_id',
				'ref_table'      => 'jos_emundus_setup_gallery',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true,
			]
		];
		$setup_gallery_tabs = EmundusHelperUpdate::createTable('jos_emundus_setup_gallery_detail_tabs', $columns, $foreign_keys, 'Onglets de la vue détails du catalogue');

		$columns      = [
			[
				'name'    => 'time_date',
				'type'    => 'datetime',
				'null'    => 0
			],
			[
				'name'    => 'ccid',
				'type'    => 'int',
				'null'    => 1,
			],
			[
				'name'    => 'user',
				'type'    => 'int',
				'null'    => 1,
			],
			[
				'name'    => 'firstname',
				'type'    => 'varchar(255)',
				'null'    => 1,
			],
			[
				'name'    => 'lastname',
				'type'    => 'varchar(255)',
				'null'    => 1,
			],
			[
				'name'    => 'email',
				'type'    => 'varchar(255)',
				'null'    => 1,
			],
			[
				'name'    => 'ip',
				'type'    => 'varchar(20)',
				'null'    => 1,
			],
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_cmapaign_candidature_fk_ccid',
				'from_column'    => 'ccid',
				'ref_table'      => 'jos_emundus_campaign_candidature',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true,
			]
		];
		$vote_table = EmundusHelperUpdate::createTable('jos_emundus_vote', $columns, $foreign_keys, 'Vote des dossiers');
		if($vote_table['message'] == 'CREATE TABLE : Table already exists.') {
			EmundusHelperUpdate::addColumn('jos_emundus_vote', 'time_date', 'DATETIME',null,0);
			EmundusHelperUpdate::addColumn('jos_emundus_vote', 'ccid', 'INT');
			EmundusHelperUpdate::addColumn('jos_emundus_vote', 'user', 'INT');
			EmundusHelperUpdate::addColumn('jos_emundus_vote', 'firstname', 'VARCHAR', 255);
			EmundusHelperUpdate::addColumn('jos_emundus_vote', 'lastname', 'VARCHAR', 255);
			EmundusHelperUpdate::addColumn('jos_emundus_vote', 'email', 'VARCHAR', 255);
			EmundusHelperUpdate::addColumn('jos_emundus_vote', 'ip', 'VARCHAR', 20);
		}

		$column_existing = $this->db->setQuery('SHOW COLUMNS FROM jos_emundus_vote WHERE ' . $this->db->quoteName('Field') . ' = ' . $this->db->quote('thematique'))->loadResult();
		if (!empty($column_existing)) {
			$this->db->setQuery('ALTER TABLE jos_emundus_vote MODIFY thematique INT NULL');
			$this->db->execute();
		}

		$column_existing = $this->db->setQuery('SHOW COLUMNS FROM jos_emundus_vote WHERE ' . $this->db->quoteName('Field') . ' = ' . $this->db->quote('fnum'))->loadResult();
		if (!empty($column_existing)) {
			$this->db->setQuery('ALTER TABLE jos_emundus_vote MODIFY fnum VARCHAR(28) NULL');
			$this->db->execute();
		}

		$this->db->setQuery('ALTER TABLE jos_emundus_vote MODIFY ' . $this->db->quoteName('user') . ' INT NULL');
		$this->db->execute();

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTING_GO_DETAILS', 'Ce projet m\'intéresse');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTING_GO_DETAILS', 'This project interests me', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_HEART', 'Mon coup de coeur');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_HEART', 'My favourite', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE', 'Je donne mon coup de coeur !');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE', 'I give my favourite!', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_TEXT', '<p>Vous vous apprêtez à voter pour ce projet.</p><p>Êtes-vous sûre de votre choix ? (une fois le coup de coeur attribué, vous ne pourrez plus le retirer)</p>');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_TEXT', '<p>You are about to vote for this project.</p><p>Are you sure of your choice? (once the "coup de coeur" has been awarded, you will not be able to withdraw it)</p>', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_YES', 'Oui, je vote !');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_YES', 'Yes, I vote!', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_GO_BACK', 'Retour aux projets');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_GO_BACK', 'Back to projects', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_HOME_LINK', '/projets-selectionnes-2');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_HOME_LINK', '/projets-selectionnes-2', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_NO', 'Non, retour aux projets');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_NO', 'No, back to projects', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_ERROR_PLEASE_COMPLETE_EMAIL', 'Veuillez saisir une adresse email afin de soumettre votre vote');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_ERROR_PLEASE_COMPLETE_EMAIL', 'Please enter an email address to submit your vote', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_ALREADY_VOTED', 'Coup de coeur attribué à ce projet !');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_ALREADY_VOTED', 'You have voted for this project!', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_ALREADY_VOTED_FOR_OTHER', 'Vous avez déjà donné votre coup de coeur');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_ALREADY_VOTED_FOR_OTHER', 'You\'ve already given us your favourite', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_SUCCESS_TITLE', 'Vote soumis !');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_SUCCESS_TITLE', 'Vote submitted!', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_SUCCESS_TEXT', 'Votre vote a bien été pris en compte ! Merci de votre participation.');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_SUCCESS_TEXT', 'Your vote has been taken into account! Thank you for your participation.', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_ERROR_TITLE', 'Une erreur est survenue');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_ERROR_TITLE', 'An error has occurred', 'override', 0, null, null, 'en-GB');

		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_ERROR_TEXT', 'Votre vote n\'as pas pu être pris en compte. Veuillez réessayer plus tard.');
		EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_VOTE_MODAL_ERROR_TEXT', 'Your vote could not be taken into account. Please try again later.', 'override', 0, null, null, 'en-GB');

		$result['status'] = true;

		return $result;
	}
}