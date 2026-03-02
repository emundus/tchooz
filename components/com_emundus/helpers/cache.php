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
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');

class EmundusHelperCache
{
	private ?CacheController $cache;

	private bool $cache_enabled;

	private string $group;

	public function __construct($group = 'com_emundus', $handler = '', $lifetime = null)
	{
		Log::addLogger(['text_file' => 'com_emundus.cache.error.php'], Log::ERROR, ['com_emundus.cache.error']);

		$this->cache = Factory::getContainer()
			->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => $group, 'caching' => true, 'lifetime' => $lifetime]);

		$this->cache_enabled = Factory::getApplication()->get('caching') !== 0;
		$this->group = $group;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function isEnabled(): bool
	{
		return $this->cache_enabled;
	}

	public function get($key)
	{
		$cache = null;

		if ($this->isEnabled() && $this->cache->contains($key))
		{
			$cache = $this->cache->get($key);
		}

		return $cache;
	}

	public function set($key, $data)
	{
		$stored = false;

		if ($this->isEnabled())
		{
			$stored = $this->cache->store($data, $key);
		}

		return $stored;
	}

	public function clean($currentGroup = true, array $groups = []): bool
	{
		$cache = Factory::getApplication()->bootComponent('com_cache')->getMVCFactory();

		/** @var \Joomla\Component\Cache\Administrator\Model\CacheModel $model */
		$model  = $cache->createModel('Cache', 'Administrator', ['ignore_request' => true]);
		$mCache = $model->getCache();

		if($currentGroup)
		{
			$groups[] = $this->group;
		}

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				if (!$mCache->clean($group))
				{
					return false;
				}
			}
		}
		elseif(!$currentGroup)
		{
			foreach ($mCache->getAll() as $cache)
			{
				if ($mCache->clean($cache->group) === false)
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public static function getCurrentGitHash(): string
	{
		$hash          = '';
		$git_base_path = JPATH_SITE . '/.git';

		if (file_exists($git_base_path . '/HEAD'))
		{
			$git_str    = file_get_contents($git_base_path . '/HEAD');
			$git_branch = rtrim(preg_replace("/(.*?\/){2}/", '', $git_str));

			if (!empty($git_branch))
			{
				$hash = trim(file_get_contents($git_base_path . '/refs/heads/' . $git_branch));
			}
		}

		if (empty($hash))
		{
			$xmlDoc = new DOMDocument();
			if ($xmlDoc->load(JPATH_SITE . '/administrator/components/com_emundus/emundus.xml'))
			{
				$hash = $xmlDoc->getElementsByTagName('version')->item(0)->textContent;
			}
		}

		return $hash;
	}
}
