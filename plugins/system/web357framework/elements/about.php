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

 
defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;

require_once(JPATH_PLUGINS . DIRECTORY_SEPARATOR . "system" . DIRECTORY_SEPARATOR . "web357framework" . DIRECTORY_SEPARATOR . "elements" . DIRECTORY_SEPARATOR . "elements_helper.php");

class JFormFieldabout extends FormField {
	
	protected $type = 'about';

	function getInput()
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			return $this->getInput_J4();
		}
		else
		{
			return $this->getInput_J3();
		}
	}

	function getLabel()
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			return $this->getLabel_J4();
		}
		else
		{
			return $this->getLabel_J3();
		}
	}

	protected function getLabel_J3()
	{	
		return $this->Web357AboutHtml();
	}
	
	protected function getInput_J3()
	{
		return ' ';
	}

	protected function getLabel_J4()
	{
		return ' ';
	}

	protected function getInput_J4()
	{
		return $this->Web357AboutHtml();
	}

	protected function Web357AboutHtml()
	{
		$html  = '<div class="web357framework-about-text">';

		$juri_base = str_replace('/administrator', '', Uri::base());

		// About
		$web357_link = 'https://www.web357.com?utm_source=CLIENT&utm_medium=CLIENT-AboutUsLink-web357&utm_content=CLIENT-AboutUsLink&utm_campaign=aboutelement';
		
		$html .= '<a href="'.$web357_link.'" target="_blank"><img src="'.$juri_base.'media/plg_system_web357framework/images/web357-logo-main.jpg" alt="Web357 logo" style="margin-bottom: 20px;" /></a>';

		$html .= "<p>Web357 develops professional extensions for <strong>Joomla!</strong> and plugins for <strong>WordPress</strong>, trusted by thousands of developers and website owners worldwide.</p><p>Since 2014, our mission has been to provide high-quality, easy-to-use, and reliable tools that simplify website management and enhance performance. From GDPR compliance solutions like the <strong>Cookies Policy Notification Bar</strong> to productivity tools such as <strong>Login as User</strong>, <strong>JLogs</strong>, and many more, our products cover a wide range of needs for both small businesses and large-scale projects.</p>

<p>With a strong focus on <strong>security, usability and performance</strong>, Web357 extensions are continuously updated to remain compatible with the latest CMS versions. Our commitment is not only to deliver powerful software but also to provide <strong>outstanding support</strong> and build long-term trust with our users.</p>

<p>Discover all our <strong>Joomla!</strong> extensions and <strong>WordPress</strong> plugins at <a href=\"".$web357_link."\" target=\"_blank\">web357.com</a>.</p>";
	
		$html .= '</div>'; // .web357framework-about-text
		
		// BEGIN: Social sharing buttons
		$html .= '<div class="web357framework-about-heading">Stay connected!</div>';
		
		$social_icons_dir_path = $juri_base.'media/plg_system_web357framework/images/social-icons';
		$social_sharing_buttons  = '<div class="web357framework-about-social-icons">'; // https://www.iconfinder.com/icons/252077/tweet_twitter_icon#size=32
				
		// facebook
		$social_sharing_buttons .= '<a href="//www.facebook.com/web357" target="_blank" title="Like us on Facebook"><img src="'.$social_icons_dir_path.'/facebook.png" alt="Facebook" /></a>';

		// instagram
		$social_sharing_buttons .= '<a href="//www.instagram.com/web357" target="_blank" title="Follow us on Instagram"><img src="'.$social_icons_dir_path.'/instagram.png" alt="Instagram" /></a>';

		// twitter
		$social_sharing_buttons .= '<a href="//x.com/web357" target="_blank" title="Follow us on Twitter"><img src="'.$social_icons_dir_path.'/twitter.png" alt="Twitter" /></a>';
	
		// jed
		$social_sharing_buttons .= '<a href="https://extensions.joomla.org/profile/profile/details/12368/" target="_blank" title="Find our extensions on Joomla! Extensions Directory"><img src="'.$social_icons_dir_path.'/jed.png" alt="JED" /></a>';
		
		$social_sharing_buttons .= '</div>'; // .web357framework-about-social-icons
		
		$html .= $social_sharing_buttons;
		// END: Social sharing buttons

		return $html;
	}
}