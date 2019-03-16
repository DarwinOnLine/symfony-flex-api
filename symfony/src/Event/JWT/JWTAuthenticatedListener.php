<?php

namespace App\Event\JWT;

use App\Entity\Users\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTAuthenticatedListener
{
    /**
     * @var string
     */
    private $jwtTokenTTL;

    /**
     * @var string
     */
    private $fallbackLocale;

    /**
     * JWTAuthenticatedListener constructor.
     *
     * @param string $jwtTokenTTL
     * @param string $fallbackLocale
     */
    public function __construct($jwtTokenTTL, $fallbackLocale)
    {
        $this->jwtTokenTTL = $jwtTokenTTL;
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        // No entity serialization (JsonResponse returned)
        $data['user'] = [
            'uuid' => $user->getUuid()->toString(),
            'username' => $user->getUsername(),
//            'name' => $user->getFullName(),
//            'profile' => $user->getProfileCode(),
            'first_login' => $user->isFirstConnection(),
//            'locale' => $user->getLocaleCode() ?? $this->fallbackLocale,
        ];
        $event->setData($data);
    }
}
