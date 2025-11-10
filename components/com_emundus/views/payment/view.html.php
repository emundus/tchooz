<?php

/**
 * @package     Joomla
 * @subpackage  com_emunudus_onboard
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Automation\EventsDefinitions\onBeforeEmundusCartRenderDefinition;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Plugin\PluginHelper;

require_once(JPATH_ROOT . '/components/com_emundus/models/payment.php');

/**
 * eMundus Onboard Campaign View
 *
 * @since  0.0.1
 */
class EmundusViewPayment extends JViewLegacy
{

	public $hash = '';
	public $user = null;

	private ?EmundusModelPayment $model = null;
	public int $current_sms_template_id = 0;

	public ?CartEntity $cart;
	public ?CartRepository $cart_repository;

	public ?ProductRepository $product_repository;
	public ?PaymentStepEntity $payment_step;

	public int $item_id = 0;

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->model = new EmundusModelPayment();
		$this->user = $app->getIdentity();

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		$this->hash = EmundusHelperCache::getCurrentGitHash();
		$jinput = $app->input;
		$layout = $jinput->getString('layout', 'products');
		$payment_repository = new PaymentRepository();

		if ($layout === 'transactions') {
			$fnum = $jinput->getString('fnum', '');
			if ((!empty($fnum) && EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) || EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'r', $this->user->id)) {
				// ok
			} else {
				$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
				$app->redirect('/');
			}
		}
		else if ($layout === 'cart') {
			$fnum = $jinput->getString('fnum', '');

			if (!empty($fnum) && EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				// verify user has completed forms before
				if (!class_exists('EmundusModelApplication')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
				}
				$m_application = new EmundusModelApplication();
				$attachments_progress = $m_application->getAttachmentsProgress($fnum);
				$forms_progress = $m_application->getFormsProgress($fnum);

				if ($attachments_progress < 100 || $forms_progress < 100) {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_CART_COMPLETE_FORMS_BEFORE_PAYMENT'), 'error');
					$app->redirect('/index.php?option=com_emundus&task=openfile&fnum=' . $fnum);
				}

				if (!class_exists('EmundusModelWorkflow')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
				}
				$m_workflow = new EmundusModelWorkflow();
				$step = $m_workflow->getPaymentStepFromFnum($fnum);

				if (!empty($step->id)) {
					PluginHelper::importPlugin('emundus');
					$dispatcher         = Factory::getApplication()->getDispatcher();
					$onBeforeRenderCart = new GenericEvent(
						'onCallEventHandler',
						[
							'onBeforeEmundusCartRender',
							[
								'fnum' => $fnum,
								'context' => new EventContextEntity(
									$this->user,
									[$fnum],
									[$this->user->id],
									[onBeforeEmundusCartRenderDefinition::PAYMENT_STEP_KEY => $step->id]
								),
								'execution_context' => null
							],
						],
					);
					$dispatcher->dispatch('onCallEventHandler', $onBeforeRenderCart);

					$this->cart_repository = new CartRepository();
					$this->cart = $this->cart_repository->getCartByFnum($fnum, $step->id);

					if (!class_exists('EmundusModelProfile')) {
						require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
					}
					$m_profile = new EmundusModelProfile();

					$applicant_profiles = $m_profile->getApplicantsProfiles();
					$applicant_profile_ids = array_map(function ($profile) {
						return $profile->id;
					}, $applicant_profiles);

					$emundus_user = Factory::getApplication()->getSession()->get('emundusUser');
					if (in_array($emundus_user->profile, $applicant_profile_ids)) {
						$m_profile->initEmundusSession($fnum);
					}
				} else {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_CART_NOT_FOUND'), 'error');
					$app->redirect('/');
				}
			} else {
				$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
				$app->redirect('/');
			}
		}
		else if ($layout === 'productedit' || $layout === 'discountedit' || $layout === 'transactionedit') {
			if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'u', $this->user->id)) {
				$this->item_id = $jinput->getInt('id', 0);
			} else {
				$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
				$app->redirect('/');
			}
		} else {
			if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'r', $this->user->id)) {
				$this->product_repository = new ProductRepository();
			} else {
				$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
				$app->redirect('/');
			}
		}

		parent::display($tpl);
	}
}