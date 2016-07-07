<?php

namespace ZF\ContentNegotiation\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Filter\FilterPluginManager;
use Zend\ServiceManager\ServiceManager;

class RenameUploadFilterFactoryTest extends TestCase
{
    protected $filters;

    protected function setUp()
    {
        $config = [
            'factories' => [
                'filerenameupload' => 'ZF\ContentNegotiation\Factory\RenameUploadFilterFactory',
            ],
        ];
        $this->filters = new FilterPluginManager(new ServiceManager(), $config);
    }

    public function testMultipleFilters()
    {
        $optionsFilterOne = [
            'target' => 'SomeDir',
        ];

        $optionsFilterTwo = [
            'target' => 'OtherDir',
        ];

        $filter = $this->filters->get('filerenameupload', $optionsFilterOne);
        $this->assertEquals('SomeDir', $filter->getTarget());

        $otherFilter = $this->filters->get('filerenameupload', $optionsFilterTwo);
        $this->assertEquals('OtherDir', $otherFilter->getTarget());
    }
}
