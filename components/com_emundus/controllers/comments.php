<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';

/**
 * Emundus Comments Controller
 * @package     Emundus
 */
class EmundusControllerComments extends BaseController
{
	/**
	 * @var \Joomla\CMS\User\User
	 * @since version 1.40.0
	 */
    private $user;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.40.0
	 */
    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->user = $this->app->getIdentity();
    }

	/**
	 * Send JSON response.
	 *
	 * @param $response
	 *
	 *
	 * @since version 1.40.0
	 */
    private function sendJsonResponse($response)
    {
        if ($response['code'] === 403) {
            header('HTTP/1.1 403 Forbidden');
            echo $response['message'];
            exit;
        } else if ($response['code'] === 500) {
            header('HTTP/1.1 500 Internal Server Error');
            echo $response['message'];
            exit;
        }

        echo json_encode($response);
        exit;
    }

	/**
	 * Get comment for an application file
	 *
	 * @since version 1.40.0
	 */
    public function getcomments()
    {
        $response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
        $ccid = $this->app->input->getInt('ccid', 0);

        if (!empty($ccid)) {
            $response['code'] = 500;
            $fnum = EmundusHelperFiles::getFnumFromId($ccid);

            if (EmundusHelperAccess::asAccessAction(10, 'r', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
                $response['code'] = 200;
                $model = $this->getModel('comments');
                $response['data'] = $model->getComments($ccid, $this->user->id);
                $response['status'] = true;
            }
        }

        $this->sendJsonResponse($response);
    }

	/**
	 * Create comment for an application file
	 *
	 * @since version 1.40.0
	 */
    public function addcomment()
    {
        $response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
        $ccid = $this->app->input->getInt('ccid', 0);
        $comment = $this->app->input->getString('comment', '');

        if (!empty($ccid) && !empty($comment)) {
            $fnum = EmundusHelperFiles::getFnumFromId($ccid);

            if (EmundusHelperAccess::asAccessAction(10, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
                $response['code'] = 500;
                $target = $this->app->input->getString('target', '');
                $target = !empty($target) ? json_decode($target, true) : [];
                $visible_to_applicant = $this->app->input->getInt('visible_to_applicant', 0);
                $parent_id = $this->app->input->getInt('parent_id', 0);

                $model = $this->getModel('comments');
                $comment_id = $model->addComment($ccid, $comment, $target, $visible_to_applicant, $parent_id, $this->user->id);

                if (!empty($comment_id)) {
                    $response['code'] = 200;
                    $response['status'] = true;
                    $response['data'] = $model->getComments($ccid, $this->user->id, false, [$comment_id])[0];
                } else {
                    $response['message'] = Text::_('COM_EMUNDUS_ADD_COMMENT_FAILED');
                }
            }
        }

        $this->sendJsonResponse($response);
    }

	/**
	 * Update comment for an application file
	 *
	 * @since version 1.40.0
	 */
    public function updateComment() {
        $response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
        $comment_id = $this->app->input->getInt('comment_id', 0);

        if (!empty($comment_id)) {
            $model = $this->getModel('comments');
            $comment = $model->getComment($comment_id);
            if (!empty($comment)) {
                $fnum = EmundusHelperFiles::getFnumFromId($comment['ccid']);

                if ((EmundusHelperAccess::asAccessAction(10, 'u', $this->user->id, $fnum) || (EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) && $comment['user_id'] == $this->user->id)) {
                    $response['code'] = 500;
                    $new_comment = $this->app->input->getString('comment', '');

                    $response['status'] = $model->updateComment($comment_id, $new_comment, $this->user->id);
                    $response['code'] = $response['status'] ? 200 : 500;
                    $response['message'] = $response['status'] ? Text::_('COM_EMUNDUS_UPDATE_COMMENT_SUCCESS') : Text::_('COM_EMUNDUS_UPDATE_COMMENT_FAILED');
                }
            }
        }

        $this->sendJsonResponse($response);
    }

	/**
	 * Open/Close comment for an application file
	 *
	 * @since version 1.40.0
	 */
    public function updateCommentOpenedState()
    {
        $response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
        $comment_id = $this->app->input->getInt('comment_id', 0);

        if (!empty($comment_id)) {
            $model = $this->getModel('comments');
            $comment = $model->getComment($comment_id);
            if (!empty($comment)) {
                $fnum = EmundusHelperFiles::getFnumFromId($comment['ccid']);

                if (EmundusHelperAccess::asAccessAction(10, 'u', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
                    $response['code'] = 500;
                    $opened = $this->app->input->getInt('opened', 0);

                    $response['status'] = $model->updateCommentOpenedState($comment_id, $opened, $this->user->id);
                    $response['code'] = $response['status'] ? 200 : 500;
                    $response['message'] = $response['status'] ? Text::_('COM_EMUNDUS_UPDATE_COMMENT_OPENED_STATE_SUCCESS') : Text::_('COM_EMUNDUS_UPDATE_COMMENT_OPENED_STATE_FAILED');
                }
            }
        }

        $this->sendJsonResponse($response);
    }

	/**
	 * Delete comment for an application file
	 *
	 * @since version 1.40.0
	 */
    public function deletecomment()
    {
        $response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
        $comment_id = $this->app->input->getInt('comment_id', 0);

        if (!empty($comment_id)) {
            $model = $this->getModel('comments');
            $comment = $model->getComment($comment_id);

            if (!empty($comment)) {
                $fnum = EmundusHelperFiles::getFnumFromId($comment['ccid']);

                if (EmundusHelperAccess::asAccessAction(10, 'd', $this->user->id, $fnum) || (EmundusHelperAccess::isFnumMine($this->user->id, $fnum)  && $comment['user_id'] == $this->user->id)) {
                    $response['code'] = 500;
                    $model = $this->getModel('comments');
                    $response['status'] = $model->deleteComment($comment_id, $this->user->id);
                    $response['code'] = $response['status'] ? 200 : 500;
                }
            }
        }

        $this->sendJsonResponse($response);
    }

	/**
	 * Get elements that can be targeted by a comment
	 *
	 * @since version 1.40.0
	 */
    public function gettargetableelements()
    {
        $response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
        $ccid = $this->app->input->getInt('ccid', 0);

        if (!empty($ccid)) {
            $fnum = EmundusHelperFiles::getFnumFromId($ccid);

            if (EmundusHelperAccess::asAccessAction(10, 'r', $this->user->id, $fnum)) {
                $response['code'] = 200;
                $model = $this->getModel('comments');
                $response['data'] = $model->getTargetableElements($ccid);
                $response['status'] = true;
            }
        }

        $this->sendJsonResponse($response);
    }

	/**
	 * Get menu item for a form id attached to a comment
	 *
	 * @since version 1.40.0
	 */
    public function getMenuItemForFormId()
    {
        $response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
        $ccid = $this->app->input->getInt('ccid', 0);
        $form_id = $this->app->input->getInt('form_id', 0);

        if (!empty($ccid) && !empty($form_id)) {
            $fnum = EmundusHelperFiles::getFnumFromId($ccid);

            if (EmundusHelperAccess::asAccessAction(10, 'r', $this->user->id, $fnum)) {
                require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
                $m_profile = new EmundusModelProfile();

                $response['status'] = true;
                $response['code'] = 200;
                $response['message'] = '';
                $response['data'] = $m_profile->getMenuItemForFormId($form_id, $fnum);
            }
        }

        $this->sendJsonResponse($response);
    }
}