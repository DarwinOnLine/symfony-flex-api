<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * API output response.
 */
class ApiOutput
{
    /**
     * @var Response
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
     * @param Response    $response
     * @param string|null $format
     */
    public function __construct(Response $response, $format = null)
    {
        $this->response = $response;
        if ($format) {
            $this->stringData = $response->getContent();
            try {
                $this->data = ApiFormat::readData($this->stringData, $format);
            } catch (UnexpectedValueException $e) {
                $this->data = $e->getMessage();
            }
        }
    }

    /**
     * Get handled response.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
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
