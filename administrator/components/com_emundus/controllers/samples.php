<?php
/**
 * Samples controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

require_once (JPATH_SITE.'/components/com_emundus/helpers/access.php');

class EmundusAdminControllerSamples extends JControllerLegacy
{
	protected $app;

    private $user;

	public function __construct($config = [], MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

        $this->user = $this->app->getIdentity();
	}

    function generate(){

        if(EmundusHelperAccess::asAdministratorAccessLevel($this->user->id)) {
            $datas = $this->input->getArray();

            include_once(JPATH_SITE.'/administrator/components/com_emundus/models/samples.php');
            $mSamples = new EmundusAdminModelSamples();

            if($datas['samples_programs']){
                $i = 0;
                $codes = [];
                while($i < $datas['samples_programs']) {
                    $codes[] = $mSamples->createSampleProgram('Programme ' . $i,'prog-' . $i);
                    $i++;
                }
            }

            if($datas['samples_campaigns']){
                $i = 0;
                $campaigns = [];
                while($i < $datas['samples_campaigns']) {
                    $campaigns[] = $mSamples->createSampleCampaign('Campagne ' . $i);
                    $i++;
                }
            }

            if($datas['samples_users']){
                $i = 0;
                $nb_files_created = 0;

                while($i < $datas['samples_users']) {
	                $j = 0;
                    $user = $mSamples->createSampleUser(9);
                    $i++;
                    if ($datas['samples_files']) {
                        while($j < $datas['samples_files']) {
                            $nb_files_created += $mSamples->createSampleFile($user->id);
                            $j++;
                        }
                    }
                }

                if ($datas['samples_files']) {
                    $this->app->enqueueMessage($nb_files_created . ' dossiers ont été créés.');
                }
            } elseif ($datas['samples_files']){
                $nb_files_created = 0;
                $j = 0;
                while($j < $datas['samples_files']) {
                    sleep(1);
                    $nb_files_created += $mSamples->createSampleFile();
                    $j++;
                }

                $this->app->enqueueMessage($nb_files_created . ' dossiers ont été créés.');
            }

            $this->app->redirect(JURI::base() . 'index.php?option=com_emundus&view=samples');
        }
    }
}
