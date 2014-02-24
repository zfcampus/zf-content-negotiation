<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use JsonSerializable;
use Zend\Json\Json;
use Zend\Stdlib\JsonSerializable as StdlibJsonSerializable;
use Zend\View\Model\JsonModel as BaseJsonModel;
use ZF\Hal\Entity as HalEntity;
use ZF\Hal\Collection as HalCollection;

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

    /**
     * Override setTerminal()
     *
     * Becomes a no-op; this model should always be terminal.
     *
     * @param  bool $flag
     * @return self
     */
    public function setTerminal($flag)
    {
        // Do nothing; should always terminate
        return $this;
    }

    /**
     * Override serialize()
     *
     * Tests for the special top-level variable "payload", set by ZF\Rest\RestController.
     *
     * If discovered, the value is pulled and used as the variables to serialize.
     *
     * A further check is done to see if we have a ZF\Hal\Entity or
     * ZF\Hal\Collection, and, if so, we pull the top-level entity or
     * collection and serialize that.
     *
     * @return string
     */
    public function serialize()
    {
        $variables = $this->getVariables();

        // 'payload' == ZF\Rest\RestController payload
        if (isset($variables['payload'])) {
            $variables = $variables['payload'];
        }

        // Use ZF\Hal\Entity's composed entity
        if ($variables instanceof HalEntity) {
            $variables = $variables->entity;
        }

        // Use ZF\Hal\Collection's composed collection
        if ($variables instanceof HalCollection) {
            $variables = $variables->getCollection();
        }

        if ($variables instanceof Traversable) {
            $variables = ArrayUtils::iteratorToArray($variables);
        }

        if (null !== $this->jsonpCallback) {
            return $this->jsonpCallback.'('.Json::encode($variables).');';
        }
        return Json::encode($variables);
    }
}
