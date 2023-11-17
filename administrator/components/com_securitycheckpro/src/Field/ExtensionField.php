<?php
/**
 * @package    Joomla.Administrator
 * @subpackage com_userlogs
 *
 * @copyright Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE
 */
 
/* Based on /administrator/components/com_actionlogs/src/Field/ExtensionField.php */
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Plugin\System\Trackactions\Model\TrackActionsHelperModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Field to load a list of all users that have logged actions
 *
 * @since __DEPLOY_VERSION__
 */
class ExtensionField extends ListField
{
    /**
     * The form field type.
     *
     * @var   string
     * @since __DEPLOY_VERSION__
     */
    protected $type = 'extension';

    /**
     * Method to get the options to populate list
     *
     * @return array  The field option objects.
     *
     * @since __DEPLOY_VERSION__
     */
    public function getOptions()
    {
		
		$db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('DISTINCT b.extension')
            ->from($db->quoteName('#__securitycheckpro_trackactions', 'b'));

        $db->setQuery($query);
        $extensions = $db->loadObjectList();

        $options = [];
						
		foreach ($extensions as $extension)
        {			
            $text = TrackActionsHelperModel::translateExtensionName(strtoupper(strtok($extension->extension, '.')));
			$options[] = HTMLHelper::_('select.option', $extension->extension, $text);
        }

        return array_merge(parent::getOptions(), $options);
    }
}
