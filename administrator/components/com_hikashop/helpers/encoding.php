<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.3
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class hikashopEncodingHelper{
	function change($data,$input,$output){
		$input = strtoupper(trim($input));
		$output = strtoupper(trim($output));
		if($input == $output) return $data;
		if($input == 'UTF-8' && $output == 'ISO-8859-1'){
			$data = str_replace(array('�','�','�'),array('EUR','"','"'),$data);
		}
		if (function_exists('iconv')){
			set_error_handler('hikashop_error_handler_encoding');
			$encodedData = iconv($input, $output."//IGNORE", $data);
			restore_error_handler();
			if(!empty($encodedData) && !hikashop_error_handler_encoding('result')){
				return $encodedData;
			}
		}
		if (function_exists('mb_convert_encoding')){
			return @mb_convert_encoding($data, $output, $input);
		}
		if ($input == 'ISO-8859-1' && $output == 'UTF-8'){
			return $this->utf8_encode($data);
		}
		if ($input == 'UTF-8' && $output == 'ISO-8859-1'){
			return $this->utf8_decode($data);
		}
		return $data;
	}
	function utf8_encode(string $s): string {
		if(function_exists('utf8_encode'))
			return utf8_encode($s);
		$s .= $s;
		$len = \strlen($s);

		for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
			switch (true) {
				case $s[$i] < "\x80": $s[$j] = $s[$i]; break;
				case $s[$i] < "\xC0": $s[$j] = "\xC2"; $s[++$j] = $s[$i]; break;
				default: $s[$j] = "\xC3"; $s[++$j] = \chr(\ord($s[$i]) - 64); break;
			}
		}

		return substr($s, 0, $j);
	}

	function utf8_decode(string $string): string {
		if(function_exists('utf8_decode'))
			return utf8_decode($s);
		$s = (string) $string;
		$len = \strlen($s);

		for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
			switch ($s[$i] & "\xF0") {
				case "\xC0":
				case "\xD0":
					$c = (\ord($s[$i] & "\x1F") << 6) | \ord($s[++$i] & "\x3F");
					$s[$j] = $c < 256 ? \chr($c) : '?';
					break;

				case "\xF0":
					++$i;

				case "\xE0":
					$s[$j] = '?';
					$i += 2;
					break;

				default:
					$s[$j] = $s[$i];
			}
		}

		return substr($s, 0, $j);
	}

	function detectEncoding(&$content){
		if(!function_exists('mb_check_encoding')) return '';
		$toTest = array('UTF-8');
		$lang = JFactory::getLanguage();
		$tag = $lang->getTag();
		if($tag == 'el-GR'){
			$toTest[] = 'ISO-8859-7';
		}
		$toTest[] = 'ISO-8859-1';
		$toTest[] = 'ISO-8859-2';
		$toTest[] = 'Windows-1252';
		foreach($toTest as $oneEncoding){
			if(mb_check_encoding($content,$oneEncoding)) return $oneEncoding;
		}
		return '';
	}
}

function hikashop_error_handler_encoding($errno,$errstr=''){
	static $error = false;
	if(is_string($errno) && $errno=='result'){
		$currentError = $error;
		$error = false;
		return $currentError;
	}
	$error = true;
	return true;
}
