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
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;

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

	public int $product_id = 0;

	function display($tpl = null)
	{
		$payment_repository = new PaymentRepository();
		$app = Factory::getApplication();
		$this->model = new EmundusModelPayment();
		$this->user = $app->getIdentity();

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		$this->hash = EmundusHelperCache::getCurrentGitHash();
		$jinput = $app->input;
		$layout = $jinput->getString('layout', 'products');

		try
		{
			if ($layout === 'cart') {
				$fnum = $jinput->getString('fnum', '');

				$payment_repository = new PaymentRepository();
				if (!empty($fnum) && (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'r', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($fnum, $this->user->id))) {
					if (!class_exists('EmundusModelWorkflow')) {
						require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
					}
					$m_workflow = new EmundusModelWorkflow();
					$step = $m_workflow->getPaymentStepFromFnum($fnum);

					if (!empty($step->id)) {
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
					}
					else
					{
						$app->enqueueMessage(Text::_('COM_EMUNDUS_CART_NOT_FOUND'), 'error');
						$app->redirect('/');
					}
				}
				else
				{
					throw new \Exception(Text::_('ACCESS_DENIED'));
				}
			}
			else if ($layout === 'productedit')
			{
				$this->product_id = $jinput->getInt('id', 0);
			}
			else if ($layout === 'affectproducts')
			{
				if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'u', $this->user->id))
				{
					$fnums = $jinput->getString('fnums', '');
					if (!empty($fnums)) {
						$fnums = json_decode($fnums, true);
						$this->fnums = array_map(function ($file) {
							return $file['fnum'];
						}, $fnums);
					}

					if (empty($this->fnums)) {
						throw new \Exception(Text::_('COM_EMUNDUS_PAYMENT_ALTER_FILES_PRODUCTS_NO_DATA'));
					}

					$this->product_repository = new ProductRepository();
				}
				else
				{
					throw new \Exception(Text::_('ACCESS_DENIED'));
				}
			}
			else
			{
				if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'r', $this->user->id))
				{
					$this->product_repository = new ProductRepository();
				}
				else
				{
					throw new \Exception(Text::_('ACCESS_DENIED'));
				}
			}
		}
		catch (Exception $e)
		{
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/');
		}

		parent::display($tpl);
	}
}