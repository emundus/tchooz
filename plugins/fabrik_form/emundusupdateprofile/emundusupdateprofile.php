<?php
// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

class PlgFabrik_FormEmundusupdateprofile extends plgFabrik_Form
{

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
	}

	public function onBeforeProcess(): void
	{
		$formModel = $this->getModel();

		$lastname  = strip_tags($formModel->formData['jos_emundus_users___lastname_raw']);
		$firstname = strip_tags($formModel->formData['jos_emundus_users___firstname_raw']);

		if (empty($lastname) || empty($firstname))
		{
			$this->app->enqueueMessage(Text::_('PROFILE_NOT_SAVED'), 'error');
			$this->app->redirect(Uri::base());
		}

		$formModel->updateFormData('jos_emundus_users___lastname', strtoupper($lastname), true);
		$formModel->updateFormData('jos_emundus_users___lastname_raw', strtoupper($lastname), true);

		$formModel->updateFormData('jos_emundus_users___firstname', ucfirst($firstname), true);
		$formModel->updateFormData('jos_emundus_users___firstname_raw', ucfirst($firstname), true);
	}

	public function onAfterProcess(): void
	{
		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.emundusupdateprofile.php'], Log::ALL, array('com_emundus.emundusupdateprofile'));

		$base_route = Uri::base();

		$menu      = $this->app->getMenu();
		$formModel = $this->getModel();
		$this->app->enqueueMessage(Text::_('PROFILE_SAVED'), 'info');

		$lastname  = strip_tags($formModel->formData['lastname_raw']);
		$firstname = strip_tags($formModel->formData['firstname_raw']);

		if (empty($lastname) || empty($firstname))
		{
			$this->app->enqueueMessage(Text::_('PROFILE_NOT_SAVED'), 'error');
			$this->app->redirect(Uri::base());
		}

		// Update the user's name
		$user = $this->app->getIdentity();
		$user->set('name', $firstname . ' ' . $lastname);
		$user->save();

		// Update emundusUser session
		$emundusUser            = $this->app->getSession()->get('emundusUser');
		$emundusUser->name      = $firstname . ' ' . $lastname;
		$emundusUser->lastname  = $lastname;
		$emundusUser->firstname = $firstname;
		$this->app->getSession()->set('emundusUser', $emundusUser);


		$alias = $this->getParams()->get('emundusupdateprofile_field_alias', '');

		if (empty($alias))
		{
			$item = $menu->getItems('link', 'index.php?option=com_fabrik&view=form&formid=' . $formModel->id, true);
			if (!empty($item))
			{
				$alias = $item->route;
			}
		}

		$current_lang = $this->app->getLanguage()->getTag();
		$default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		if ($current_lang != $default_lang)
		{
			$base_route = $base_route . substr($current_lang, 0, 2) . '/';
		}

		if (!empty($alias))
		{
			$this->app->redirect($base_route . $alias);
		}
	}
}
