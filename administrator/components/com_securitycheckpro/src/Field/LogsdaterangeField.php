<?php
/**
 * @package    Joomla.Administrator
 * @subpackage COM_SECURITYCHECKPRO
 *
 * @copyright Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\PredefinedlistField;
use Joomla\CMS\Form\Form;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Field to show a list of range dates to sort with
 *
 * @since __DEPLOY_VERSION__
 */
class LogsdaterangeField extends PredefinedlistField
{
    /**
     * The form field type.
     *
     * @var   string
     * @since __DEPLOY_VERSION__
     */
    protected $type = 'logsdaterange';

    /**
     * Available options
     *
     * @var   array
     * @since __DEPLOY_VERSION__
     */
    protected $predefinedOptions = array(
    'today'       => 'COM_SECURITYCHECKPRO_OPTION_RANGE_TODAY',
    'past_week'   => 'COM_SECURITYCHECKPRO_OPTION_RANGE_PAST_WEEK',
    'past_1month' => 'COM_SECURITYCHECKPRO_OPTION_RANGE_PAST_1MONTH',
    'past_3month' => 'COM_SECURITYCHECKPRO_OPTION_RANGE_PAST_3MONTH',
    'past_6month' => 'COM_SECURITYCHECKPRO_OPTION_RANGE_PAST_6MONTH',
    'past_year'   => 'COM_SECURITYCHECKPRO_OPTION_RANGE_PAST_YEAR',
    'post_year'   => 'COM_SECURITYCHECKPRO_OPTION_RANGE_POST_YEAR',
    );

    /**
     * Method to instantiate the form field object.
     *
     * @param JForm $form The form to attach to the form field object.
     *
     * @since __DEPLOY_VERSION__
     */
    public function __construct($form = null)
    {
        parent::__construct($form);

        // Load the required language
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
    }
}
