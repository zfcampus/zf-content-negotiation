<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\ContentNegotiation\ContentNegotiationOptions;

class ContentNegotiationOptionsTest extends TestCase
{
    public function dashSeparatedOptions()
    {
        return array(
            'accept-whitelist' => array('accept-whitelist', 'accept_whitelist'),
            'content-type-whitelist' => array('content-type-whitelist', 'content_type_whitelist'),
        );
    }

    /**
     * @dataProvider dashSeparatedOptions
     */
    public function testSetNormalizesDashSeparatedKeysToUnderscoreSeparated($key, $normalized)
    {
        $options = new ContentNegotiationOptions();
        $options->{$key} = array('value');
        $this->assertEquals(array('value'), $options->{$key});
        $this->assertEquals(array('value'), $options->{$normalized});
    }

    /**
     * @dataProvider dashSeparatedOptions
     */
    public function testConstructorAllowsDashSeparatedKeys($key, $normalized)
    {
        $options = new ContentNegotiationOptions(array($key => array('value')));
        $this->assertEquals(array('value'), $options->{$key});
        $this->assertEquals(array('value'), $options->{$normalized});
    }
}
