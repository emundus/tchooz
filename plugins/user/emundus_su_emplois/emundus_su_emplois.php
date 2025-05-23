<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2015 eMundus. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      eMundus - Benjamin Rivalland
 */

// No direct access
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
/**
 * Joomla User plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  User.emundus
 * @since       5.0.0
 */
class plgUserEmundus_su_emplois extends \Joomla\CMS\Plugin\CMSPlugin
{
    
    /**
     * This method should handle any login logic and report back to the subject
     *
     * @param   array   $user       Holds the user data
     * @param   array   $options    Array holding options (remember, autoregister, group)
     *
     * @return  boolean True on success
     * @since   1.5
     */
    public function onUserLogin($user, $options = array())
    {
        // Here you would do whatever you need for a login routine with the credentials
        // Remember, this is not the authentication routine as that is done separately.
        // The most common use of this routine would be logging the user into a third party application
        // In this example the boolean variable $success would be set to true if the login routine succeeds
        // ThirdPartyApp::loginUser($user['username'], $user['password']);
        $app            = JFactory::getApplication();
        include_once(JPATH_SITE.'/components/com_emundus/helpers/access.php');

        $isAdmin = JFactory::getApplication()->isClient('administrator');

        if (!$isAdmin) {
            $current_user = JFactory::getSession()->get('emundusUser');
            if (EmundusHelperAccess::isApplicant($current_user->id)) {
                if ($current_user->code == "utc-dfp-dri") {
                    $app->redirect("index.php?option=com_emundus&view=jobs&Itemid=1468");
                } 
            } else {
                $app->redirect("index.php");
            } 
        } else {
            $app->redirect("index.php");
        } 
    
        return true;
    }

    /**
     * This method should handle any logout logic and report back to the subject
     *
     * @param   array   $user       Holds the user data.
     * @param   array   $options    Array holding options (client, ...).
     *
     * @return  object  True on success
     * @since   1.5
     */
    public function onUserLogout($user, $options = array())
    {
        
        include_once(JPATH_SITE.'/components/com_emundus/models/profile.php');
        $app        = JFactory::getApplication();
        $profiles = new EmundusModelProfile;

        $campaign = $profiles->getCurrentCampaignInfoByApplicant($user['id']);

        $isAdmin = JFactory::getApplication()->isClient('administrator');

        if (!$isAdmin) {
            if ($campaign["training"] == "utc-dfp-dri") {
                $app->redirect("index.php?option=com_emundus&view=jobs&Itemid=1468");
            } else {
                $app->redirect("/");
            }
        } else {
            $app->redirect("/");
        }

        return true;
    }

}
