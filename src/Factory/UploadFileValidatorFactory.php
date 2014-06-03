<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use ZF\ContentNegotiation\Validator\UploadFile;

class UploadFileValidatorFactory
{
    /**
     * @var null|array|\Traversable
     */
    protected $creationOptions;

    /**
     * @param null|array|\Traversable $options
     */
    public function __construct($options = null)
    {
        $this->creationOptions = $options;
    }

    /**
     * Create an UploadFile instance
     *
     * @param \Zend\Validator\ValidatorPluginManager $validators
     * @return UploadFile
     */
    public function __invoke($validators)
    {
        $services  = $validators->getServiceLocator();
        $validator = new UploadFile($this->creationOptions);
        if ($services->has('Request')) {
            $validator->setRequest($services->get('Request'));
        }
        return $validator;
    }
}
