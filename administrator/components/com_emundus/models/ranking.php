<?php
/**
 * eMundus Campaign model
 *
 * @package        Joomla
 * @subpackage    eMundus
 * @link        http://www.emundus.fr
 * @copyright    Copyright (C) 2008 - 2013 Décision Publique. All rights reserved.
 * @license        GNU/GPL
 * @author        Decision Publique - Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Factory;

class EmundusAdministrationModelRanking extends JModelList
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Install tables and add sysadmin default menu
     * @return bool
     */
    public function install($debug = false): bool
    {
        $installed = false;
        $tasks = [];

        require_once (JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');
        $app = $debug ? Factory::getApplication() : null;
        $db = Factory::getContainer()->get('DatabaseDriver');

        /**
         * Tables that must exists
         * jos_emundus_ranking_hierarchy
         * jos_emundus_ranking_hierarchy_view
         * jos_emundus_ranking
         */
        $columns = [
            [
                'name' => 'parent_id',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'label',
                'type' => 'VARCHAR',
                'length' => 255,
                'null' => 0,
            ],
            [
                'name' => 'published',
                'type' => 'TINYINT',
                'null' => 1,
                'default' => 1,
            ],
            [
                'name' => 'package_by',
                'type' => 'varchar',
                'length' => 255,
                'null' => 0,
                'default' => 'jos_emundus_setup_campaigns.id',
            ],
            [
                'name' => 'package_start_date_field',
                'type' => 'varchar',
                'length' => 255,
                'null' => 1,
                'default' => '',
            ],
            [
                'name' => 'package_end_date_field',
                'type' => 'varchar',
                'length' => 255,
                'null' => 1,
                'default' => '',
            ],
	        [
		        'name' => 'form_id',
		        'type' => 'int',
		        'null' => 1,
		        'default' => 0,
	        ]
        ];

        $response = EmundusHelperUpdate::createTable('jos_emundus_ranking_hierarchy', $columns);
		$tasks[] = $response['status'];

        if ($debug) {
            if ($response['status']) {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy exists or has been created');
            } else {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy not created ' .json_encode($response), 'error');
            }
        }

        $columns = [
            [
                'name' => 'hierarchy_id',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'profile_id',
                'type' => 'INT',
                'null' => 0,
            ]
        ];
        $foreign_keys = [
            [
                'name'           => 'jos_emundus_ranking_hierarchy_profiles_hierarchy_id_fk',
                'from_column'    => 'hierarchy_id',
                'ref_table'      => 'jos_emundus_ranking_hierarchy',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ],
            [
                'name'           => 'jos_emundus_ranking_hierarchy_profiles_profile_id_fk',
                'from_column'    => 'profile_id',
                'ref_table'      => 'jos_emundus_setup_profiles',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ],
        ];
        $unique_keys = [
            [
                'name' => 'jos_emundus_ranking_hierarchy_profiles_profile_uk',
                'columns' => ['profile_id'],
            ]
        ];

        $response = EmundusHelperUpdate::createTable('jos_emundus_ranking_hierarchy_profiles', $columns, $foreign_keys, 'Table de liaison entre les profils et les niveaux de hiérarchie', $unique_keys);
        $tasks[] = $response['status'];

        if ($debug) {
            if ($response['status']) {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy exists or has been created');
            } else {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy not created ' .json_encode($response), 'error');
            }
        }

        $columns = [
            [
                'name' => 'hierarchy_id',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'visible_hierarchy_id',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'ordering',
                'type' => 'INT',
                'null' => 1,
                'default' => 0,
            ]
        ];
        $foreign_keys = [
            [
                'name'           => 'jos_emundus_ranking_hierarchy_view_hierarchy_id_fk',
                'from_column'    => 'hierarchy_id',
                'ref_table'      => 'jos_emundus_ranking_hierarchy',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ],
            [
                'name'           => 'jos_emundus_ranking_hierarchy_view_visible_hierarchy_id_fk',
                'from_column'    => 'visible_hierarchy_id',
                'ref_table'      => 'jos_emundus_ranking_hierarchy',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ],
        ];

        $response = EmundusHelperUpdate::createTable('jos_emundus_ranking_hierarchy_view', $columns, $foreign_keys);
        $tasks[] = $response['status'];

        if ($debug) {
            if ($response['status']) {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy_view exists or has been created');
            } else {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy_view not created', 'error');
            }
        }

        $columns = [
            [
                'name' => 'user_id',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'ccid',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'rank',
                'type' => 'int',
                'null' => 0,
                'default' => -1,
            ],
            [
                'name' => 'hierarchy_id',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'package',
                'type' => 'INT',
                'null' => 1,
            ],
            [
                'name' => 'locked',
                'type' => 'TINYINT',
                'null' => 1,
                'default' => 0,
            ],
        ];
        $foreign_keys = [
            [
                'name'           => 'jos_emundus_classement_ccid__fk',
                'from_column'    => 'ccid',
                'ref_table'      => 'jos_emundus_campaign_candidature',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ],
            [
                'name'           => 'jos_emundus_ranking_jos_emundus_classement_hierarchy_id_fk',
                'from_column'    => 'hierarchy_id',
                'ref_table'      => 'jos_emundus_ranking_hierarchy',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ],
            [
                'name'           => 'jos_emundus_ranking_jos_users_id_fk',
                'from_column'    => 'user_id',
                'ref_table'      => 'jos_users',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ],
        ];
        $unique_keys = [
            [
                'name' => 'jos_emundus_classement_unicity_pk',
                'columns' => ['ccid', 'user_id', 'hierarchy_id', 'package'],
            ]
        ];

        $response = EmundusHelperUpdate::createTable('jos_emundus_ranking', $columns, $foreign_keys, 'Table de classement', $unique_keys);
        $tasks[] = $response['status'];

        if ($debug) {
            if ($response['status']) {
                $app->enqueueMessage('Table jos_emundus_ranking exists or has been created');
            } else {
                $app->enqueueMessage('Table jos_emundus_ranking not created', 'error');
            }
        }


        $columns = [
            [
                'name' => 'hierarchy_id',
                'type' => 'INT',
                'null' => 0,
            ],
            [
                'name' => 'status',
                'type' => 'INT',
                'null' => 0,
            ],
        ];
        $foreign_keys = [
            [
                'name'           => 'jos_emundus_ranking_hierarchy_visible_status_hierarchy_id_fk',
                'from_column'    => 'hierarchy_id',
                'ref_table'      => 'jos_emundus_ranking_hierarchy',
                'ref_column'     => 'id',
                'update_cascade' => true,
                'delete_cascade' => true,
            ]
        ];

        $response = EmundusHelperUpdate::createTable('jos_emundus_ranking_hierarchy_visible_status', $columns, $foreign_keys, 'Table des status apparaissant dans le tableau de classement pour un niveau de hiérarchie');
        $tasks[] = $response['status'];

        if ($debug) {
            if ($response['status']) {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy_visible_status exists or has been created');
            } else {
                $app->enqueueMessage('Table jos_emundus_ranking_hierarchy_visible_status not created', 'error');
            }
        }

		$columns = [
			['name' => 'hierarchy_id', 'type' => 'INT', 'null' => 0],
			['name' => 'status', 'type' => 'INT', 'null' => 0],
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_ranking_hierarchy_editable_status_hierarchy_id_fk',
				'from_column'    => 'hierarchy_id',
				'ref_table'      => 'jos_emundus_ranking_hierarchy',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true,
			]
		];

		$response = EmundusHelperUpdate::createTable('jos_emundus_ranking_hierarchy_editable_status', $columns, $foreign_keys, 'Table des status pour lesquels le rang est éditable pour un niveau de hiérarchie');
		$tasks[] = $response['status'];

		if ($debug) {
			if ($response['status']) {
				$app->enqueueMessage('Table jos_emundus_ranking_hierarchy_editable exists or has been created');
			} else {
				$app->enqueueMessage('Table jos_emundus_ranking_hierarchy_editable not created', 'error');
			}
		}

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->select('extension_id')
            ->from('#__extensions')
            ->where('type = ' . $db->quote('component'))
            ->where('element = ' . $db->quote('com_emundus'))
            ->where('enabled = 1')
            ->where('name = ' . $db->quote('com_emundus'));

        $db->setQuery($query);
        $component_id = $db->loadResult();

        $datas = [
            'menutype'     => 'coordinatormenu',
            'title'        => 'Classement',
            'alias'        => 'classement',
            'path'         => 'classement',
            'link'         => 'index.php?option=com_emundus&view=ranking',
            'type'         => 'component',
	        'access'       => 7,
            'component_id' => $component_id,
            'params'       => [
                'show_title' => 0,
                'menu_image_css' => 'format_list_numbered',
            ]
        ];
        $response = EmundusHelperUpdate::addJoomlaMenu($datas, 1, 0);
        $tasks[] = $response['status'];

        if ($debug) {
            if ($response['status']) {
                $app->enqueueMessage('Menu Classement exists or has been created');
            } else {
                $app->enqueueMessage('Menu Classement not created', 'error');
            }
        }

        $query = $db->getQuery(true);
        $query->select('id')
            ->from('#__emundus_setup_emails')
            ->where('lbl = ' . $db->quote('ask_lock_ranking'));

        $db->setQuery($query);
        $email_id = $db->loadResult();
        $email_insert = false;
        if (empty($email_id)) {
            $default_message = 'Bonjour [NAME], <br /><br /><p>Une demande de verrouillage du classement a été effectuée.</p> <br /><br />Cordialement,';

            $query = $db->getQuery(true);
            $query->insert('#__emundus_setup_emails')
                ->columns('lbl, subject, message, type, category')
                ->values($db->quote('ask_lock_ranking') . ', ' . $db->quote('Demande de verrouillage du classement') . ', ' . $db->quote($default_message) . ', 1, ' . $db->quote('Système'));

            try {
                $db->setQuery($query);
                $email_insert = $db->execute();
                $tasks[] = $email_insert;
            } catch (Exception $e) {
                $tasks[] = false;
            }
        }

        if ($debug) {
            if ($email_insert || !empty($email_id)) {
                $app->enqueueMessage('Email ask_lock_ranking exists or has been created');
            } else {
                $app->enqueueMessage('Email ask_lock_ranking not created', 'error');
            }
        }

        $query->clear()
            ->select('id')
            ->from('#__emundus_setup_emails')
            ->where('lbl = ' . $db->quote('ranking_locked'));
        $db->setQuery($query);
        $email_id = $db->loadResult();

        $email_insert = false;
        if (empty($email_id)) {
            $default_message = 'Bonjour [NAME], <br /><br /><p>[RANKER_NAME], du niveau [RANKER_HIERARCHY],  a verrouillé le classement des ses dossiers.</p> <br /><br />Cordialement,';

            $query = $db->getQuery(true);
            $query->insert('#__emundus_setup_emails')
                ->columns('lbl, subject, message, type, category, published')
                ->values($db->quote('ranking_locked') . ', ' . $db->quote('Classement verrouillé') . ', ' . $db->quote($default_message) . ', 1, ' . $db->quote('Système') . ', 0');

            try {
                $db->setQuery($query);
                $email_insert = $db->execute();
                $tasks[] = $email_insert;
            } catch (Exception $e) {
                $tasks[] = false;
            }
        }

        if ($debug) {
            if ($email_insert || !empty($email_id)) {
                $app->enqueueMessage('Email ranking_locked exists or has been created');
            } else {
                $app->enqueueMessage('Email ranking_locked not created', 'error');
            }
        }

        EmundusHelperUpdate::addCustomEvents([
            ['label' => 'onAfterUpdateFileRanking', 'category' => 'Classement'],
            ['label' => 'onGetFilesUserCanRank', 'category' => 'Classement'],
            ['label' => 'onBeforeExportRanking', 'category' => 'Classement']
        ]);

	    EmundusHelperUpdate::installExtension('PLG_FABRIK_FORM_EMUNDUSRANKINGFORMEND', 'emundusrankingformend','{"name":"PLG_FABRIK_FORM_EMUNDUSRANKINGFORMEND","type":"plugin","creationDate":"March 2025","author":"eMundus","copyright":"Copyright (C) 2017-2018 eMundus.fr - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"2.4.0","description":"PLG_FABRIK_FORM_EMUNDUSRANKINGFORMEND_DESCRIPTION","group":"","filename":"emundusrankingformend"}', 'plugin',1, 'fabrik_form', '{}', false, false);
		EmundusHelperUpdate::enableEmundusPlugins('emundusrankingformend', 'fabrik_form');

		$query->clear()
			->insert('#__emundus_setup_config')
			->columns('namekey, value')
			->values($db->quote('ranking') . ', ' . $db->quote(json_encode(['enabled' => 0, 'displayed' => 0, 'params' => []])));

		try {
			$db->setQuery($query);
			$db->execute();
			$tasks[] = true;
		} catch (Exception $e) {
			$tasks[] = false;
		}

        if (!in_array(false, $tasks)) {
            $installed = true;
        }

        return $installed;
    }
}