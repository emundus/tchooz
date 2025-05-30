<?php
/**
 * @version 1: ExceliaPrice 2019-10-30 James Dean
 * @author  James Dean
 * @package Hikashop
 * @copyright Copyright (C) 2018 emundus.fr. All r
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have
 * to the GNU General Public License, and as distr
 * is derivative of works licensed under the GNU G
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and det
 * @description Sets the price to 0 depending if the excelia user exists
 */
// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');


class PlgHikashopEmundus_hikashop extends CMSPlugin {

    function __construct(&$subject, $config) {
        jimport('joomla.log.log');
        Log::addLogger(array('text_file' => 'com_emundus.emundus_hikashop_plugin.php'), Log::ALL, array('com_emundus'));
        parent::__construct($subject, $config);
    }

    public function onBeforeOrderCreate(&$order,&$do)
    {
        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onHikashopBeforeOrderCreate', ['order' => $order, 'do' => $do]]);
    }

    public function onAfterOrderCreate(&$order)
    {
		$app = Factory::getApplication();
	    PluginHelper::importPlugin('emundus','custom_event_handler');
	    $app->triggerEvent('onCallEventHandler', ['onHikashopAfterOrderCreate', ['order' => $order]]);

        // We get the emundus payment type from the config
        $eMConfig = ComponentHelper::getParams('com_emundus');
        $em_application_payment = $eMConfig->get('application_payment', 'user');

        $session = $app->getSession()->get('emundusUser');
	    $order_id = $order->order_parent_id ?: $order->order_id;

		// find the fnum related to current order (it isn't always the same as the session)
	    $db = Factory::getContainer()->get('DatabaseDriver');
	    $query = $db->getQuery(true);
	    $query->clear()
		    ->select('order_id')
		    ->from($db->quoteName('#__hikashop_order'))
		    ->where($db->quoteName('order_id') . ' = ' . $order_id .  ' OR ' . $db->quoteName('order_parent_id') . ' = ' . $order_id);
	    $db->setQuery($query);
	    $orders = $db->loadColumn();
	    $orders = empty($orders) ? [$order_id] : $orders;

		$query->clear()
			->select('fnum')
			->from($db->quoteName('#__emundus_hikashop'))
			->where($db->quoteName('order_id') . ' IN (' . implode(',', $orders) . ')');
		$db->setQuery($query);
		$fnum = $db->loadResult();

		if (!empty($fnum)) {
			$user = $session->id;
			require_once (JPATH_SITE.'/components/com_emundus/models/files.php');
			$m_files = new EmundusModelFiles();
			$fnum_infos = $m_files->getFnumInfos($fnum);
			$cid = $fnum_infos['campaign_id'];
			$status = $fnum_infos['status'];
		} else if (!empty($session)) {
            $user = $session->id ?: $app->getIdentity()->id;
            $fnum = $session->fnum;
            $cid = $session->campaign_id;
            $status = $session->status;
        }
        else {
            Log::add('Could not get session on order ID nor fnum from order_id. -> '. $order_id, Log::ERROR, 'com_emundus');
            return false;
        }

        if ($eMConfig->get('hikashop_session')) {
	        $payment_session = $app->getSession()->get('emundusPayment', null);
	        if (empty($payment_session->fnum)) {
                $emundus_payment = new StdClass();
                $emundus_payment->user_id = $user;
                $emundus_payment->fnum = $fnum;
		        $app->getSession()->set('emundusPayment', $emundus_payment);
            }
        }

        $db = Factory::getContainer()->get('DatabaseDriver');
        $config = hikashop_config();
        $confirmed_statuses = explode(',', trim($config->get('invoice_order_statuses','confirmed,shipped'), ','));
        if (empty($confirmed_statuses)) {
            $confirmed_statuses = array('confirmed','shipped');
        }

	    $query->clear()
		    ->select('*')
		    ->from($db->quoteName('#__emundus_hikashop'));

		if(!empty($user)) {
			switch ($em_application_payment) {
				case 'campaign':
					$query->where($db->quoteName('order_id') . ' IN (' . implode(',', $orders) . ') OR (' . $db->quoteName('campaign_id') . ' = ' . $cid . ' AND ' . $db->quoteName('user') . ' = ' . $user . ' ) ');
					break;
				case 'fnum':
					$query->where($db->quoteName('order_id') . ' IN (' . implode(',', $orders) . ') OR ' . $db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));
					break;
				case 'status':
					$query->where($db->quoteName('order_id') . ' IN (' . implode(',', $orders) . ') OR (' . $db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum) . ' AND ' . $db->quoteName('status') . ' = ' . $status . ')');
					break;
				case 'user':
				default :
					$query->where($db->quoteName('order_id') . ' IN (' . implode(',', $orders) . ') OR ' . $db->quoteName('user') . ' = ' . $user);
					break;
			}
		} else {
			$query->where($db->quoteName('order_id') . ' IN (' . implode(',', $orders) . ')');
		}

        try {
            $db->setQuery($query);

            $em_hikas = $db->loadObjectList();
            $em_hika = $em_hikas[sizeof($em_hikas)-1];

            if(empty($em_hika)) {

                $columns = ['user', 'fnum', 'campaign_id', 'order_id', 'status'];
                $values = [$user, $db->quote($fnum), $cid, $order_id, $status];

                $query
                    ->clear()
                    ->insert($db->quoteName('#__emundus_hikashop'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));

                $db->setQuery($query);

	            Log::add('Inserting Order '. $order_id .' update -> '. preg_replace("/[\r\n]/"," ",$query->__toString()), Log::INFO, 'com_emundus');
            } else {
                Log::add('Updating Order '. $order_id .' update -> '. preg_replace("/[\r\n]/"," ",$query->__toString()), Log::INFO, 'com_emundus');

                $fields = array(
                    $db->quoteName('order_id') . ' = ' . $db->quote($order_id)
                );

                $update_conditions = array(
                    $db->quoteName('id') . ' = ' . $em_hika->id
                );

                // Prepare the insert query.
                $query
                    ->clear()
                    ->update($db->quoteName('#__emundus_hikashop'))
                    ->set($fields)
                    ->where($update_conditions);

                $db->setQuery($query);
            }

            $res = $db->execute();

            if ($res) {
                Log::add('Order '. $order_id .' update -> '. preg_replace("/[\r\n]/"," ",$query->__toString()), Log::INFO, 'com_emundus');
                return true;
            }
            return $res;

        } catch (Exception $exception) {
            Log::add('Error SQL -> '. preg_replace("/[\r\n]/"," ",$query->__toString()), Log::ERROR, 'com_emundus');
            return false;
        }
    }

    public function onBeforeOrderUpdate(&$order,&$do)
    {
        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onHikashopBeforeOrderUpdate', ['order' => $order, 'do' => $do]]);
    }

    public function onAfterOrderUpdate(&$order) {
		$app = Factory::getApplication();
        $db         = Factory::getContainer()->get('DatabaseDriver');

	    if(isset($order->order_parent_id)){
			$order_id = $order->order_parent_id;
		} elseif (isset($order->hikamarket)){
			if(isset($order->hikamarket->parent)){
				$order_id = $order->hikamarket->parent->order_id;
			} else {
				$order_id = $order->order_id;
			}
        } else {
			$order_id = $order->order_id;
		}

        if ($order_id > 0) {
            $query = 'SELECT * FROM #__emundus_hikashop WHERE order_id='.$order_id;
            $db->setQuery($query);

            try {
                $em_order = $db->loadObject();
                if(empty($em_order)){
                    $this->onAfterOrderCreate($order);
                    $query = 'SELECT * FROM #__emundus_hikashop WHERE order_id='.$order_id;
                    $db->setQuery($query);
                    $em_order = $db->loadObject();
                }
                $user = $em_order->user;
                $fnum = $em_order->fnum;
                $cid = $em_order->campaign_id;
                $status = $em_order->status;

            } catch (Exception $exception) {
                Log::add('Error SQL -> '. preg_replace("/[\r\n]/"," ",$query), Log::ERROR, 'com_emundus');
                return false;
            }
        }
        else {
            Log::add('Could not get user session on order ID. -> '. $order_id, Log::ERROR, 'com_emundus');
            return false;
        }

        $eMConfig = ComponentHelper::getParams('com_emundus');

        if($eMConfig->get('hikashop_session', 0)) {
            if (in_array($order->order_status, ['cancelled', 'confirmed', 'shipped'])) {
	            $app->getSession()->set('emundusPayment', null);
            }
        }

        $application_payment_status = explode(',', $eMConfig->get('application_payment_status'));
        $status_after_payment = explode(',', $eMConfig->get('status_after_payment'));

        // get the step of paiement
        $key = array_search($status, $application_payment_status);

        $config = hikashop_config();
        $confirmed_statuses = explode(',', trim($config->get('invoice_order_statuses','confirmed,shipped'), ','));

        if ($status_after_payment[$key] > 0 && in_array($order->order_status, $confirmed_statuses)) {
	        require_once (JPATH_SITE.'/components/com_emundus/models/files.php');
            $m_files = new EmundusModelFiles();

			if(!empty($fnum)) {
				$m_files->updateState($fnum, $status_after_payment[$key]);
				Log::add('Application file status updated to -> ' . $status_after_payment[$key]. ' after order confirmed', Log::INFO, 'com_emundus');
			}

            $query = $db->getQuery(true);
            $query->update('#__emundus_campaign_candidature')
                ->set('submitted = 1')
                ->where('fnum LIKE ' . $db->quote($fnum));

            try {
                $db->setQuery($query);
                $db->execute();
            } catch (Exception $e) {
                Log::add('Failed to update file submitted after payment ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
            }
        }
        else {
            $query = 'SELECT * FROM #__hikashop_order WHERE order_id='.$order_id;
            $db->setQuery($query);
            $hika_order = $db->loadObject();

            if (empty($hika_order->order_payment_method)){
                $user = $app->getSession()->get('emundusUser');
                require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

                $app->enqueueMessage( Text::_('THANK_YOU_FOR_PURCHASE') );

                $m_application 	= new EmundusModelApplication;
                $redirect = $m_application->getConfirmUrl();

                $app->redirect($redirect);
            }
        }

        PluginHelper::importPlugin('emundus','custom_event_handler');
	    $app->triggerEvent('onCallEventHandler', ['onHikashopAfterOrderUpdate', ['order' => $order, 'em_order' => $em_order]]);

        $this->onAfterOrderCreate($order);
    }

    public function onAfterOrderConfirm(&$order,&$methods,$method_id)
    {
        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onHikashopAfterOrderConfirm',
            ['order' => $order, 'methods' => $methods, 'method_id' => $method_id]
        ]);
    }

    public function onAfterOrderDelete($elements)
    {
        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onHikashopAfterOrderDelete', ['elements' => $elements]]);
    }


    public function onCheckoutWorkflowLoad(&$checkout_workflow, &$shop_closed, $cart_id)
    {
	    $app = Factory::getApplication();
	    $session = $app->getSession()->get('emundusUser');
	    PluginHelper::importPlugin('emundus', 'custom_event_handler');
	    $app->triggerEvent('onCallEventHandler', ['onHikashopCheckoutWorkflowLoad',
		    ['checkout_workflow' => $checkout_workflow, 'shop_closed' => $shop_closed, 'cart_id' => $cart_id, 'session' => $session, 'fnum' => $session->fnum]
	    ]);

	    $eMConfig = ComponentHelper::getParams('com_emundus');
	    if ($eMConfig->get('hikashop_session')) {
		    if (!empty($session) && !empty($session->fnum)) {
			    $itemId = $app->input->get('Itemid', null,'int');

			    $db = Factory::getContainer()->get('DatabaseDriver');
			    $query = $db->getQuery(true);

			    $query->select('menutype')
				    ->from('jos_menu')
				    ->where('id = ' . $itemId);

			    try {
				    $db->setQuery($query);
				    $menutype = $db->loadResult();

				    if (!empty($menutype)) {
					    require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
					    $m_profile = new EmundusModelProfile();
					    $current_profile = $m_profile->getProfileByFnum($session->fnum);

					    if (strpos($menutype, 'menu-profile') !== false && $menutype !== 'menu-profile'.$current_profile) {
						    Log::add('FNUM ' . $session->fnum  . ' tried to pay product of menu ' . $menutype . ' but its current profile is ' . $current_profile  , Log::WARNING, 'com_emundus.emundus_hikashop_plugin');
						    $app->enqueueMessage(Text::_('COM_EMUNDUS_WRONG_PRODUCT_FOR_CAMPAIGN'), 'warning');
						    $app->redirect('/');
					    } else {
						    // TODO: is correct product ??
					    }
				    }

			    } catch (Exception $e) {
				    Log::add('Failed to get menu type associated to user profile ' .  $e->getMessage(), Log::ERROR, 'com_emundus.emundus_hikashop_plugin');
			    }
		    }
	    }
    }

    public function onBeforeProductListingLoad(&$filters,&$order,&$parent, &$select, &$select2, &$a, &$b, &$on) {
        $app = Factory::getApplication();

        $isAdmin = $app->isClient('administrator');

        if(!$isAdmin) {
            PluginHelper::importPlugin('emundus','custom_event_handler');
	        $app->triggerEvent('onCallEventHandler', ['onHikashopBeforeProductListingLoad',
                ['filters' => $filters, 'order' => $order,'parent' => $parent, 'select' => $select, 'select2' => $select2, 'a' => $a, 'b' => $b, 'on' => $on]
            ]);

            // Nobody can see product list for the moment
            $app->redirect('/');
        }
    }

    public function onAfterCartProductsLoad(&$cart) {
	    $app = Factory::getApplication();
	    $user = $app->getSession()->get('emundusUser');

	    $params	= ComponentHelper::getParams('com_emundus');
        if ($params->get('hikashop_session')) {
            $payment_session = $app->getSession()->get('emundusPayment', null);

            if (empty($payment_session->fnum)) {
                $emundus_payment = new StdClass();
                $emundus_payment->user_id = $user->id;
                $emundus_payment->fnum = $user->fnum;

	            $app->getSession()->set('emundusPayment', $emundus_payment);
            }
        }

        PluginHelper::importPlugin('emundus','custom_event_handler');
	    $app->triggerEvent('onCallEventHandler', ['onHikashopAfterCartProductsLoad', ['cart' => &$cart, 'fnum' => $user->fnum, 'user' => $user->id]]);
    }

    public function onCheckoutStepList(&$list)
    {
        $list['emundus_return'] = array('name' => 'eMundus - Retour au dossier', 'params' => array('reset_session' => ['name' => JText::_('COM_EMUNDUS_RESET_SESSION_ON_QUIT'), 'type' => 'boolean', 'default' => 0]));
	    PluginHelper::importPlugin('emundus','custom_event_handler');
	    Factory::getApplication()->triggerEvent('onCallEventHandler', ['onHikashopCheckoutStepList', ['list' => &$list]]);
    }

    public function onCheckoutStepDisplay($layoutName, &$html, &$view, $pos = null, $options = null)
    {
	    $app = Factory::getApplication();
        if ($layoutName != 'emundus_return')
            return;

        $user = $app->getSession()->get('emundusUser');
        $layout = '<div><a id="go-back-button" data-fnum="'. $user->fnum . '" class="tw-btn-primary em-mt-16" style="width:fit-content;" href="' . Uri::base() . 'component/emundus/?task=openfile&fnum=' . $user->fnum . '"><span class="material-symbols-outlined">arrow_back</span><span class="em-ml-8">Retour</span></a></div>';

        if ($options['reset_session'] == 1) {
            $layout .= "<script>
                const formData = new FormData();
                const goBack = document.querySelector('#go-back-button');
                formData.append('fnum', goBack.getAttribute('data-fnum'));
                goBack.addEventListener('click', function (e) {
                  e.preventDefault();
                  fetch(window.location.hostname + '/index.php?option=com_emundus&controller=payment&task=resetpaymentsession').then(function(response) {window.location.href = goBack.getAttribute('href');});
                });
            </script>";
        }

        $html .= $layout;

	    PluginHelper::importPlugin('emundus','custom_event_handler');
	    $app->triggerEvent('onCallEventHandler', ['onHikashopCheckoutStepDisplay', ['layoutName' => $layoutName, 'html' => &$html]]);
    }

    public function onAfterCheckoutStep($controllerName, &$go_back, $original_go_back, &$controller) {
	    $app = Factory::getApplication();
        $params	= ComponentHelper::getParams('com_emundus');

        if ($params->get('hikashop_session')) {
            $session = $app->getSession()->get('emundusUser');
            require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'payment.php');
            $m_payment = new EmundusModelPayment();
            $m_payment->checkPaymentSession($session->fnum, 'onAfterCheckoutStep');
        }

        PluginHelper::importPlugin('emundus','custom_event_handler');
	    $app->triggerEvent('onCallEventHandler', ['onHikashopAfterCheckoutStep', ['controllerName' => $controllerName, 'go_back' => &$go_back, 'original_go_back' => $original_go_back, 'controller' => &$controller]]);
    }

	public function onHikashopAfterDisplayView($view)
	{
		if ($view->getName() === 'checkout' && $view->getLayout() === 'after_end') {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/menu.php');
			$homepage = EmundusHelperMenu::getHomepageLink();

			$app = Factory::getApplication();
			$app->redirect($homepage);
		}
	}
}
