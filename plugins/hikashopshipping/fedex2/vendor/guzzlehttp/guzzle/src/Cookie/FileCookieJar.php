<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
namespace GuzzleHttp\Cookie;

class FileCookieJar extends CookieJar
{

    private $filename;


    private $storeSessionCookies;

    public function __construct($cookieFile, $storeSessionCookies = false)
    {
        parent::__construct();
        $this->filename = $cookieFile;
        $this->storeSessionCookies = $storeSessionCookies;

        if (file_exists($cookieFile)) {
            $this->load($cookieFile);
        }
    }

    public function __destruct()
    {
        $this->save($this->filename);
    }

    public function save($filename)
    {
        $json = [];
        foreach ($this as $cookie) {

            if (CookieJar::shouldPersist($cookie, $this->storeSessionCookies)) {
                $json[] = $cookie->toArray();
            }
        }

        $jsonStr = \GuzzleHttp\json_encode($json);
        if (false === file_put_contents($filename, $jsonStr, LOCK_EX)) {
            throw new \RuntimeException("Unable to save file {$filename}");
        }
    }

    public function load($filename)
    {
        $json = file_get_contents($filename);
        if (false === $json) {
            throw new \RuntimeException("Unable to load file {$filename}");
        } elseif ($json === '') {
            return;
        }

        $data = \GuzzleHttp\json_decode($json, true);
        if (is_array($data)) {
            foreach (json_decode($json, true) as $cookie) {
                $this->setCookie(new SetCookie($cookie));
            }
        } elseif (strlen($data)) {
            throw new \RuntimeException("Invalid cookie file: {$filename}");
        }
    }
}
