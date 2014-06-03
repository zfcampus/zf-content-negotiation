<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Filter;

use Zend\Filter\Exception\RuntimeException as FilterRuntimeException;
use Zend\Filter\File\RenameUpload as BaseFilter;
use Zend\Stdlib\ErrorHandler;
use Zend\Stdlib\RequestInterface;

class RenameUpload extends BaseFilter
{
    /**
     * @var RequestInterface
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
     * Override moveUploadedFile
     *
     * If the request is not HTTP, or not a PUT or PATCH request, delegates to
     * the parent functionality.
     *
     * Otherwise, does a `rename()` operation, and returns the status of the
     * operation.
     *
     * @param string $sourceFile
     * @param string $targetFile
     * @return bool
     * @throws FilterRuntimeException in the event of a warning
     */
    protected function moveUploadedFile($sourceFile, $targetFile)
    {
        if (null === $this->request
            || ! method_exists($this->request, 'isPut')
            || (! $this->request->isPut() && ! $this->request->isPatch())
        ) {
            return parent::moveUploadedFile($sourceFile, $targetFile);
        }

        ErrorHandler::start();
        $result = rename($sourceFile, $targetFile);
        $warningException = ErrorHandler::stop();

        if (false === $result || null !== $warningException) {
            throw new FilterRuntimeException(
                sprintf('File "%s" could not be renamed. An error occurred while processing the file.', $sourceFile),
                0,
                $warningException
            );
        }
        return $result;
    }
}
