<?php

namespace App\Controller\Users;

use App\Controller\ApiController;
use App\Entity\Users\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends ApiController
{
    /**
     * Get current.
     *
     * This method returns the current user authenticated, with full person data, if exists.
     *
     * @SWG\Tag(name="User")
     * @ApiSecurity(name="Bearer")
     * @SWG\Response(
     *     response=200,
     *     description="Successful operation",
     *     @Model(type=User::class, groups={"userEntity"})
     * )
     * @SWG\Response(response="401", ref="#/definitions/401")
     * @SWG\Response(response="405", ref="#/definitions/405")
     * @SWG\Response(response="415", ref="#/definitions/415")
     *
     * @Rest\View(serializerGroups={"userEntity"})
     * @Route("/users/me", name="api_users_get_one", methods={"GET"})
     *
     * @return User
     */
    public function getMe()
    {
        return $this->getUser();
    }

    /**
     * Build the form entity.
     *
     * @param mixed $entity
     * @param array $customOptions
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function buildForm($entity, array $customOptions = [])
    {
        // TODO: Implement buildForm() method.
    }
}
