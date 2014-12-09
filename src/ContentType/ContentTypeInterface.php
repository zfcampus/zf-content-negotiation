<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Service;

use Zend\Mvc\MvcEvent;

interface ContentTypeInterface 
{
    public function __invoke(MvcEvent $event);
}
