<?php
namespace Worldline\Connect\Sdk;

use Exception;
use stdClass;

/**
 * Class JsonValuesStore
 */
class JsonValuesStore
{
    /**
     * @var string
     */
    private string $valuesFilePath;

    /**
     * @var stdClass|null
     */
    private ?stdClass $valuesObject = null;

    /**
     * @param string $valuesFilePath
     */
    public function __construct(string $valuesFilePath)
    {
        $this->valuesFilePath = $valuesFilePath;
    }

    /**
     * @param string $key
     * @param bool $isRequired
     * @return mixed
     * @throws Exception
     */
    public function getValue(string $key, bool $isRequired = true)
    {
        $valuesObject = $this->getValuesObject();
        $value = null;
        if (!property_exists($valuesObject, $key) && $isRequired) {
            throw new Exception('could not find property "' . $key . '"" in file "' . $this->valuesFilePath . '"');
        }
        if (property_exists($valuesObject, $key)) {
            $value = $valuesObject->{$key};
        }
        return $value;
    }

    /**
     * @return stdClass
     * @throws Exception
     */
    protected function getValuesObject(): stdClass
    {
        if (is_null($this->valuesObject)) {
            if (!file_exists($this->valuesFilePath)) {
                throw new Exception('could not open file ' . $this->valuesFilePath . ' (file does not exist)');
            }
            if (!is_readable($this->valuesFilePath)) {
                throw new Exception('could not open file ' . $this->valuesFilePath . ' (file is not readable)');
            }
            $valuesObject = json_decode(file_get_contents($this->valuesFilePath));
            if (!$valuesObject) {
                throw new Exception(
                    'could not read JSON values file ' . $this->valuesFilePath . ' (file does not contain valid json)'
                );
            }
            $this->valuesObject = $valuesObject;
        }

        return $this->valuesObject;
    }
}
