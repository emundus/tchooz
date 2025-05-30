<?php

/**
 * @version        $Id: tags.php
 * @package        Joomla
 * @subpackage     Emundus
 * @copyright      Copyright (C) 2019 eMundus. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.helper');

/**
 * Content Component Cache Helper
 *
 * @static
 * @package        Joomla
 * @subpackage     Helper
 * @since          1.5
 */
class EmundusHelperCache
{
	private $cache = null;
	private $group = '';
	private $cache_enabled = false;

	public function __construct($group = 'com_emundus', $handler = '', $lifetime = '', $context = 'component')
	{
		Log::addLogger(['text_file' => 'com_emundus.cache.error.php'], Log::ERROR, ['com_emundus.cache.error']);

		$cache_path = JPATH_SITE . '/cache';
		if (is_dir($cache_path)) {
			$config        = Factory::getConfig();
			$cache_enabled = $config->get('caching'); // 1 = conservative, 2 = progressive
			$cache_handler = $config->get('cache_handler', 'file');

			if ($cache_enabled > 0) {
				if ($context === 'component' || $cache_enabled === 2) {
					if (empty($lifetime)) {
						$cache_time = $config->get('cachetime', 15);
						$lifetime   = $cache_time * 60;
					}

					$this->group = $group;
					$this->cache = Factory::getCache($group, $handler);
					$this->cache->setLifeTime($lifetime);
					$this->cache->setCaching(true);
					$this->cache_enabled = true;
				}
			}
		}
		else {
			error_log('Cache directory does not exists!');
			Log::add('Cache directory does not exists!', Log::WARNING, 'com_emundus.cache.error');
		}
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function isEnabled()
	{
		return $this->cache_enabled;
	}

	public function get($id)
	{
		$cache = null;

		if ($this->isEnabled()) {
			$cache = $this->cache->get($id, $this->group);
		}

		return $cache;
	}

	public function set($id, $data)
	{
		$stored = false;

		if ($this->isEnabled()) {
			$stored = $this->cache->store($data, $id, $this->group);
		}

		return $stored;
	}

	public function clean($admin = false, $group = '')
	{
		$cleaned = false;

		if ($this->isEnabled()) {
			$cleaned = $this->cache->__call('clean', array($this->group));

			if ($admin && !empty($group)) {
				if (is_dir(JPATH_ADMINISTRATOR.'/cache/'.$group)) {
					try {
						$cleaned = $this->deleteDir($group);
					} catch (Exception $e) {
						$cleaned = false;
						Log::add('Error cleaning cache of group ' . $group . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.cache.error');
					}
				} else {
					$cleaned = false;
					Log::add('Cache directory of group ' . $group . ' does not exists!', Log::WARNING, 'com_emundus.cache.error');
				}
			}
		}

		return $cleaned;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public static function getCurrentGitHash()
	{
		$hash          = '';
		$git_base_path = JPATH_SITE . '/.git';

		if (file_exists($git_base_path . '/HEAD')) {
			$git_str    = file_get_contents($git_base_path . '/HEAD');
			$git_branch = rtrim(preg_replace("/(.*?\/){2}/", '', $git_str));

			if (!empty($git_branch)) {
				$hash = trim(file_get_contents($git_base_path . '/refs/heads/' . $git_branch));
			}
		}

		if (empty($hash)) {
			$xmlDoc = new DOMDocument();
			if ($xmlDoc->load(JPATH_SITE . '/administrator/components/com_emundus/emundus.xml')) {
				$hash = $xmlDoc->getElementsByTagName('version')->item(0)->textContent;
			}
		}

		return $hash;
	}

	/**
	 * @codeCoverageIgnore
	 */
	private function deleteDir($group) {
		$dirPath = JPATH_ADMINISTRATOR . '/cache/' . $group;

		if (!is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				$this->deleteDir($file);
			} else {
				unlink($file);
			}
		}

		return rmdir($dirPath);
	}
}
