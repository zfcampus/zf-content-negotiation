<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use JsonSerializable;
use Zend\Stdlib\JsonSerializable as StdlibJsonSerializable;
use Zend\View\Model\JsonModel as BaseJsonModel;

class JsonModel extends BaseJsonModel
{
    /**
     * Mark view model as terminal by default (intended for use with APIs)
     *
     * @var bool
     */
    protected $terminate = true;

    /**
     * Set variables
     *
     * Overrides parent to extract variables from JsonSerializable objects.
     * 
     * @param  array|Traversable|JsonSerializable|StdlibJsonSerializable $variables 
     * @param  bool $overwrite 
     * @return self
     */
    public function setVariables($variables, $overwrite = false)
    {
        if ($variables instanceof JsonSerializable
            || $variables instanceof StdlibJsonSerializable
        ) {
            $variables = $variables->jsonSerialize();
        }
        return parent::setVariables($variables, $overwrite);
    }
}
