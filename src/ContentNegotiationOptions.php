<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2018 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Stdlib\AbstractOptions;

class ContentNegotiationOptions extends AbstractOptions
{
    /**
     * @var array
     */
    protected $controllers = [];

    /**
     * @var array
     */
    protected $selectors = [];

    /**
     * @var array
     */
    protected $acceptWhitelist = [];

    /**
     * @var array
     */
    protected $contentTypeWhitelist = [];

    /**
     * @var boolean
     */
    protected $xHttpMethodOverrideEnabled = false;

    /**
     * @var array
     */
    protected $httpOverrideMethods = [];

    /**
     * @var array
     */
    private $keysToNormalize = [
        'accept-whitelist',
        'content-type-whitelist',
        'x-http-method-override-enabled',
        'http-override-methods',
    ];

    /**
     * {@inheritDoc}
     *
     * Normalizes and merges the configuration for specific configuration keys
     * @see self::normalizeOptions
     */
    public function setFromArray($options)
    {
        return parent::setFromArray(
            $this->normalizeOptions($options)
        );
    }

    /**
     * This method uses the config keys given in $keyToNormalize to merge
     * the config.
     * It uses Zend's default approach of merging configs, by merging them with
     * `array_merge_recursive()`.
     *
     * @param array $config
     * @return array
     */
    private function normalizeOptions(array $config)
    {
        $mergedConfig = $config;

        foreach ($this->keysToNormalize as $key) {
            $normalizedKey = $this->normalizeKey($key);

            if (isset($config[$key]) && isset($config[$normalizedKey])) {
                $mergedConfig[$normalizedKey] = array_merge_recursive(
                    $config[$key],
                    $config[$normalizedKey]
                );
                unset($mergedConfig[$key]);
                continue;
            }

            if (isset($config[$key])) {
                $mergedConfig[$normalizedKey] = $config[$key];
                unset($mergedConfig[$key]);
                continue;
            }

            if (isset($config[$normalizedKey])) {
                $mergedConfig[$normalizedKey] = $config[$normalizedKey];
                continue;
            }
        }

        return $mergedConfig;
    }

    /**
     * @deprecated since 1.4.0; hhould be removed in next major version, and only one
     *     configuration key style should be supported.
     * @param string $key
     * @return string
     */
    private function normalizeKey($key)
    {
        return str_replace('-', '_', $key);
    }

    /**
     * {@inheritDoc}
     *
     * Normalizes dash-separated keys to underscore-separated to ensure
     * backwards compatibility with old options (even though dash-separated
     * were previously ignored!).
     *
     * @see \Zend\Stdlib\ParameterObject::__set()
     * @param string $key
     * @param mixed $value
     * @throws \Zend\Stdlib\Exception\BadMethodCallException
     * @return void
     */
    public function __set($key, $value)
    {
        parent::__set($this->normalizeKey($key), $value);
    }

    /**
     * {@inheritDoc}
     *
     * Normalizes dash-separated keys to underscore-separated to ensure
     * backwards compatibility with old options (even though dash-separated
     * were previously ignored!).
     *
     * @see \Zend\Stdlib\ParameterObject::__get()
     * @param string $key
     * @throws \Zend\Stdlib\Exception\BadMethodCallException
     * @return mixed
     */
    public function __get($key)
    {
        return parent::__get($this->normalizeKey($key));
    }

    /**
     * @param array $controllers
     */
    public function setControllers(array $controllers)
    {
        $this->controllers = $controllers;
    }

    /**
     * @return array
     */
    public function getControllers()
    {
        return $this->controllers;
    }

    /**
     * @param array $selectors
     */
    public function setSelectors(array $selectors)
    {
        $this->selectors = $selectors;
    }

    /**
     * @return array
     */
    public function getSelectors()
    {
        return $this->selectors;
    }

    /**
     * @param array $whitelist
     */
    public function setAcceptWhitelist(array $whitelist)
    {
        $this->acceptWhitelist = $whitelist;
    }

    /**
     * @return array
     */
    public function getAcceptWhitelist()
    {
        return $this->acceptWhitelist;
    }

    /**
     * @param array $whitelist
     */
    public function setContentTypeWhitelist(array $whitelist)
    {
        $this->contentTypeWhitelist = $whitelist;
    }

    /**
     * @return array
     */
    public function getContentTypeWhitelist()
    {
        return $this->contentTypeWhitelist;
    }

    /**
     * @param boolean $xHttpMethodOverrideEnabled
     */
    public function setXHttpMethodOverrideEnabled($xHttpMethodOverrideEnabled)
    {
        $this->xHttpMethodOverrideEnabled = $xHttpMethodOverrideEnabled;
    }

    /**
     * @return boolean
     */
    public function getXHttpMethodOverrideEnabled()
    {
        return $this->xHttpMethodOverrideEnabled;
    }

    /**
     * @param array $httpOverrideMethods
     */
    public function setHttpOverrideMethods(array $httpOverrideMethods)
    {
        $this->httpOverrideMethods = $httpOverrideMethods;
    }

    /**
     * @return array
     */
    public function getHttpOverrideMethods()
    {
        return $this->httpOverrideMethods;
    }
}
