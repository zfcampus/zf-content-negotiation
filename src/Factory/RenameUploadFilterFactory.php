<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use Traversable;
use ZF\ContentNegotiation\Filter\RenameUpload;

class RenameUploadFilterFactory
{
    /**
     * @param  ContainerInterface $container
     * @return RenameUpload
     */
    public function __invoke(ContainerInterface $container)
    {
        $filter   = new RenameUpload($this->creationOptions);

        if ($container->has('Request')) {
            $filter->setRequest($container->get('Request'));
        }

        return $filter;
    }
}
