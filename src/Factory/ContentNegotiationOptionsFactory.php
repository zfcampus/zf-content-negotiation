<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use ZF\ContentNegotiation\ContentNegotiationOptions;

class ContentNegotiationOptionsFactory
{
    /**
     * @param  ContainerInterface $container
     * @return ContentNegotiationOptions
     */
    public function __invoke(ContainerInterface $container)
    {
        return new ContentNegotiationOptions($this->getConfig($container));
    }

    /**
     * Attempt to retrieve the zf-content-negotiation configuration.
     *
     * - Consults the container's 'config' service, returning an empty array
     *   if not found.
     * - Validates that the zf-content-negotiation key exists, and evaluates
     *   to an array; if not,returns an empty array.
     *
     * @param ContainerInterface $container
     * @return array
     */
    private function getConfig(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');

        if (! isset($config['zf-content-negotiation'])
            || ! is_array($config['zf-content-negotiation'])
        ) {
            return [];
        }

        return $config['zf-content-negotiation'];
    }
}
