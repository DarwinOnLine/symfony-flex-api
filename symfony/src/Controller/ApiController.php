<?php

namespace App\Controller;

use App\Exception\ApiProblemException;
use App\Form\Serializer\FormErrorsSerializer;
use App\Repository\AbstractRepository;
use App\Utils\ApiProblem;
use App\Utils\ApiTools;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiController extends AbstractFOSRestController
{
    /**
     * Original data (update purposes).
     *
     * @var array
     */
    protected $originalData = [];

    /**
     * Performs a count.
     *
     * @param Request $request
     * @param string  $entityClassName Entity class name
     * @param array   $parameters      Query parameters
     *
     * @return array
     */
    protected function doGetCount(Request $request, string $entityClassName, array $parameters = [])
    {
        return [
            'count' => $this->getDoctrine()->getManager()
                ->getRepository($entityClassName)
                ->getCount($request, $parameters),
        ];
    }

    /**
     * Performs a search.
     *
     * @param string  $entityClassName Entity class name
     * @param Request $request         HTTP request
     * @param array   $parameters      Query parameters
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return array
     */
    protected function doSearch(string $entityClassName, Request $request, array $parameters = [])
    {
        /** @var AbstractRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository($entityClassName);

        return [
            'items' => $repository->search($request, $parameters),
            'total' => $repository->getCount($request, $parameters),
        ];
    }

    /**
     * Performs a creation.
     *
     * @param string  $entityClassName Entity class name
     * @param Request $request         HTTP request
     * @param array   $customOptions   Custom options for the form
     *
     * @return View
     */
    protected function doCreate(string $entityClassName, Request $request, array $customOptions = [])
    {
        return $this->doSave(new $entityClassName(), $request, $customOptions, true);
    }

    /**
     * Performs an update.
     *
     * @param mixed   $entity        Entity to update
     * @param Request $request       HTTP request
     * @param array   $customOptions Custom options for the form
     *
     * @return View
     */
    protected function doUpdate($entity, Request $request, array $customOptions = [])
    {
        return $this->doSave($entity, $request, $customOptions);
    }

    /**
     * Performs an entity save.
     *
     * @param mixed   $entity        Entity to save
     * @param Request $request       HTTP request
     * @param array   $customOptions Custom options for the form
     * @param bool    $creation      Creation mode
     *
     * @return View
     */
    protected function doSave($entity, Request $request, array $customOptions = [], $creation = false)
    {
        if (!$creation) {
            $this->storeOriginalData($entity);
        }

        $form = $this->buildForm($entity, $customOptions);
        $form->submit($request->request->all(), $creation);

        if ($form->isValid()) {
            $this->entityPrePersist($entity, $request, $creation);

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->entityPostPersist($entity, $request, $creation);

            return $this->view($entity, $creation ? Response::HTTP_CREATED : Response::HTTP_OK);
        }

        throw new ApiProblemException(
            new ApiProblem(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $this->get(FormErrorsSerializer::class)->serializeFormErrors($form, true, true)
            )
        );
    }

    /**
     * Build the form entity.
     *
     * @param mixed $entity
     * @param array $customOptions
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    abstract protected function buildForm($entity, array $customOptions = []);

    /**
     * Store original data before update.
     *
     * @param mixed $entity Entity to save
     */
    protected function storeOriginalData($entity)
    {
    }

    /**
     * Do some stuff on the entity or something else before persist.
     *
     * @param mixed   $entity   Entity to save
     * @param Request $request  HTTP request
     * @param bool    $creation Creation mode
     */
    protected function entityPrePersist($entity, Request $request, $creation = false)
    {
    }

    /**
     * Do some stuff on the entity or something else after persist.
     *
     * @param mixed   $entity   Entity to save
     * @param Request $request  HTTP request
     * @param bool    $creation Creation mode
     */
    protected function entityPostPersist($entity, Request $request, $creation = false)
    {
    }

    /**
     * Performs a delete.
     *
     * @param mixed $entity     Entity to delete
     * @param bool  $returnView
     *
     * @return bool|View
     */
    protected function doDelete($entity, $returnView = true)
    {
        $entityClassName = \get_class($entity);

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        try {
            $em->remove($entity);
            $em->flush();

            return $returnView ? $this->view(null, Response::HTTP_NO_CONTENT) : true;
        } catch (\Exception $e) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_BAD_REQUEST,
                    sprintf(ApiProblem::ENTITY_NOT_DELETABLE, ApiTools::normalizeEntityClassName($entityClassName))
                )
            );
        }
    }
}
