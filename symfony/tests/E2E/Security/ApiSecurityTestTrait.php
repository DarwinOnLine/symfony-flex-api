<?php

namespace App\Tests\E2E\Security;

use App\Utils\ApiOutput;
use Symfony\Component\HttpFoundation\Response;

trait ApiSecurityTestTrait
{
    /**
     * Expected fields for security items elements.
     *
     * @var array
     */
    public $securityAuthFields = ['token', 'refresh_token', 'user'];

    /**
     * Expected fields for security items elements.
     *
     * @var array
     */
    public $securityAuthUserFields = ['uuid', 'username', 'first_login', 'roles'];

    /**
     * Check the security auth.
     *
     * @param ApiOutput $apiOutput The API output
     */
    private function checkSecurityAuth(ApiOutput $apiOutput): void
    {
        static::assertApiEntityResult($apiOutput, Response::HTTP_OK, $this->securityAuthFields);
        $user = $apiOutput->getData();
        static::assertFields($this->securityAuthUserFields, $user['user']);
    }
}
