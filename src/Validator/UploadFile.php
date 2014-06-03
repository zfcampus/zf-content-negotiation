<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Validator;

use Zend\Validator\File\UploadFile as BaseValidator;
use Zend\Stdlib\RequestInterface;

class UploadFile extends BaseValidator
{
    /**
     * @var null|RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Overrides isValid()
     *
     * If the reason for failure is self::ATTACK, we can assume that
     * is_uploaded_file() has failed -- which is
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if (null === $this->request
            || ! method_exists($this->request, 'isPut')
            || (! $this->request->isPut()
                && ! $this->request->isPatch())
        ) {
            // In absence of a request object, an HTTP request, or a PATCH/PUT
            // operation, just use the parent logic.
            return parent::isValid($value);
        }

        $result = parent::isValid($value);
        if ($result !== false) {
            return $result;
        }

        if (! isset($this->abstractOptions['messages'][static::ATTACK])) {
            return $result;
        }

        if (count($this->abstractOptions['messages']) > 1) {
            return $result;
        }

        unset($this->abstractOptions['messages'][static::ATTACK]);
        return true;
    }
}
