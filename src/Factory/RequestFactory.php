<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Zend\Console\Console;
use Zend\Console\Request as ConsoleRequest;
use ZF\ContentNegotiation\Request as HttpRequest;

class RequestFactory
{
    /**
     * Create and return a request instance, according to current environment.
     *
     * @param  \Zend\ServiceManager\ServiceLocatorInterface $services
     * @return ConsoleRequest|HttpRequest
     */
    public function __invoke($services)
    {
        if (Console::isConsole()) {
            return new ConsoleRequest();
        }

        return new HttpRequest();
    }
}
