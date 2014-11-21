<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

class ParameterDataContainer
{
    /**
     * @var array
     */
    protected $routeParams = array();

    /**
     * @var array
     */
    protected $queryParams = array();

    /**
     * @var array
     */
    protected $bodyParams = array();

    /**
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * @param  array $routeParams
     * @return self
     */
    public function setRouteParams(array $routeParams)
    {
        $this->routeParams = $routeParams;
        return $this;
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasRouteParam($name)
    {
        return isset($this->routeParams[$name]);
    }

    /**
     * @param  string $name
     * @param  null|mixed $default
     * @return mixed
     */
    public function getRouteParam($name, $default = null)
    {
        if (isset($this->routeParams[$name])) {
            return $this->routeParams[$name];
        }
        return $default;
    }

    /**
     * @param  string $name
     * @param  mixed $value
     * @return self
     */
    public function setRouteParam($name, $value)
    {
        $this->routeParams[$name] = $value;
        return $this;
    }

    /**
     * @param  array $queryParams
     * @return self
     */
    public function setQueryParams(array $queryParams)
    {
        $this->queryParams = $queryParams;
        return $this;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasQueryParam($name)
    {
        return isset($this->queryParams[$name]);
    }

    /**
     * @param  string $name
     * @param  null|mixed $default
     * @return mixed
     */
    public function getQueryParam($name, $default = null)
    {
        if (isset($this->queryParams[$name])) {
            return $this->queryParams[$name];
        }
        return $default;
    }

    /**
     * @param  string $name
     * @param  mixed $value
     * @return self
     */
    public function setQueryParam($name, $value)
    {
        $this->queryParams[$name] = $value;
        return $this;
    }

    /**
     * @param  array $bodyParams
     * @return self
     */
    public function setBodyParams(array $bodyParams)
    {
        $this->bodyParams = $bodyParams;
        return $this;
    }

    /**
     * @return array
     */
    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasBodyParam($name)
    {
        return isset($this->bodyParams[$name]);
    }

    /**
     * @param  string $name
     * @param  null|mixed $default
     * @return mixed
     */
    public function getBodyParam($name, $default = null)
    {
        if (isset($this->bodyParams[$name])) {
            return $this->bodyParams[$name];
        }
        return $default;
    }

    /**
     * @param  string $name
     * @param  mixed $value
     * @return self
     */
    public function setBodyParam($name, $value)
    {
        $this->bodyParams[$name] = $value;
        return $this;
    }
}
