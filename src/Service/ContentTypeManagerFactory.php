<?php

namespace ZF\ContentNegotiation\Service;

use Zend\Mvc\Service\AbstractPluginManagerFactory;

class ContentTypeManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = 'ZF\ContentNegotiation\Service\ContentTypeManager';
}
