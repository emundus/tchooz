<?php


namespace FedexRest\Traits;


trait switchableEnv
{
    public bool $production_mode = false;
    protected string $production_url = 'https://apis.fedex.com';
    protected string $testing_url = 'https://apis-sandbox.fedex.com';

    public function getApiUri($endpoint = '')
    {
        return (($this->production_mode === false) ? $this->testing_url : $this->production_url).$endpoint;
    }

    public function useProduction()
    {
        $this->production_mode = true;
        return $this;
    }
}
