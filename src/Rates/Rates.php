<?php

namespace DvK\Vat\Rates;

use DvK\Vat\Rates\Caches\NullCache;
use DvK\Vat\Rates\Clients\JsonVat;
use DvK\Vat\Rates\Interfaces\Cache;
use DvK\Vat\Rates\Interfaces\Client;
use DvK\Vat\Rates\Exceptions\Exception;

class Rates
{

    /**
     * @var array
     */
    protected $map = array();

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Rates constructor.
     *
     * @param Client $client     (optional)
     * @param Cache $cache          (optional)
     */
    public function __construct( Client $client = null, Cache $cache = null )
    {
        $this->client = $client;
        $this->cache = $cache ? $cache : new NullCache();
        $this->map = $this->load();
    }

    protected function load()
    {
        // load from cache
        $map = $this->cache->get('vat-rates');

        // fetch from jsonvat.com
        if (empty($map)) {
            $map = $this->fetch();

            // store in cache
            $this->cache->put('vat-rates', $map, 86400);
        }

        return $map;
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    protected function fetch()
    {
        if( ! $this->client ) {
            $this->client = new JsonVat();
        }

        return $this->client->fetch();
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->map;
    }

    /**
     * @param string $country
     * @param string $rate
     *
     * @return double
     *
     * @throws Exception
     */
    public function country($country, $rate = 'standard')
    {
        $country = strtoupper($country);
        $country = $this->getCountryCode($country);

        if (!isset($this->map[$country])) {
            throw new Exception('Invalid country code.');
        }

        if (!isset($this->map[$country]->$rate)) {
            throw new Exception('Invalid rate.');
        }

        return $this->map[$country]->$rate;
    }

    /**
     * Get normalized country code
     *
     * Fixes ISO-3166-1-alpha2 exceptions
     *
     * @param string $country
     * @return string
     */
    protected function getCountryCode($country)
    {
        if ($country == 'UK') {
            $country = 'GB';
        }

        if ($country == 'EL') {
            $country = 'GR';
        }

        return $country;
    }


}
