<?php
/* ======================================================
 # Web357 Framework for Joomla! - v2.0.0 (free version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Support: support@web357.com
 # Last modified: Monday 27 October 2025, 03:04:38 PM
 ========================================================= */

 
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldapikey extends FormField {
	
	protected $type = 'apikey';

	protected function getLabel()
	{
		return '<label id="jform_params_apikey-lbl" for="jform_params_apikey" class="hasTooltip" title="&lt;strong&gt;'.Text::_('W357FRM_APIKEY').'&lt;/strong&gt;&lt;br /&gt;'.Text::_('W357FRM_APIKEY_DESCRIPTION').'">'.Text::_('W357FRM_APIKEY').'</label>';	
	}

	protected function getInput()
	{
		$html = '';
		
		// Load the Web357 Framework language file
		Factory::getLanguage()->load('plg_system_web357framework', JPATH_PLUGINS . '/system/web357framework');

		// load js
		Text::script('W357FRM_SAVE_PLUGIN_SETTINGS');
		Factory::getDocument()->addScript(Uri::root(true).'/media/plg_system_web357framework/js/admin.min.js');

		// Translate placeholder text
		$hint = $this->translateHint ? Text::_($this->hint) : $this->hint;

		// Initialize some field attributes.
		$class        = !empty($this->class) ? ' class="' . $this->class . '"' : '';
		$disabled     = $this->disabled ? ' disabled' : '';
		$readonly     = $this->readonly ? ' readonly' : '';
		$columns      = $this->columns ? ' cols="' . $this->columns . '"' : '';
		$rows         = $this->rows ? ' rows="' . $this->rows . '"' : '';
		$required     = $this->required ? ' required aria-required="true"' : '';
		$hint         = $hint ? ' placeholder="' . $hint . '"' : '';
		$autocomplete = !$this->autocomplete ? ' autocomplete="off"' : ' autocomplete="' . $this->autocomplete . '"';
		$autocomplete = $autocomplete == ' autocomplete="on"' ? '' : $autocomplete;
		$autofocus    = $this->autofocus ? ' autofocus' : '';
		$spellcheck   = $this->spellcheck ? '' : ' spellcheck="false"';

		// Initialize JavaScript field attributes.
		$onchange = $this->onchange ? ' onchange="' . $this->onchange . '"' : '';
		$onclick = $this->onclick ? ' onclick="' . $this->onclick . '"' : '';
		
		// Default value
		$value = (!empty($this->value) && $this->value != '') ? $this->value : '';
		$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		
		// Including fallback code for HTML5 non supported browsers.
		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('script', 'system/html5fallback.min.js', ['version' => 'auto', 'relative' => true]);

		$html .= '<textarea name="' . $this->name . '" id="' . $this->id . '"' . $columns . $rows . $class
			. $hint . $disabled . $readonly . $onchange . $onclick . $required . $autocomplete . $autofocus . $spellcheck . '>'
			. $value . '</textarea>';

		// get domain
		$domain = $_SERVER['HTTP_HOST'];
		$html .= '<input type="hidden" name="jform[params][domain]" id="jform_params_domain" value="'.$domain.'" />';

		// loading icon
		$html .= '<div id="apikey-container">';
		$html .= '<div class="web357-loading-gif text-center" style="display:none"></div>';

		if (!empty($value))
		{
			// Get
			$url = 'https://app.web357.com/wp-json/web357-api-key/v1/status/'.$value;
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url ); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
			$resp = curl_exec($ch); 

			if (curl_errno($ch)) 
			{ 
				$curl_error_message = curl_error($ch); 
			} 

			curl_close($ch);

			$show_active_key_button = true;
			if ($resp === FALSE) 
			{
				$html .= '<div style="margin: 20px 0;" id="w357-activated-successfully-msg" class="alert alert-danger"><span class="icon-cancel"></span> '.Text::_('Call with web357.com has been failed with the error message "'. $curl_error_message .'". Please, try again later or contact us at support@web357.com.').'</div>';
			} 
			else 
			{
				$resp = json_decode($resp);
				if (isset($resp->status) && ($resp->status == 1 || $resp->status == 'old_api_key'))
				{
					$html .= '<div style="margin: 20px 0;" id="w357-activated-successfully-msg" class="alert alert-success"><span class="icon-save"></span> '.sprintf(Text::_('W357FRM_APIKEY_ACTIVATED_AND_VALIDATED'), $value).'</div>';
					$show_active_key_button = false;
				}
				elseif (isset($resp->status) && $resp->status == 0)
				{
					$html .= '<div style="margin: 20px 0;" id="w357-activated-successfully-msg" class="alert alert-danger"><span class="icon-cancel"></span> '.sprintf(Text::_('W357FRM_APIKEY_NOT_ACTIVATED'), $value).'</div>';
				}
				
				elseif (isset($resp->code) && ($resp->code == 'error' && !empty($resp->message)))
				{
					$show_active_key_button = false;

					switch ($resp->message)
					{
						case 'WP_ERROR_INVALID_API_KEY':
							$html .= '<div style="margin: 20px 0;" id="w357-activated-successfully-msg" class="alert alert-danger"><span class="icon-cancel"></span> '.
							sprintf(Text::_('W357FRM_APIKEY_INVALID'), $value).'</div>';
							break;
						case 'WP_ERROR_INACTIVE_SUBSCRIPTION':
							$html .= '<div style="margin: 20px 0;" id="w357-activated-successfully-msg" class="alert alert-danger"><span class="icon-cancel"></span> '.Text::_('W357FRM_INACTIVE_SUBSCRIPTION').'</div>';
							break;
						default:
							$html .= '<div style="margin: 20px 0;" id="w357-activated-successfully-msg" class="alert alert-danger"><span class="icon-cancel"></span> '.Text::_($resp->message).'</div>';
							break;
					}
				}
				else
				{
					$html .= '<div style="margin: 20px 0;" id="w357-activated-successfully-msg" class="alert alert-danger"><span class="icon-cancel"></span> '.Text::_('W357FRM_CALL_WITH_WEB357_LICENSE_MANAGER_FAILED').'</div>';
				}
			}

			// show the button only if is not activated
			if ($show_active_key_button)
			{
				$html .= '<p class="web357_apikey_activation_html"></p>';
				$html .= '<p><a class="btn btn-success web357-activate-api-key-btn" data-apikey="'.$value.'" data-domain="'.$domain.'" data-><strong>'.Text::_('W357FRM_ACTIVATE_API_KEY').'</strong></a></p>';
			}
			
		}

		$html .= '</div>'; // #apikey-container

		return $html;		
	}
}