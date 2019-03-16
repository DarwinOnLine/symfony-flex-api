<?php

namespace App\Event;

use App\Exception\ApiProblemException;
use App\Utils\ApiProblem;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException as PropertyAccessInvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException as ValidatorUnexpectedTypeException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $env;

    /**
     * ApiExceptionSubscriber constructor.
     *
     * @param LoggerInterface $logger
     * @param $env
     */
    public function __construct(LoggerInterface $logger, string $env = 'prod')
    {
        $this->logger = $logger;
        $this->env = mb_strtolower($env);
    }

    /**
     * Gets the exception from event, and use an ApiProblem object to set a custom response.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();

        $apiProblem = ($e instanceof ApiProblemException)
            ? $e->getApiProblem()
            : $this->getApiProblem($e)
        ;

        $response = new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode()
        );
        $response->headers->set('Content-Type', 'application/problem+json');
        $event->setResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * Gets an ApiProblem object from exception.
     *
     * @param \Exception $e
     *
     * @return ApiProblem
     */
    private function getApiProblem(\Exception $e)
    {
        $this->logger->error($e->getMessage(), $e->getTrace());

        if ($e instanceof HttpException) {
            return new ApiProblem($e->getStatusCode(), $e->getMessage());
        } elseif ($e instanceof AuthenticationException) {
            return new ApiProblem(Response::HTTP_UNAUTHORIZED, $e->getMessage());
        } elseif ($e instanceof AccessDeniedException) {
            return new ApiProblem(Response::HTTP_FORBIDDEN, $e->getMessage());
        } elseif ($e instanceof ValidatorUnexpectedTypeException
            || $e instanceof PropertyAccessInvalidArgumentException
        ) {
            return new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::INVALID_DATA_SUBMITTED);
        }

        // Unexpected error
        $verbose = \in_array($this->env, ['dev', 'test'], true);

        return new ApiProblem(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $verbose ? $e->getMessage().' ('.\get_class($e).')' : ApiProblem::UNEXPECTED_ERROR,
            !$verbose
        );
    }
}
