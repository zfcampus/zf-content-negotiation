<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Http\Headers as HttpHeaders;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class AcceptFilterListener extends ContentTypeFilterListener
{
    /**
     * Test if the accept content-type received is allowable.
     *
     * @param  MvcEvent $e
     * @return null|ApiProblemResponse
     */
    public function onRoute(MvcEvent $e)
    {
        if (empty($this->config)) {
            return;
        }

        $controllerName = $e->getRouteMatch()->getParam('controller');
        if (!isset($this->config[$controllerName])) {
            return;
        }

        $request = $e->getRequest();
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        $headers = $request->getHeaders();

        $matched = false;
        if (is_string($this->config[$controllerName])) {
            $matched = $this->validateMediaType($this->config[$controllerName], $headers);
        } elseif (is_array($this->config[$controllerName])) {
            foreach ($this->config[$controllerName] as $whitelistType) {
                $matched = $this->validateMediaType($whitelistType, $headers);
                if ($matched) {
                    break;
                }
            }
        }

        if (!$matched) {
            return new ApiProblemResponse(
                new ApiProblem(406, 'Cannot honor Accept type specified')
            );
        }
    }

    /**
     * Validate the passed mediatype against the appropriate header
     *
     * @param  string $match
     * @param  HttpHeaders $headers
     * @return bool
     */
    protected function validateMediaType($match, HttpHeaders $headers)
    {
        if (!$headers->has('accept')) {
            return false;
        }

        $accept = $headers->get('accept');
        if ($accept->match($match)) {
            return true;
        }

        return false;
    }
}
