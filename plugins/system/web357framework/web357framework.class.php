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
 
defined('_JEXEC') or die('Restricted access');
use Joomla\Utilities\IpHelper;
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

if (!class_exists('Web357FrameworkHelperClass')):
class Web357FrameworkHelperClass
{
	var $is_j25x = '';
	var $is_j3x = '';
	var $apikey = '';
	
	function __construct()
	{
		// Define the DS (DIRECTORY SEPARATOR)
		$this->defineDS();

		// Get Joomla's version
		$jversion = new Version;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8

		// get the Joomla! version
		if (!version_compare($mini_version, "2.5", "<=")) :
			// is Joomla! 3.x
			$this->is_j3x = true;
			$this->is_j25x = false;
		else:
			// is Joomla! 2.5.x
			$this->is_j3x = false;
			$this->is_j25x = true;
		endif;

		// get API Key from the plugin settings
		$this->apikey = $this->getAPIKey();
	}
	
	// Define the DS (DIRECTORY SEPARATOR)
	public static function defineDS()
	{
		if (!defined("DS")):
			define("DS", DIRECTORY_SEPARATOR);
		endif;
	}
	
	/**
	 * Get User's Browser (e.g. Google Chrome (64.0.3282.186))
	 * @param $user_agent null
	 * @return string
	 */
	public static function getBrowser()
	{ 
		$u_agent = $_SERVER['HTTP_USER_AGENT']; 
		$bname = 'Unknown';
		$ub = 'Unknown';
		$platform = 'Unknown';
		$version= "";
	
		//First get the platform?
		if (preg_match('/linux/i', $u_agent)) {
			$platform = 'linux';
		}
		elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		}
		elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}
	
		// Next get the name of the useragent yes seperately and for good reason
		if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
		{ 
			$bname = 'Internet Explorer'; 
			$ub = "MSIE"; 
		} 
		elseif(preg_match('/Trident/i',$u_agent)) 
		{ // this condition is for IE11
			$bname = 'Internet Explorer'; 
			$ub = "rv"; 
		} 
		elseif(preg_match('/OPR/i',$u_agent)) 
		{ 
			$bname = 'Opera'; 
			$ub = "OPR"; 
		} 
		elseif(preg_match('/Edg/i',$u_agent)) 
		{ 
			$bname = 'Edge'; 
			$ub = "Edg"; 
		} 
		elseif(preg_match('/Firefox/i',$u_agent)) 
		{ 
			$bname = 'Mozilla Firefox'; 
			$ub = "Firefox"; 
		} 
		elseif(preg_match('/Chrome/i',$u_agent)) 
		{ 
			$bname = 'Google Chrome'; 
			$ub = "Chrome"; 
		} 
		elseif(preg_match('/Safari/i',$u_agent)) 
		{ 
			$bname = 'Apple Safari'; 
			$ub = "Safari"; 
		} 
		elseif(preg_match('/Opera/i',$u_agent)) 
		{ 
			$bname = 'Opera'; 
			$ub = "Opera"; 
		} 
		elseif(preg_match('/Netscape/i',$u_agent)) 
		{ 
			$bname = 'Netscape'; 
			$ub = "Netscape"; 
		} 
		
		// finally get the correct version number
		// Added "|:"
		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) .
		 ')[/|: ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}
	
		// see how many we have
		$i = count($matches['browser']);
		if ($i != 1):
			//we will have two since we are not using 'other' argument yet
			//see if version is before or after the name
			if (strripos($u_agent,"Version") < strripos($u_agent,$ub)):
				$version= $matches['version'][0];
			else:
				if (isset($matches['version'][1])):
					$version = $matches['version'][1];
				elseif (isset($matches['version'][0])):
					$version= $matches['version'][0];
				else:
					$version = '';
				endif;
			endif;
		else:
			if (isset($matches['version'][0])):
				$version= $matches['version'][0];
			else:
				$version = '';
			endif;
		endif;

		// check if we have a number
		if ($version==null || $version=="") {$version="?";}
	
		$browser_details_arr = array(
			'userAgent' => $u_agent,
			'name'      => $bname,
			'version'   => $version,
			'platform'  => $platform,
			'pattern'    => $pattern
		);

		return $bname . ' (' . $version . ')';
	} 
	
	/**
	 * Get User's operating system (e.g. Windows 10 x64)
	 * @param $user_agent null
	 * @return string
	 */
	public static function getOS($user_agent = null)
	{
		if(!isset($user_agent) && isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		}

		// https://stackoverflow.com/questions/18070154/get-operating-system-info-with-php
		$os_array = array(
			'windows nt 10'                              =>  'Windows 10',
			'windows nt 6.3'                             =>  'Windows 8.1',
			'windows nt 6.2'                             =>  'Windows 8',
			'windows nt 6.1|windows nt 7.0'              =>  'Windows 7',
			'windows nt 6.0'                             =>  'Windows Vista',
			'windows nt 5.2'                             =>  'Windows Server 2003/XP x64',
			'windows nt 5.1'                             =>  'Windows XP',
			'windows xp'                                 =>  'Windows XP',
			'windows nt 5.0|windows nt5.1|windows 2000'  =>  'Windows 2000',
			'windows me'                                 =>  'Windows ME',
			'windows nt 4.0|winnt4.0'                    =>  'Windows NT',
			'windows ce'                                 =>  'Windows CE',
			'windows 98|win98'                           =>  'Windows 98',
			'windows 95|win95'                           =>  'Windows 95',
			'win16'                                      =>  'Windows 3.11',
			'mac os x 10.1[^0-9]'                        =>  'Mac OS X Puma',
			'macintosh|mac os x'                         =>  'Mac OS X',
			'mac_powerpc'                                =>  'Mac OS 9',
			'linux'                                      =>  'Linux',
			'ubuntu'                                     =>  'Linux - Ubuntu',
			'iphone'                                     =>  'iPhone',
			'ipod'                                       =>  'iPod',
			'ipad'                                       =>  'iPad',
			'android'                                    =>  'Android',
			'blackberry'                                 =>  'BlackBerry',
			'webos'                                      =>  'Mobile',

			'(media center pc).([0-9]{1,2}\.[0-9]{1,2})'=>'Windows Media Center',
			'(win)([0-9]{1,2}\.[0-9x]{1,2})'=>'Windows',
			'(win)([0-9]{2})'=>'Windows',
			'(windows)([0-9x]{2})'=>'Windows',

			// Doesn't seem like these are necessary...not totally sure though..
			//'(winnt)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=>'Windows NT',
			//'(windows nt)(([0-9]{1,2}\.[0-9]{1,2}){0,1})'=>'Windows NT', // fix by bg

			'Win 9x 4.90'=>'Windows ME',
			'(windows)([0-9]{1,2}\.[0-9]{1,2})'=>'Windows',
			'win32'=>'Windows',
			'(java)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2})'=>'Java',
			'(Solaris)([0-9]{1,2}\.[0-9x]{1,2}){0,1}'=>'Solaris',
			'dos x86'=>'DOS',
			'Mac OS X'=>'Mac OS X',
			'Mac_PowerPC'=>'Macintosh PowerPC',
			'(mac|Macintosh)'=>'Mac OS',
			'(sunos)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=>'SunOS',
			'(beos)([0-9]{1,2}\.[0-9]{1,2}){0,1}'=>'BeOS',
			'(risc os)([0-9]{1,2}\.[0-9]{1,2})'=>'RISC OS',
			'unix'=>'Unix',
			'os/2'=>'OS/2',
			'freebsd'=>'FreeBSD',
			'openbsd'=>'OpenBSD',
			'netbsd'=>'NetBSD',
			'irix'=>'IRIX',
			'plan9'=>'Plan9',
			'osf'=>'OSF',
			'aix'=>'AIX',
			'GNU Hurd'=>'GNU Hurd',
			'(fedora)'=>'Linux - Fedora',
			'(kubuntu)'=>'Linux - Kubuntu',
			'(ubuntu)'=>'Linux - Ubuntu',
			'(debian)'=>'Linux - Debian',
			'(CentOS)'=>'Linux - CentOS',
			'(Mandriva).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)'=>'Linux - Mandriva',
			'(SUSE).([0-9]{1,3}(\.[0-9]{1,3})?(\.[0-9]{1,3})?)'=>'Linux - SUSE',
			'(Dropline)'=>'Linux - Slackware (Dropline GNOME)',
			'(ASPLinux)'=>'Linux - ASPLinux',
			'(Red Hat)'=>'Linux - Red Hat',
			// Loads of Linux machines will be detected as unix.
			// Actually, all of the linux machines I've checked have the 'X11' in the User Agent.
			//'X11'=>'Unix',
			'(linux)'=>'Linux',
			'(amigaos)([0-9]{1,2}\.[0-9]{1,2})'=>'AmigaOS',
			'amiga-aweb'=>'AmigaOS',
			'amiga'=>'Amiga',
			'AvantGo'=>'PalmOS',
			//'(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1}-([0-9]{1,2}) i([0-9]{1})86){1}'=>'Linux',
			//'(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1} i([0-9]{1}86)){1}'=>'Linux',
			//'(Linux)([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}(rel\.[0-9]{1,2}){0,1})'=>'Linux',
			'[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,3}'=>'Linux',
			'(webtv)/([0-9]{1,2}\.[0-9]{1,2})'=>'WebTV',
			'Dreamcast'=>'Dreamcast OS',
			'GetRight'=>'Windows',
			'go!zilla'=>'Windows',
			'gozilla'=>'Windows',
			'gulliver'=>'Windows',
			'ia archiver'=>'Windows',
			'NetPositive'=>'Windows',
			'mass downloader'=>'Windows',
			'microsoft'=>'Windows',
			'offline explorer'=>'Windows',
			'teleport'=>'Windows',
			'web downloader'=>'Windows',
			'webcapture'=>'Windows',
			'webcollage'=>'Windows',
			'webcopier'=>'Windows',
			'webstripper'=>'Windows',
			'webzip'=>'Windows',
			'wget'=>'Windows',
			'Java'=>'Unknown',
			'flashget'=>'Windows',

			// delete next line if the script show not the right OS
			//'(PHP)/([0-9]{1,2}.[0-9]{1,2})'=>'PHP',
			'MS FrontPage'=>'Windows',
			'(msproxy)/([0-9]{1,2}.[0-9]{1,2})'=>'Windows',
			'(msie)([0-9]{1,2}.[0-9]{1,2})'=>'Windows',
			'libwww-perl'=>'Unix',
			'UP.Browser'=>'Windows CE',
			'NetAnts'=>'Windows'
		);

		// https://github.com/ahmad-sa3d/php-useragent/blob/master/core/user_agent.php
		$arch_regex = '/\b(x86_64|x86-64|Win64|WOW64|x64|ia64|amd64|ppc64|sparc64|IRIX64)\b/ix';
		$arch = preg_match($arch_regex, $user_agent) ? '64' : '32';

		foreach ($os_array as $regex => $value) {
			if (preg_match('{\b('.$regex.')\b}i', $user_agent)) {
				return $value.' (x'.$arch.')';
			}
		}

		return 'Unknown';
	}

	/**
	 * 
	 * Get User's Country with multiple fallback services
	 *
	 * @return string
	 */
	public static function getCountry()
	{
		// Get the correct IP address of the client
		$ip = IpHelper::getIp();

		if (!filter_var($ip, FILTER_VALIDATE_IP))
		{
			$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		}

		// Skip geolocation for local/private IPs
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)
		{
			return 'Unknown';
		}

		// Try multiple services with fallback
		$services = [
			[
				'url' => "http://www.geoplugin.net/json.gp?ip=" . $ip,
				'parser' => function($data) {
					$json = json_decode($data, true);
					return isset($json['geoplugin_countryName']) ? $json['geoplugin_countryName'] : null;
				}
			],
			[
				'url' => "http://ip-api.com/json/" . $ip . "?fields=country",
				'parser' => function($data) {
					$json = json_decode($data, true);
					return isset($json['country']) ? $json['country'] : null;
				}
			],
			[
				'url' => "https://ipapi.co/" . $ip . "/country_name/",
				'parser' => function($data) {
					return trim($data) !== '' ? trim($data) : null;
				}
			],
			[
				'url' => "http://ipinfo.io/" . $ip . "/json",
				'parser' => function($data) {
					$json = json_decode($data, true);
					return isset($json['country']) ? $json['country'] : null;
				}
			]
		];

		foreach ($services as $service) {
			$country = self::getCountryFromService($service['url'], $service['parser']);
			if ($country && $country !== 'Unknown') {
				return $country;
			}
		}

		return 'Unknown';
	}

	/**
	 * Helper method to get country from a specific service
	 *
	 * @param string $url
	 * @param callable $parser
	 * @return string|null
	 */
	private static function getCountryFromService($url, $parser)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 3 second connection timeout
		curl_setopt($ch, CURLOPT_USERAGENT, 'Web357Framework/1.0');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$output = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($output === false || $httpCode !== 200) {
			return null;
		}

		try {
			return $parser($output);
		} catch (Exception $e) {
			return null;
		}
	}

	/**
       * 
       * Fetch the API Key from the plugin settings
       *
       * @return string
       */
	public static function getAPIKey()
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('params'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('element') . ' = ' . $db->quote('web357framework'));
		$query->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
		$db->setQuery($query);

		try
		{
			$plugin = $db->loadObject();
			$plugin_params = new Registry();
			$plugin_params->loadString($plugin->params);
			return $plugin_params->get('apikey', '');
		}
		catch (RuntimeException $e)
		{
			JError::raiseError(500, $e->getMessage());
		}
	}

	/**
       * 
       * Displays a warning message if the Web357 API key has not been set in the plugin settings.
       *
	   * USAGE: 
	   * // API Key Checker
	   * $w357frmwrk->apikeyChecker();
	   * 
       * @return string
       */
	public function apikeyChecker()
	{
		if (empty($this->apikey) || $this->apikey == '')
		{
			// warn about missing api key
			$api_key_missed_msg = Text::_('W357FRM_APIKEY_REQUIRED_GLOBAL_MSG');

			// display the message
			Factory::getApplication()->enqueueMessage($api_key_missed_msg, 'warning');

			// remove the warning heading from alert message
			Factory::getDocument()->addStyleDeclaration('.alert .alert-heading { display: none; }');
		}
	}
}
endif;

// HOW TO USE
/*
function W357FrameworkHelperClass()
{
	// Call the Web357 Framework Helper Class
	require_once(JPATH_PLUGINS.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'web357framework'.DIRECTORY_SEPARATOR.'web357framework.class.php');
	$w357frmwrk = new Web357FrameworkHelperClass;
	return $w357frmwrk;
}

$this->W357FrameworkHelperClass();
echo $this->W357FrameworkHelperClass()->test;
*/