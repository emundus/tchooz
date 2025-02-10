<?php
/**
 * @version     $Id: emundus_period.php 10709 2016-04-07 09:58:52Z emundus.fr $
 * @package     Joomla
 * @copyright   Copyright (C) 2016 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * emundus_period candidature periode check
 *
 * @package     Joomla
 * @subpackage  System
 */
class plgSystemEmundus_block_user extends CMSPlugin
{
	/**
	 * @var    \Joomla\CMS\Application\CMSApplication
	 *
	 * @since  3.2
	 */
	protected $app;

	/**
	 * @var    \Joomla\Database\DatabaseDriver
	 *
	 * @since  3.2
	 */
	protected $db;

    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @since   1.0
     */
    function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    function onAfterInitialise() {
        include_once(JPATH_SITE.'/components/com_emundus/helpers/access.php');

        $user   =  $this->app->getSession()->get('emundusUser');
        $input = $this->app->input;
		$uri = JUri::getInstance();

        if (
			!$this->app->isClient('administrator') &&
			!empty($user->id) &&
			EmundusHelperAccess::isApplicant($user->id) &&
			($input->get('option', '') != 'com_emundus' && $input->get('view', '') != 'user') &&
			strpos($uri->toString(), 'logout') === false
        ) {
			$activationUri = $this->app->getUserState('users.login.activation.return');
			if (!empty($activationUri)) {
				$this->app->setUserState('users.login.activation.return', null);
				$this->app->redirect($activationUri);
			}

	        $table = JTable::getInstance('user', 'JTable');

	        $table->load($user->id);
	        $params = new JRegistry($table->params);

	        $token = $params->get('emailactivation_token');
	        $token = md5($token);
	        require_once(JPATH_ROOT . '/components/com_emundus/helpers/menu.php');
	        if (!empty($token) && strlen($token) === 32 && $this->app->input->getInt($token, 0, 'get') === 1 && $input->getInt('emailactivation',0) == 1) {
		        $table->activation = 1;
		        $params->set('emailactivation_token', null);
		        $table->params = $params->toString();

		        // save user data
		        if ($table->store()) {
			        $this->app->enqueueMessage(JText::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ACTIVATED'), 'success');

			        $redirect = EmundusHelperMenu::getHomepageLink($this->params->get('activation_redirect', 'index.php'));
			        if (!empty($redirect)) {
				        $this->app->redirect($redirect);
			        }
		        }
		        else {
			        throw new RuntimeException($table->getError());
		        }
	        }
	        elseif (($table->activation == 1 || $table->activation == 0) && $input->getInt('emailactivation',0) == 1) {
		        $this->app->enqueueMessage(JText::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ALREADY_ACTIVATED'), 'warning');

		        $redirect = EmundusHelperMenu::getHomepageLink($this->params->get('activation_redirect', 'index.php'));
		        if (!empty($redirect)) {
			        $this->app->redirect($redirect);
		        }
			}
	        else if ((int) $table->activation == -1 && strpos($uri->toString(), 'activation') === false) {
				$this->app->redirect('activation');
			}
        }
    }
}
