<?php
/**
 * Copyright (C) 2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2018 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

/**
 * Class ECBExchange
 */
class ECBExchange extends CurrencyRateModule
{
    const SERVICE_URL = 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /*
     * If filled, an array with currency exchange rates, like this:
     *
     *     [
     *         'EUR' => 1.233434,
     *         'USD' => 1.343,
     *         [...]
     *     ]
     */
    protected $serviceCache = [];

    /**
     * ECBExchange constructor.
     */
    public function __construct()
    {
        $this->name = 'ecbexchange';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ECB Exchange Rate Services');
        $this->description = $this->l('Fetches currency exchange rates from the European Central Bank.');
        $this->tb_versions_compliancy = '> 1.0.0';
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install()
               && $this->registerHook('actionRetrieveCurrencyRates');
    }

    /**
     * @param  array $params Description see hookActionRetrieveCurrencyRates()
     *                       in classes/module/CurrencyRateModule.php in core.
     *
     * @return false|array   Description see hookActionRetrieveCurrencyRates()
     *                       in classes/module/CurrencyRateModule.php in core.
     *
     * @since 1.0.0
     */
    public function hookActionRetrieveCurrencyRates($params)
    {
        static::fillServiceCache();

        return false;
    }

    /**
     * @return array An array with uppercase currency codes (ISO 4217).
     *
     * @since 1.0.0
     */
    public function getSupportedCurrencies()
    {
        static::fillServiceCache();

        return [];
    }

    /**
     * Makes sure that $this->serviceCache is filled and does an service
     * request if not. Note that $this->serviceCache can be still an empty
     * array after return, e.g. if the request failed for some reason.
     *
     * @since 1.0.0
     */
    public function fillServiceCache()
    {
        if (!count($this->serviceCache)) {
            $guzzle = new GuzzleHttp\Client();
            try {
                $response = $guzzle->get(static::SERVICE_URL)->getBody();
                $XML = simplexml_load_string($response);

                $this->serviceCache['EUR'] = 1.0;
                foreach ($XML->Cube->Cube->Cube as $entry) {
                    $this->serviceCache[(string) $entry['currency']] =
                        (float) $entry['rate'];
                }
            } catch (Exception $e) {
                $this->serviceCache = [];
            }
        }
    }
}
