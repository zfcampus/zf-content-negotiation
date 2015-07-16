<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
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
        parent::__set(str_replace('-', '_', $key), $value);
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
        return parent::__get(str_replace('-', '_', $key));
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
}
