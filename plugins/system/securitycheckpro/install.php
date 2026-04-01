<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Installer\InstallerAdapter;

class PlgSystemSecuritycheckproInstallerScript
{
    
    /**
     * Function to act prior to installation process begins
     *
     * @param   string     $action     Which action is happening (install|uninstall|discover_install|update)
     * @param   Installer  $installer  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   3.7.0
     */
    public function preflight($action, $installer)
    {
		// Chequeamos si hay extensiones dependientes del pllugin antes de desinstalarlo
		$canuninstall = true;
		
		if ($action === 'uninstall') {
			 // Si NO se está desinstalando el paquete, impedimos desinstalar este sub-elemento individualmente.
            if (!\defined('SCP_PKG_UNINSTALLING')) {
               
				/** @var DatabaseInterface $db */
				$db = Factory::getContainer()->get(DatabaseInterface::class);
				
				$type = 'package';
				$element = 'pkg_trackactions';

				$query = $db->getQuery(true)
					->select($db->quoteName('extension_id'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = :type')
					->where($db->quoteName('element') . ' = :element');

				$query->bind(':type', $type, ParameterType::STRING);
				$query->bind(':element', $element, ParameterType::STRING); 
				
				$db->setQuery($query);
				$extensionId = (int) $db->loadResult();

				if ($extensionId > 0) {
					Factory::getApplication()->enqueueMessage('You must uninstall the Track Action package before uninstalling Securitycheck Pro plugin','warning');
					$canuninstall = false;
				}  
				
				$type = 'plugin';
				$element = 'securitycheck_spam_protection';
				
				$query2 = $db->getQuery(true)
					->select($db->quoteName('extension_id'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = :type')
					->where($db->quoteName('element') . ' = :element');

				$query2->bind(':type', $type, ParameterType::STRING);
				$query2->bind(':element', $element, ParameterType::STRING); 
				
				$db->setQuery($query2);
				$extensionId = (int) $db->loadResult();

				if ($extensionId > 0) {
					Factory::getApplication()->enqueueMessage('You must uninstall the Spam protection plugin before uninstalling Securitycheck Pro plugin','warning');
					$canuninstall = false;
				}
			}
			
		}
		
		return $canuninstall;
        
    }
    
}
?>
