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
        return [
            'accept-whitelist' => ['accept-whitelist', 'accept_whitelist'],
            'content-type-whitelist' => ['content-type-whitelist', 'content_type_whitelist'],
            'x-http-method-override-enabled' => ['x-http-method-override-enabled', 'x_http_method_override_enabled'],
            'http-override-methods' => ['http-override-methods', 'http_override_methods'],
        ];
    }

    /**
     * @dataProvider dashSeparatedOptions
     */
    public function testSetNormalizesDashSeparatedKeysToUnderscoreSeparated($key, $normalized)
    {
        $options = new ContentNegotiationOptions();
        $options->{$key} = ['value'];
        $this->assertEquals(['value'], $options->{$key});
        $this->assertEquals(['value'], $options->{$normalized});
    }

    /**
     * @dataProvider dashSeparatedOptions
     */
    public function testConstructorAllowsDashSeparatedKeys($key, $normalized)
    {
        $options = new ContentNegotiationOptions([$key => ['value']]);
        $this->assertEquals(['value'], $options->{$key});
        $this->assertEquals(['value'], $options->{$normalized});
    }

    /**
     * @dataProvider dashSeparatedOptions
     */
    public function testDashAndUnderscoreSeparatedValuesGetMerged(
        $key,
        $normalized
    ) {
        $keyValue = 'valueKey';
        $normalizedValue = 'valueNormalized';
        $expectedResult = [
            $keyValue,
            $normalizedValue,
        ];

        $options = new ContentNegotiationOptions(
            [
                $key => [
                    $keyValue,
                ],
                $normalized => [
                    $normalizedValue,
                ],
            ]
        );

        $this->assertEquals(
            $expectedResult,
            $options->{$key},
            'The value for the hyphen separated key was not as expected.'
        );
        $this->assertEquals(
            $expectedResult,
            $options->{$normalized},
            'The value for the normalized key was not as expected.'
        );
    }
}
