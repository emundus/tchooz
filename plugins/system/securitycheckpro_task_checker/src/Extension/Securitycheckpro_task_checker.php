<?php
/**
 * @Securitycheckpro_cron plugin
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
namespace Joomla\Plugin\System\Securitycheckpro_task_checker\Extension;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

final class Securitycheckpro_task_checker extends CMSPlugin implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onSCPTaskAdded'                  => 'onSCPTaskAdded',
        ];
    }
	
	// Lanzamos la tarea pendiente 
    private function launch_task($task_pending)
    {
		// Load library
		$model = new JsonModel();
		$model->execute($task_pending);
		
	}
	
	/**
     * Launch the tasks added
     *
     * @param   EventInterface  $event
     *
     * @return  boolean
     *
     * @since   4.0.0
     */
    public function onSCPTaskAdded(EventInterface $event)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = "SELECT storage_value FROM #__securitycheckpro_storage WHERE storage_key='remote_task'";
        $db->setQuery($query);
        $db->execute();
        $task_pending = $db->loadResult();
				
		if (!empty($task_pending))
		{
			$this->launch_task($task_pending);
		}		

        
    }

}