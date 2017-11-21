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
        // If console tooling is present, use that to determine whether or not
        // we are in a console environment. This approach allows overriding the
        // environment for purposes of testing HTTP requests from the CLI.
        if (class_exists(Console::class)) {
            return Console::isConsole() ? new ConsoleRequest() : new HttpRequest();
        }

        // If console tooling is not present, we use the PHP_SAPI value to decide.
        return PHP_SAPI === 'cli' ? new ConsoleRequest() : new HttpRequest();
    }
}
