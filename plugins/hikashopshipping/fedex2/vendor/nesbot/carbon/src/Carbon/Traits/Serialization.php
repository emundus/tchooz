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


namespace Carbon\Traits;

use Carbon\Exceptions\InvalidFormatException;
use ReturnTypeWillChange;
use Throwable;

trait Serialization
{
    use ObjectInitialisation;

    protected static $serializer;

    protected $dumpProperties = ['date', 'timezone_type', 'timezone'];

    protected $dumpLocale;

    protected $dumpDateProperties;

    public function serialize()
    {
        return serialize($this);
    }

    public static function fromSerialized($value)
    {
        $instance = @unserialize((string) $value);

        if (!$instance instanceof static) {
            throw new InvalidFormatException("Invalid serialized value: $value");
        }

        return $instance;
    }

    #[ReturnTypeWillChange]
    public static function __set_state($dump)
    {
        if (\is_string($dump)) {
            return static::parse($dump);
        }


        $date = get_parent_class(static::class) && method_exists(parent::class, '__set_state')
            ? parent::__set_state((array) $dump)
            : (object) $dump;

        return static::instance($date);
    }

    public function __sleep()
    {
        $properties = $this->getSleepProperties();

        if ($this->localTranslator ?? null) {
            $properties[] = 'dumpLocale';
            $this->dumpLocale = $this->locale ?? null;
        }

        return $properties;
    }

    public function __serialize(): array
    {
        if (isset($this->timezone_type, $this->timezone, $this->date)) {
            return [
                'date' => $this->date ?? null,
                'timezone_type' => $this->timezone_type,
                'timezone' => $this->timezone ?? null,
            ];
        }

        $timezone = $this->getTimezone();
        $export = [
            'date' => $this->format('Y-m-d H:i:s.u'),
            'timezone_type' => $timezone->getType(),
            'timezone' => $timezone->getName(),
        ];

        if (\extension_loaded('msgpack') && isset($this->constructedObjectId)) {
            $export['dumpDateProperties'] = [
                'date' => $this->format('Y-m-d H:i:s.u'),
                'timezone' => serialize($this->timezone ?? null),
            ];
        }

        if ($this->localTranslator ?? null) {
            $export['dumpLocale'] = $this->locale ?? null;
        }

        return $export;
    }

    #[ReturnTypeWillChange]
    public function __wakeup()
    {
        if (parent::class && method_exists(parent::class, '__wakeup')) {
            try {
                parent::__wakeup();
            } catch (Throwable $exception) {
                try {
                    ['date' => $date, 'timezone' => $timezone] = $this->dumpDateProperties;
                    parent::__construct($date, unserialize($timezone));
                } catch (Throwable $ignoredException) {
                    throw $exception;
                }
            }
        }

        $this->constructedObjectId = spl_object_hash($this);

        if (isset($this->dumpLocale)) {
            $this->locale($this->dumpLocale);
            $this->dumpLocale = null;
        }

        $this->cleanupDumpProperties();
    }

    public function __unserialize(array $data): void
    {
        try {
            $this->__construct($data['date'] ?? null, $data['timezone'] ?? null);
        } catch (Throwable $exception) {
            if (!isset($data['dumpDateProperties']['date'], $data['dumpDateProperties']['timezone'])) {
                throw $exception;
            }

            try {
                ['date' => $date, 'timezone' => $timezone] = $data['dumpDateProperties'];
                $this->__construct($date, unserialize($timezone));
            } catch (Throwable $ignoredException) {
                throw $exception;
            }
        }

        if (isset($data['dumpLocale'])) {
            $this->locale($data['dumpLocale']);
        }
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $serializer = $this->localSerializer ?? static::$serializer;

        if ($serializer) {
            return \is_string($serializer)
                ? $this->rawFormat($serializer)
                : $serializer($this);
        }

        return $this->toJSON();
    }

    public static function serializeUsing($callback)
    {
        static::$serializer = $callback;
    }

    public function cleanupDumpProperties()
    {
        if (PHP_VERSION < 8.2) {
            foreach ($this->dumpProperties as $property) {
                if (isset($this->$property)) {
                    unset($this->$property);
                }
            }
        }

        return $this;
    }

    private function getSleepProperties(): array
    {
        $properties = $this->dumpProperties;

        if (!\extension_loaded('msgpack')) {
            return $properties;
        }

        if (isset($this->constructedObjectId)) {
            $this->dumpDateProperties = [
                'date' => $this->format('Y-m-d H:i:s.u'),
                'timezone' => serialize($this->timezone ?? null),
            ];

            $properties[] = 'dumpDateProperties';
        }

        return $properties;
    }
}
