<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation\Validator;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as HttpRequest;
use ZF\ContentNegotiation\Validator\UploadFile;

class UploadFileTest extends TestCase
{
    public function setUp()
    {
        $this->validator = new UploadFile();
    }

    public function uploadMethods()
    {
        return array(
            'put'   => array('PUT'),
            'patch' => array('PATCH'),
        );
    }

    /**
     * @dataProvider uploadMethods
     */
    public function testDoesNotMarkUploadFileAsInvalidForPutAndPatchHttpRequests($method)
    {
        $request = new HttpRequest();
        $request->setMethod($method);
        $this->validator->setRequest($request);

        $file = array(
            'name'     => basename(__FILE__),
            'tmp_name' => realpath(__FILE__),
            'size'     => filesize(__FILE__),
            'type'     => 'application/x-php',
            'error'    => UPLOAD_ERR_OK,
        );

        $this->assertTrue($this->validator->isValid($file), var_export($this->validator->getMessages(), 1));
    }
}
