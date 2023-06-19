<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2017. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

class ImportController extends BaseController
{

    function __construct( )
    {
        parent::__construct();
        $this->registerDefaultTask('show');
    }

    public function process(){
        // Set output format to raw
        $input = Factory::getApplication()->input;
        $data = $input->get('jform', null, 'array');

        $files = new Input($_FILES, array());
        $file = $files->get('jform', null, 'array');

        if (empty($file) or empty($file['name']['translationFile'])) {
            $this->setMessage(Text::_("COM_FALANG_IMPORT_FILE_MISSING"));
            $this->setRedirect( 'index.php?option=com_falang&task=import.show' );
            return false;
        }

        $model = $this->getModel('import', 'importModel');
        $model->process();
        $this->setRedirect( 'index.php?option=com_falang&task=import.show' );

    }

    function cancel()
    {
        $this->setRedirect( 'index.php?option=com_falang' );
    }

}