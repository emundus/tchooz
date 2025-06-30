<?php
/**
 * @package        Joomla
 * @subpackage     eMundus
 * @link           http://www.emundus.fr
 * @copyright      Copyright (C) 2018 eMundus. All rights reserved.
 * @license        GNU/GPL
 * @author         Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');

/**
 * Emundus Component Comments Model
 *
 * @since  1.40.0
 */
class EmundusModelComments extends BaseDatabaseModel
{
	/**
	 * @var DatabaseDriver|null
	 * @since version 1.40.0
	 */
    private $db;

	/**
	 * @var EmundusModelLogs
	 * @since version 1.40.0
	 */
    private $logger;

	private HtmlSanitizerSingleton $sanitizer;

	/**
	 * Constructor
	 * 
	 * @param $config
	 *
	 * @throws Exception
	 */
    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->db = Factory::getContainer()->get('DatabaseDriver');
        $this->logger = new EmundusModelLogs();

		if (!class_exists('HtmlSanitizerSingleton')) {
		    require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
	    }
	    $this->sanitizer = HtmlSanitizerSingleton::getInstance();
    }

	/**
	 * Add a comment to a file
	 *
	 * @param         $file_id              int
	 * @param         $comment              string
	 * @param         $target               array|null (target_type, target_id)
	 * @param         $visible_to_applicant int (0|1)
	 * @param   int   $parent_id
	 * @param   null  $user                 int
	 *
	 * @return int
	 *
	 * @since version 1.40.0
	 */
    public function addComment($file_id, $comment, $target, $visible_to_applicant, $parent_id = 0, $user = null): int
    {
        $new_comment_id = 0;

        if (empty($user)) {
            $user = Factory::getApplication()->getIdentity()->id;
        }

	    $comment = $this->sanitizer->sanitizeFor('textarea', $comment);
        if (!empty($file_id) && !empty($comment)) {
            $allowed_targets = ['forms', 'groups', 'elements'];

            $target_type = !empty($target) && isset($target['type']) && in_array($target['type'], $allowed_targets) ? $target['type'] : '';
            $target_id = !empty($target) && isset($target['id']) ? $target['id'] : 0;

            $query = $this->db->getQuery(true);

            $query->select('fnum, applicant_id')
                ->from($this->db->quoteName('#__emundus_campaign_candidature'))
                ->where('id = ' . $this->db->quote($file_id));

            $this->db->setQuery($query);
            $file_infos = $this->db->loadAssoc();

            $query->clear()
                ->insert($this->db->quoteName('#__emundus_comments'))
                ->columns([
                    $this->db->quoteName('ccid'),
                    $this->db->quoteName('fnum'),
                    $this->db->quoteName('applicant_id'),
                    $this->db->quoteName('comment_body'),
                    $this->db->quoteName('target_type'),
                    $this->db->quoteName('target_id'),
                    $this->db->quoteName('visible_to_applicant'),
                    $this->db->quoteName('user_id'),
                    $this->db->quoteName('date'),
                    $this->db->quoteName('parent_id')
                ])
                ->values(
                    $this->db->quote($file_id) . ', ' .
                    $this->db->quote($file_infos['fnum']) . ', ' .
                    $this->db->quote($file_infos['applicant_id']) . ', ' .
                    $this->db->quote($comment) . ', ' .
                    $this->db->quote($target_type) . ', ' .
                    $this->db->quote($target_id) . ', ' .
                    $this->db->quote($visible_to_applicant) . ', ' .
                    $this->db->quote($user) . ', ' .
                    $this->db->quote(date('Y-m-d H:i:s')) . ', ' .
                    $this->db->quote($parent_id)
                );

            try {
                $this->db->setQuery($query);
                $inserted = $this->db->execute();

                if ($inserted) {
                    $new_comment_id = $this->db->insertid();

                    $params = ['details' => $comment];
                    $this->logger->log($user, $file_infos['applicant_id'], $file_infos['fnum'], 10, 'c', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_CREATE', json_encode($params));
                    $this->dispatchEmundusEvent('onAfterCommentAdd', ['comment_id' => $new_comment_id, 'comment' => [
                        'applicant_id' => $file_infos['applicant_id'],
                        'user_id' => $user,
                        'comment_body' => $comment,
                        'fnum' => $file_infos['fnum']
                    ]]);
                }
            } catch (Exception $e) {
                Log::add('Failed to add comment ' . $e->getMessage(), Log::ERROR, 'com_emundus.comments');
            }
        }

        return $new_comment_id;
    }

    /**
     * Delete a comment
     * 
     * @param $comment_id
     * @param $user
     * @return bool
     *             
     * @since version 1.40.0            
     */
    public function deleteComment($comment_id, $user): bool
    {
        $deleted = false;

        if (!empty($comment_id) && !empty($user)) {
            $query = $this->db->getQuery(true);

            $query->select('ecc.fnum, ecc.applicant_id, ec.comment_body')
                ->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
                ->leftJoin($this->db->quoteName('#__emundus_comments', 'ec') . ' ON ecc.fnum = ec.fnum')
                ->where('ec.id = ' . $this->db->quote($comment_id));

            $this->db->setQuery($query);
            $file_infos = $this->db->loadAssoc();

            $query->clear()
                ->delete($this->db->quoteName('#__emundus_comments'))
                ->where('id = ' . $this->db->quote($comment_id))
                ->orWhere('parent_id = ' . $this->db->quote($comment_id));

            try {
                $this->db->setQuery($query);
                $deleted = $this->db->execute();
            } catch (Exception $e) {
                Log::add('Failed to delete comment ' . $e->getMessage(), Log::ERROR, 'com_emundus.comments');
            }

            if ($deleted) {
                $params = ['comment_id' => $comment_id, 'details' => $file_infos['comment_body']];
                $this->logger->log($user, $file_infos['applicant_id'], $file_infos['fnum'], 10, 'd', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_DELETE', json_encode($params));
                $this->dispatchEmundusEvent('onAfterCommentDeleted', ['comment_id' => $comment_id, 'user_id' => $user]);
            }
        }

        return $deleted;
    }

	/**
	 * Update a comment
	 *
	 * @param $comment_id
	 * @param $comment
	 * @param $user
	 *
	 * @return bool
	 *
	 * @since version 1.40.0
	 */
    public function updateComment($comment_id, $comment, $user): bool
    {
        $updated = false;

        if (!empty($comment_id) && !empty($comment) && !empty($user)) {
            $query = $this->db->getQuery(true);

            $query->select('ecc.fnum, ecc.applicant_id, ec.comment_body')
                ->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
                ->leftJoin($this->db->quoteName('#__emundus_comments', 'ec') . ' ON ecc.fnum = ec.fnum')
                ->where('ec.id = ' . $this->db->quote($comment_id));

            $this->db->setQuery($query);
            $file_infos = $this->db->loadAssoc();

            $query->clear()
                ->update($this->db->quoteName('#__emundus_comments'))
                ->set($this->db->quoteName('comment_body') . ' = ' . $this->db->quote($comment))
                ->set($this->db->quoteName('updated') . ' = NOW()')
                ->set($this->db->quoteName('updated_by') . ' = ' . $this->db->quote($user))
                ->where('id = ' . $this->db->quote($comment_id));

            try {
                $this->db->setQuery($query);
                $updated = $this->db->execute();
            } catch (Exception $e) {
                Log::add('Failed to update comment ' . $e->getMessage(), Log::ERROR, 'com_emundus.comments');
            }

            if ($updated) {
                $params = ['new_comment' => $comment, 'old_comment' => $file_infos['comment_body']];
                $this->logger->log($user, $file_infos['applicant_id'], $file_infos['fnum'], 10, 'u', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_UPDATE', json_encode($params));
                $this->dispatchEmundusEvent('onAfterCommentUpdate', ['comment_id' => $comment_id]);
            }
        }

        return $updated;
    }

	/**
	 * Open/Closed a comment
	 *
	 * @param $comment_id
	 * @param $opened
	 * @param $user
	 *
	 * @return bool
	 *
	 * @since version 1.40.0
	 */
    public function updateCommentOpenedState($comment_id, $opened, $user): bool
    {
        $updated = false;

        if (!empty($comment_id)) {
            $query = $this->db->getQuery(true);
            $query->update($this->db->quoteName('#__emundus_comments'))
                ->set($this->db->quoteName('opened') . ' = ' . $this->db->quote($opened))
                ->set($this->db->quoteName('updated') . ' = NOW()')
                ->set($this->db->quoteName('updated_by') . ' = ' . $this->db->quote($user))
                ->where('id = ' . $this->db->quote($comment_id));

            try {
                $this->db->setQuery($query);
                $updated = $this->db->execute();
            } catch (Exception $e) {
                Log::add('Failed to update comment opened state ' . $e->getMessage(), Log::ERROR, 'com_emundus.comments');
            }
        }

        return $updated;
    }

    /**
     * Get a comment
     * 
     * @param $comment_id
     * @return array
     * 
     * @since version 1.40.0             
     */
    public function getComment($comment_id): array
    {
        $comment = [];

        if (!empty($comment_id)) {
            $query = $this->db->getQuery(true);
            $query->select('ec.*, CONCAT(eu.firstname, " ", eu.lastname) as username')
                ->from($this->db->quoteName('#__emundus_comments', 'ec'))
	            ->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON eu.user_id = ec.user_id')
                ->where('ec.id = ' . $this->db->quote($comment_id));

            try {
                $this->db->setQuery($query);
                $comment = $this->db->loadAssoc();
			} catch (Exception $e) {
                Log::add('Failed to get comment ' . $e->getMessage(), Log::ERROR, 'com_emundus.comments');
            }
        }

        return $comment;
    }

	/**
	 * Get comments
	 *
	 * @param   int    $file_id
	 * @param          $current_user
	 * @param   bool   $is_applicant
	 * @param   array  $comment_ids
	 * @param   ?int   $parent_id
	 * @param   null   $opened
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
    public function getComments(int $file_id, $current_user, bool $is_applicant = false, array $comment_ids = [], $parent_id = null, $opened = null, ?int $limit = null, string $order_by = 'ec.id', string $order = 'DESC'): array
    {
        $comments = [];

        if (!empty($file_id) && !empty($current_user)) {
            $query = $this->db->getQuery(true);
            $query->select('ec.*')
                ->from($this->db->quoteName('#__emundus_comments', 'ec'))
                ->where($this->db->quoteName('ec.ccid') . ' = ' . $this->db->quote($file_id));

            if ($is_applicant) {
                $query->andWhere($this->db->quoteName('ec.visible_to_applicant') . ' = 1');
            }

            if (!empty($comment_ids)) {
                $query->andWhere($this->db->quoteName('ec.id' ) . ' IN (' . implode(',', $comment_ids) . ')');
            }

            if ($parent_id !== null) {
                $query->andWhere($this->db->quoteName('ec.parent_id') . ' = ' . $this->db->quote($parent_id));
            }

            if (isset($opened)) {
                $query->andWhere($this->db->quoteName('ec.opened') . ' = ' . $this->db->quote($opened));
            }

            try {
				if (!empty($limit)) {
					$query->setLimit($limit);
				}

				$query->order($this->db->quoteName($order_by) . ' ' . $order);

                $this->db->setQuery($query);
                $comments = $this->db->loadAssocList();
            } catch (Exception $e) {
                Log::add('Failed to get comments ' . $e->getMessage(), Log::ERROR, 'com_emundus.comments');
            }

            if (!empty($comments)) {
                $users = [];
                $user_ids = array_column($comments, 'user_id');
                $user_ids = array_unique($user_ids);

                $query->clear()
                    ->select('u.id, eu.firstname, eu.lastname, CONCAT(eu.firstname, " ", eu.lastname) as name, eu.profile_picture')
                    ->from($this->db->quoteName('#__users', 'u'))
                    ->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON eu.user_id = u.id')
                    ->where($this->db->quoteName('u.id' ) . ' IN (' . implode(',', $user_ids) . ')');

                try {
                    $this->db->setQuery($query);
                    $users = $this->db->loadAssocList('id');
                } catch (Exception $e) {
                    Log::add('Failed to get users ' . $e->getMessage(), Log::ERROR, 'com_emundus.comments');
                }

                foreach ($comments as $key => $comment) {
                    $comments[$key]['username'] = $users[$comment['user_id']]['name'];
                    $comments[$key]['profile_picture'] = $users[$comment['user_id']]['profile_picture'];
                    $comments[$key]['firstname'] = $users[$comment['user_id']]['firstname'];
                    $comments[$key]['lastname'] = $users[$comment['user_id']]['lastname'];
                    $comments[$key]['date_time'] = strtotime($comment['date']);
                    $comments[$key]['date'] = EmundusHelperDate::displayDate($comment['date'], 'DATE_FORMAT_LC2', 0);
                    $comments[$key]['updated'] = EmundusHelperDate::displayDate($comment['updated'], 'DATE_FORMAT_LC2', 0);
                }
            }
        }

        return $comments;
    }

	/**
	 * Get the target of a comment
	 * @param array $comment
	 * @return string
	 */
	public function getCommentTarget(array $comment): string
	{
		$target = '';

		if (!empty($comment) && !empty($comment['target_id']) && !empty($comment['target_type'])) {
			$query = $this->db->createQuery();

			$form_name = '';
			$group_name = '';
			$element_name = '';
			switch ($comment['target_type']) {
				case 'elements':
					$query->select('jfe.id as element_id, jfe.label as element_label, jfg.id as group_id, jfg.label as group_label, jff.id as form_id, jff.label as form_label')
						->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
						->leftJoin($this->db->quoteName('#__fabrik_groups', 'jfg') . ' ON jfe.group_id = jfg.id')
						->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
						->leftJoin($this->db->quoteName('#__fabrik_forms', 'jff') . ' ON jffg.form_id = jff.id')
						->where('jfe.id = ' . $this->db->quote($comment['target_id']));

					$this->db->setQuery($query);
					$element = $this->db->loadAssoc();

					if (!empty($element)) {
						$element_name = Text::_($element['element_label']);
						$group_name = Text::_($element['group_label']);
						$form_name = Text::_($element['form_label']);
					}
					break;
				case 'groups':
					$query->select('jfg.label as group_label, jff.label as form_label')
						->from($this->db->quoteName('#__fabrik_groups', 'jfg'))
						->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jfg.id = jffg.group_id')
						->leftJoin($this->db->quoteName('#__fabrik_forms', 'jff') . ' ON jffg.form_id = jff.id')
						->where('jfg.id = ' . $this->db->quote($comment['target_id']));

					$this->db->setQuery($query);
					$group = $this->db->loadAssoc();
					if (!empty($group)) {
						$group_name = Text::_($group['group_label']);
						$form_name = Text::_($group['form_label']);
					}
					break;
				case 'forms':
					$query->select('jff.label')
						->from($this->db->quoteName('#__fabrik_forms', 'jff'))
						->where('jff.id = ' . $this->db->quote($comment['target_id']));

					$this->db->setQuery($query);
					$form_name = Text::_($this->db->loadResult());
					break;
				default:
			}

			if (!empty($form_name)) {
				$target .= $form_name;
			}

			if (!empty($group_name)) {
				if (!empty($form_name))
				{
					$target .= ' - ';
				}

				$target .= $group_name;
			}

			if (!empty($element_name)) {
				if (!empty($group_name) || !empty($form_name)) {
					$target .= ' - ';
				}
				$target .= $element_name;
			}
		}

		return $target;
	}

	/**
	 * Get elements available for comments via an application file
	 *
	 * @param $ccid
	 *
	 * @return array
	 *
	 * @since version 1.40.0
	 */
    public function getTargetableElements($ccid): array
    {
        $data = [
            'forms' => [],
            'groups' => [],
            'elements' => []
        ];

        if (!empty($ccid)) {
            $query = $this->db->getQuery(true);
            $query->select('campaign_id')
                ->from($this->db->quoteName('#__emundus_campaign_candidature'))
                ->where('id = ' . $this->db->quote($ccid));

            $this->db->setQuery($query);
            $campaign_id = $this->db->loadResult();

            if (!empty($campaign_id)) {
                require_once(JPATH_ROOT . '/components/com_emundus/models/campaign.php');
                $m_campaign = new EmundusModelCampaign();
                $profile_ids = $m_campaign->getProfilesFromCampaignId([$campaign_id]);

                if (!empty($profile_ids)) {
                    require_once(JPATH_ROOT . '/components/com_emundus/models/form.php');
                    $m_form = new EmundusModelForm();
                    require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
                    $h_fabrik = new EmundusHelperFabrik();

                    foreach($profile_ids as $profile_id) {
                        $forms = $m_form->getFormsByProfileId($profile_id);
                        $data['forms'] = array_merge($data['forms'], $forms);

                        if (!empty($forms)) {
                            $form_ids = array_column($forms, 'id');

                            if (!empty($form_ids)) {
                                $data['groups'] = array_merge($data['groups'], $h_fabrik->getGroupsFromFabrikForms($form_ids));
                                $data['elements'] = array_merge($data['elements'], $h_fabrik->getElementsFromFabrikForms($form_ids));
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

	/**
	 * Dispatch an eMundus event
	 *
	 * @param $event
	 * @param $args
	 *
	 * @return void
	 * @throws Exception
	 *
	 * @since version 1.40.0
	 */
    protected function dispatchEmundusEvent($event, $args): void
    {
        PluginHelper::importPlugin('emundus');

		$app = Factory::getApplication();
		$app->triggerEvent($event, $args);
		$app->triggerEvent('onCallEventHandler', [$event, $args]);
    }
}
