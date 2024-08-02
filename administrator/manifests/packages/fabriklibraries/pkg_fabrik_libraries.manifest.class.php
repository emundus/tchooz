<?php
/**
 * Fabrik: Package Installer Manifest Class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @author      Henk
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
            private $app;

            public function __construct(AdministratorApplication $app)
            {
                $this->app = $app;
            }
            public function install(InstallerAdapter $parent): bool
            {
                return true;
            }

            public function update(InstallerAdapter $parent): bool
            {
                return true;
            }

            public function uninstall(InstallerAdapter $parent): bool
            {
                return true;
            }
			/**
			 * Run before installation or upgrade run
			 *
			 * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
			 *                         or install (standard install)
			 *                         or update (update)
			 * @param   object $parent installer object
			 *
			 * @return  void
			 */
			public function preflight(string $type, InstallerAdapter $parent): bool
			{
				/* Do not allow an installation if the base package is not installed */
				if ($type != 'uninstall') {
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->getQuery(true);
					$query->select("count(*)")->from("#__extensions")->where("type='package'")->where("element='pkg_fabrikbase'");
					if ($db->setQuery($query)->loadResult() == 0) {
						Factory::getApplication()->enqueueMessage('Fabrik Base package must be installed before Libraries package.', 'error');
						return false;
					}
				}

				return true;
			}

			public function postFlight(string $type, InstallerAdapter $parent) : bool {
				return true;
			}
		});
	}
};
