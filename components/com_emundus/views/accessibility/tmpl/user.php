<?php
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Tchooz\Factories\LayoutFactory;

Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_TITLE');
Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_DESCRIPTION');
Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_SETTINGS_SAVED_SUCCESSFULLY');
Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_SETTINGS_ERROR');

Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_MONO');
Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_HIGH_CONTRAST');
Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_HIGHLIGHT_LINK');
Text::script('COM_EMUNDUS_USERS_ACCESSIBILITY_INCREASE_FONT_SIZE');

$data = LayoutFactory::prepareVueData();

echo LayoutHelper::render('emundus.vue-mount', [
	'component' => 'Users/Accessibility',
	'data'      => $data,
]); ?>