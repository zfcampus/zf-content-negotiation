<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Http\PhpEnvironment\Request as BaseRequest;

/**
 * Custom request object
 *
 * Adds the ability to retrieve the request content as a stream, in order to
 * reduce memory usage.
 */
class Request extends BaseRequest
{
    /**
     * Stream URI or stream resource for content
     *
     * @var string
     */
    protected $contentStream = 'php://input';

    /**
     * Returns a stream URI for the content, allowing the user to use standard
     * filesystem functions in order to parse the incoming content.
     *
     * This is particularly useful for PUT and PATCH requests that contain file
     * uploads, as you can pipe the content piecemeal to the final destination,
     * preventing situations of memory exhaustion.
     *
     * @return resource Stream
     */
    public function getContentAsStream()
    {
        if (is_resource($this->contentStream)) {
            rewind($this->contentStream);
            return $this->contentStream;
        }

        if (empty($this->content)) {
            return fopen($this->contentStream, 'r');
        }

        $this->contentStream = fopen('php://temp', 'r+');
        fwrite($this->contentStream, $this->content);
        rewind($this->contentStream);
        return $this->contentStream;
    }

    /**
     * Set the content stream to use with getContentAsStream()
     *
     * @param string|resource $stream Either the stream URI to use, or a stream resource
     * @return self
     * @throws Exception\InvalidContentStreamException
     */
    public function setContentStream($stream)
    {
        if (! is_string($stream)
            && ! is_resource($stream)
        ) {
            throw new Exception\InvalidContentStreamException();
        }

        $this->contentStream = $stream;
        return $this;
    }
}
