<?php

namespace App\Entity\Users;

use App\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as FOSUser;

/**
 * Users.
 *
 * @ORM\Table(
 *     name="users",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="uk_usr_confirmation_token", columns={"confirmation_token"}),
 *      @ORM\UniqueConstraint(name="uk_usr_email_canonical", columns={"email_canonical"}),
 *      @ORM\UniqueConstraint(name="uk_usr_username_canonical", columns={"username_canonical"}),
 *      @ORM\UniqueConstraint(name="uk_usr_uuid", columns={"uuid"})
 * })
 * @ORM\Entity
 */
class User extends FOSUser
{
    use EntityIdTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true,"comment"="Technical identifier"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var bool
     */
    private $firstConnection;

    /**
     * @return bool
     */
    public function isFirstConnection()
    {
        return $this->firstConnection;
    }

    /**
     * @param bool $firstConnection
     */
    public function setFirstConnection(bool $firstConnection)
    {
        $this->firstConnection = $firstConnection;
    }
}
