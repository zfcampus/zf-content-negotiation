<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use Zend\Console\Console;
use Zend\Console\Request as ConsoleRequest;
use ZF\ContentNegotiation\Request as HttpRequest;

class RequestFactory
{
    /**
     * @param  ContainerInterface $container
     * @return ConsoleRequest|HttpRequest
     */
    public function __invoke(ContainerInterface $container)
    {
        if (Console::isConsole()) {
            return new ConsoleRequest();
        }

        return new HttpRequest();
    }
}
