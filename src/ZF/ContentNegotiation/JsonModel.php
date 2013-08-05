<?php

namespace ZF\ContentNegotiation;

use Zend\View\Model\JsonModel as BaseJsonModel;

class JsonModel extends BaseJsonModel
{
    /**
     * Mark view model as terminal by default (intended for use with APIs)
     * 
     * @var bool
     */
    protected $terminate = true;
}
