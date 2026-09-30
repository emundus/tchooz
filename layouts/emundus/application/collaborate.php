<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

$app = Factory::getApplication();
$app->getLanguage()->load('mod_emundus_applications', JPATH_SITE . '/modules/mod_emundus_applications');

$document = $app->getDocument();
$document->addScriptOptions('com_emundus.collaborate', ['userEmail' => $app->getIdentity()->email]);

$wa = $document->getWebAssetManager();
$wa->useScript('jquery');
$wa->registerAndUseScript('com_emundus_selectize', 'media/com_emundus/lib/selectize/dist/js/standalone/selectize.js', [], [], ['jquery']);
$wa->registerAndUseStyle('com_emundus_selectize', 'media/com_emundus/lib/selectize/dist/css/selectize.default.css');
if (!class_exists('EmundusHelperCache'))
{
	require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
}
$wa->registerAndUseScript('com_emundus.collaborate', 'media/com_emundus/js/collaborate.js', ['version' => EmundusHelperCache::getCurrentGitHash()], ['defer' => true], ['com_emundus_selectize']);

foreach ([
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_TITLE',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_VIEW_TITLE',
	'JCLOSE',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_SEND',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_BACK',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ADD_EMAILPLACEHOLDER',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ADD_EMAIL',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_NOT_YOUR_OWN',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_INVALID_EMAIL',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_FILL_EMAILS',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_EMAILS',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_SUCCESS',
	'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_FINISH_SUCCESS',
	'MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED',
	'COM_EMUNDUS_APPLICATION_SHARE_CONFIRM_DELETE',
	'JYES',
] as $key)
{
	Text::script($key);
}
