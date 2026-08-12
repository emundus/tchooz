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
use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Comments\CommentEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Comments\CommentRepository;
use Tchooz\Controller\EmundusController;


require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';

/**
 * Emundus Comments Controller
 * @package     Emundus
 */
class EmundusControllerComments extends EmundusController
{
	private int $allow_applicant_to_comment = 0;

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
	    $emundus_config = ComponentHelper::getParams('com_emundus');

		$this->allow_applicant_to_comment = (int)$emundus_config->get('allow_applicant_to_comment', 0);
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

            if (($this->allow_applicant_to_comment && EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) || EmundusHelperAccess::asAccessAction(10, 'r', $this->user->id, $fnum)) {
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
                $menu_id = $m_profile->getMenuItemForFormId($form_id, $fnum);

				if (!empty($menu_id)) {
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->createQuery();

					$query->select('path')
						->from('#__menu')
						->where('id = ' . $menu_id);

					$db->setQuery($query);
					$response['data'] = $db->loadResult();
				} else {
					$response['message'] = Text::_('COM_EMUNDUS_GET_MENU_ITEM_FOR_FORM_ID_FAILED');
				}
            }
        }

        $this->sendJsonResponse($response);
    }

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::COMMENT_FILE, 'mode' => CrudEnum::READ],
	])]
	public function getCommentsByTarget(): EmundusResponse
	{
		$this->checkToken('get');
		$response = EmundusResponse::fail(Text::_('NOT_FOUND'), 404);

		$targetType = $this->app->getInput()->getString('targetType', '');
		$targetId = $this->app->getInput()->getInt('targetId', 0);
		$targetType = CommentTargetTypeEnum::tryFrom($targetType);

		if ($targetType !== null && !empty($targetId))
		{
			$commentRepository = new CommentRepository();
			$comments = $commentRepository->getCommentsByTarget($targetId, $targetType, $this->user->id);
			$response = EmundusResponse::ok(array_map(function($comment) {
				$data = $comment->__serialize();
				$data['date'] = EmundusHelperDate::displayDate($data['date'], 'Y-m-d H:i', 0);
				if (!empty($data['updated']))
				{
					$data['updated'] = EmundusHelperDate::displayDate($data['updated'], 'Y-m-d H:i', 0);
				}

				return $data;
			}, $comments));
		}

		return $response;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::COMMENT_FILE, 'mode' => CrudEnum::CREATE],
		['id' => ActionEnum::COMMENT_FILE, 'mode' => CrudEnum::UPDATE],
	])]
	public function savecomment(): EmundusResponse
	{
		$this->checkToken();

		$id = $this->app->getInput()->getInt('id', 0);
		$targetType = $this->app->getInput()->getString('targetType', '') ?? null;
		$targetId = $this->app->getInput()->getInt('targetId', 0);
		$content = $this->app->getInput()->getString('content', '') ?? null;
		$isPublic = $this->app->getInput()->getInt('isPublic', 1) == 1;
		$parentId = $this->app->getInput()->getInt('parentId', 0);

		try
		{
			$commentRepository = new CommentRepository();

			if (!empty($parentId) || !empty($id))
			{
				$parentToCheck = null;

				if (!empty($parentId))
				{
					$parentToCheck = $commentRepository->getById($parentId);
				}
				elseif (!empty($id))
				{
					$existingComment = $commentRepository->getById($id);
					if ($existingComment && $existingComment->getParentId())
					{
						$parentToCheck = $commentRepository->getById($existingComment->getParentId());
					}
				}

				if ($parentToCheck && !$parentToCheck->isPublic())
				{
					$isPublic = false;
				}
			}

			if (!empty($id))
			{
				$comment = $commentRepository->getById($id);

				if (empty($comment))
				{
					throw new Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_NOT_FOUND'));
				}

				if ($comment->getCreatedBy() !== $this->user->id && !EmundusHelperAccess::asAccessAction(ActionEnum::COMMENT_FILE->value, CrudEnum::UPDATE->value, $this->user->id))
				{
					throw new Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_FORBIDDEN'));
				}

				$comment->setContent($content);
				$comment->setIsPublic($isPublic);
				$comment->setUpdatedAt(new DateTime());
				$comment->setUpdatedBy($this->user->id);
			}
			else
			{
				$comment = new CommentEntity(
					id: 0,
					targetType: $targetType,
					targetId: $targetId,
					content: $content,
					createdBy: $this->user->id,
					createdAt: new DateTime(),
					isPublic: $isPublic,
					parentId: $parentId
				);
			}


			if ($commentRepository->flush($comment))
			{
				$response = EmundusResponse::ok();
			}
			else
			{
				$response = EmundusResponse::fail(Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_SAVE_COMMENT_ERROR'));
			}
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail(Text::_($e->getMessage()));
		}

		return $response;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function removecomment(): EmundusResponse
	{
		$this->checkToken();
		$id = $this->app->getInput()->getInt('commentId', 0);

		try
		{
			$commentRepository = new CommentRepository();

			$comment = $commentRepository->getById($id);

			if (empty($comment))
			{
				throw new Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_NOT_FOUND'));
			}

			if ($comment->getCreatedBy() !== $this->user->id && !EmundusHelperAccess::asAccessAction(ActionEnum::COMMENT_FILE->value, CrudEnum::DELETE->value, $this->user->id))
			{
				throw new Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_FORBIDDEN'));
			}

			if ($commentRepository->delete($id))
			{
				$response = EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_REMOVE_COMMENT_SUCCESS'));
			} else
			{
				$response = EmundusResponse::fail(Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_REMOVE_COMMENT_ERROR'));
			}
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail(Text::_('COM_EMUNDUS_ONBOARD_CRC_COMMENT_REMOVE_COMMENT_ERROR'));
		}

		return $response;
	}
}