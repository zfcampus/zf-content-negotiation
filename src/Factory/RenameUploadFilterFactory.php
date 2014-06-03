<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use ZF\ContentNegotiation\Filter\RenameUpload;

class RenameUploadFilterFactory
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
     * Create a RenameUpload instance
     *
     * @param \Zend\Filter\FilterPluginManager $filters
     * @return RenameUpload
     */
    public function __invoke($filters)
    {
        $services = $filters->getServiceLocator();
        $filter   = new RenameUpload($this->creationOptions);
        if ($services->has('Request')) {
            $filter->setRequest($services->get('Request'));
        }
        return $filter;
    }
}
