<?php

namespace App\Utils;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * API output response.
 *
 * Inherited methods (from $response attribute)
 *
 * @method string            getProtocolVersion()                  Retrieves the HTTP protocol version as a string.
 * @method ResponseInterface withProtocolVersion($version)         Return an instance with the specified HTTP protocol version.
 * @method string[][]        getHeaders()                          Retrieves all message header values.
 * @method bool              hasHeader($name)                      Checks if a header exists by the given case-insensitive name.
 * @method string[]          getHeader($name)                      Retrieves a message header value by the given case-insensitive name.
 * @method string            getHeaderLine($name)                  Retrieves a comma-separated string of the values for a single header.
 * @method ResponseInterface withHeader($name, $value)             Return an instance with the provided value replacing the specified header.
 * @method ResponseInterface withAddedHeader($name, $value)        Return an instance with the specified header appended with the given value.
 * @method ResponseInterface withoutHeader($name)                  Return an instance without the specified header.
 * @method StreamInterface   getBody()                             Gets the body of the message.
 * @method ResponseInterface withBody(StreamInterface $body)       Return an instance with the specified message body.
 * @method int               getStatusCode()                       Gets the response status code.
 * @method ResponseInterface withStatus($code, $reasonPhrase = '') Return an instance with the specified status code and, optionally, reason phrase.
 * @method string            getReasonPhrase()                     Gets the response reason phrase associated with the status code.
 */
class ApiOutput
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var string
     */
    private $stringData;

    /**
     * @var mixed
     */
    private $data;

    /**
     * ApiOutput constructor.
     *
     * @param ResponseInterface $response
     * @param string            $format
     */
    public function __construct(ResponseInterface $response, $format)
    {
        $this->response = $response;
        $this->stringData = $response->getBody()->getContents();
        try {
            $this->data = ApiFormat::readData($this->stringData, $format);
        } catch (UnexpectedValueException $e) {
            $this->data = $e->getMessage();
        }
    }

    /**
     * Magic call to access to response elements.
     *
     * @param $method
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->response, $method)) {
            return \call_user_func_array([$this->response, $method], $arguments);
        }
    }

    /**
     * Get decoded data.
     *
     * @param bool $asString
     *
     * @return array|mixed|string
     */
    public function getData($asString = false)
    {
        return $asString ? $this->stringData : $this->data;
    }
}
