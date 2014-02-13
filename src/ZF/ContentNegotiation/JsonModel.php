<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

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

    public function setVariables($variables, $overwrite = false)
    {
        if ($variables instanceof \JsonSerializable) {
            $variables = $variables->jsonSerialize();
        }
        return parent::setVariables($variables, $overwrite);
    }
}
