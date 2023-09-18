<?php
/**
 * Dropfiles
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Dropfiles
 * @copyright Copyright (C) 2013 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2013 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') || die;

jimport('joomla.form.formfield');

/**
 * Form Field class for the Joomla Framework.
 */
class JFormFieldOneDriveBusinessbtn extends JFormField
{
    /**
     * Type
     *
     * @var string
     */
    protected $type = 'OneDriveBusinessbtn';

    /**
     * Get label
     *
     * @return string
     */
    protected function getLabel()
    {
        return '';
    }

    /**
     * Add Connection|Disconnect OneDrive Business button
     *
     * @return string
     */
    protected function getInput()
    {

        $pathDropfilesOneDriveBusiness = JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesOneDriveBusiness.php';
        JLoader::register('DropfilesOneDriveBusiness', $pathDropfilesOneDriveBusiness);
        $params = JComponentHelper::getParams('com_dropfiles');
        $onedriveBusinessObj = new DropfilesOneDriveBusiness();

        ob_start();
        echo '<style>
            .btn-onedrivebusiness {
                background: #1d6cb0 none repeat scroll 0 0;
                border: medium none;
                border-radius: 2px;
                box-shadow: none;
                height: auto;
                padding: 5px 20px;
                text-shadow: none;
                width: auto;
                color: #fff;
            }
            .onedrivebusiness_node_head > h3 {
                font-weight: bold;
                padding: 8px 0 7px 15px;
                background-color: #23282D;
                border-color: #bce8f1;
                color: #eee;
                font-size: 13px;
            }
            #dropfiles-btnpush-onedrive-business span[class^=icon] {
                background-color: transparent;
                border-right: 1px solid #ffffff;
                height: auto;
                line-height: inherit;
                margin: 0;
                opacity: 1;
                text-shadow: none;
                padding: 0;
                z-index: -1;
            }
            .dropfiles_loading {
                animation-name: spin;
                animation-duration: 5000ms;
                animation-iteration-count: infinite;
                animation-timing-function: linear; 
            }
            @keyframes dropfiles_spin {
                from {
                    transform:rotate(0deg);
                }
                to {
                    transform:rotate(360deg);
                }
            }
            </style>';
        ?>
        <?php if ($onedriveBusinessObj->hasOneDriveButton()) : ?>
            <?php if (!$onedriveBusinessObj->checkConnectOnedrive()) {
                $url = $onedriveBusinessObj->getAuthorisationUrl();

                ?>
                <a id="dropfiles-onedrive-business-connect" class="btn btn-primary btn-onedrivebusiness" href="#"
                   onclick="window.open('<?php echo $url; ?>','foo','width=600,height=600');return false;"><img
                        src="<?php echo JURI::root(); ?>/components/com_dropfiles/assets/images/onedrive_white.png"
                        alt="" width="20px"/>
                    <span class="btn-title"><?php echo JText::_('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECT_PART2_CONNECT'); ?> </span>
                </a>
            <?php } else { ?>
                <?php echo JText::_('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECT_PART3'); ?>
                <?php $onedriveBusinessObj->getPushNotificationButton(); ?>
            <a id="dropfies_onedrive_business_disconnect" class="ju-button btn-onedrivebusiness" href="index.php?option=com_dropfiles&task=onedrivebusiness.logout">
                <img src="<?php echo JURI::root(); ?>/components/com_dropfiles/assets/images/onedrive-business-disconnect.svg" alt="" width="20px"/>
                <span class="btn-title"><?php echo JText::_('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECT_PART3_DISCONNECT'); ?></span>
            </a>
            <?php } ?>
        <?php endif; ?>
        <p><?php echo JText::_('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECT_PART2_FIRST'); ?></p>
        <?php
        JText::printf('COM_DROPFILES_ONEDRIVE_BUSINESS_CONNECT_PART1_2', JURI::root() . 'administrator/index.php?option=com_dropfiles&task=onedrivebusiness.authenticated');
        return ob_get_clean();
    }
}
