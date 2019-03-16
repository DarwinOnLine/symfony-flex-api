<?php

namespace App\Exception;

use App\Utils\ApiProblem;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiProblemException extends HttpException
{
    /**
     * @var ApiProblem
     */
    private $apiProblem;

    /**
     * ApiProblemException constructor.
     *
     * @param ApiProblem      $apiProblem
     * @param \Exception|null $previous
     * @param array           $headers
     * @param int             $code
     */
    public function __construct(ApiProblem $apiProblem, \Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->apiProblem = $apiProblem;
        parent::__construct($this->apiProblem->getStatusCode(), 'API Problem', $previous, $headers, $code);
    }

    /**
     * @return ApiProblem
     */
    public function getApiProblem()
    {
        return $this->apiProblem;
    }
}
