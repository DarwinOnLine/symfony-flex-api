<?php

namespace App\Tests\E2E\Security;

use App\Utils\ApiProblem;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\AbstractApiTest;

class ApiFosUserSecurityCheckTest extends AbstractApiTest
{
    use ApiSecurityTestTrait;

    /**
     * {@inheritdoc}
     */
    protected static $executeSetupOnAllTest = false;

    /**
     * {@inheritdoc}
     */
    protected static $executeCleanupOnAllTest = false;

    protected static $tokenRequired = false;

    /**
     * Nominal case.
     */
    public function testSuccess(): void
    {
        $apiOutput = self::httpPost('fos_user_security_check', [
            'username' => self::USER_TEST_USERNAME,
            'password' => self::USER_TEST_PASSWORD,
        ], false);

        $this->checkSecurityAuth($apiOutput);
    }

    /**
     * Error case - Bad credentials.
     */
    public function testFailureBadCredentials(): void
    {
        $apiOutput = self::httpPost('fos_user_security_check', [
            'username' => 'toto',
            'password' => 'tata',
        ], false);

        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::AUTHENTICATION_FAILURE]);
    }

    /**
     * Error case - 401 - Missing token.
     */
    public function testFailureMissingToken(): void
    {
        $apiOutput = self::httpGet('api_users_get_me', false);

        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_NOT_FOUND]);
    }

    /**
     * Error case - 401 - Invalid token.
     */
    public function testFailureInvalidToken(): void
    {
        // Force a bad token
        static::$token = 'IGuessItsAValidToken';
        $apiOutput = self::httpGet('api_users_get_me');

        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_INVALID]);

        // Discard that dirty token to get a good new one :)
        static::$token = null;
    }

    /**
     * Error case - 401 - Expired token.
     */
    public function testFailureExpiredToken(): void
    {
        // Force a valid but expired token
        static::$token = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkJlYXJlciJ9.eyJ1c2VybmFtZSI6IltBUEktVEVTVFNdIiwiZXhwIjoxNDgyNDgyNjk4LCJpYXQiOjE0ODI0ODI2OTd9.epnauWleRvHpa7uY9YQ-gGZKB8RZGT71svLbVKaC_QJYjawk-zsVlI402FhbDHXyCP8euaqO5YNJFJFC8GK3JOM_XYwIdeVngVAW0wPf9IwQYY36A0Pz9QzNW8FgJDMVy3jkTPm6zMdEhGPUlOHQmGM410ex8WV8_bbzKKMBpf-myeIgR7TMlEQM5KZfUPSWjDVOq0XCmrgJ8vpd-NSFC75i3dUlNpvcYZYT9Dwhvsnm21l-MaNG6xpwZPBjarBjguFWLPEFo0YvPd2ckmvSWGnowTODvVrQ4cpDcD7Cs2yA2HdBBHFfWrofF5ungZBGA71757MGGAPTo3-lAOy9HD_BJCx80ajvf1yMHQH8oUJUer60RduT_gvxHQyvAUfn3WJBLyvGjSuaYwgCpK_5kVnjWy5umINo8UpvVq3RZZOsjaV-iGc6QAZAKNW_DjKWMDDZshqhlUyKs9ZD8A1aFD4cnImAAlqcG4P2ocaE7HObpWhsdOKm0MfMPoYMynp1v4uO903XOWKpd4V5C2fdDCvsqHchHh1xiVqm8Y2D4QuBNcGV6gWOa0GazJRoV9DOOsNYV8uR--uvpImorRvg8wMkOhraT0R170hU_Y4waiPDkaPLOUVFKjHT-sje1Gp4_g7z33qnj4IrsEzysqe104D7hxQUSfQvlqSf371i1FQ","refresh_token":"456c3403b24baeccf1549dcf388a91bcbfedb5eac91cb8f0c62c044cd1535a1c29f788b7deef72098e7d58f708bd076260e254ec725a238953516c945a1f6dcc';
        $apiOutput = self::httpGet('api_users_get_me');

        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_EXPIRED]);

        // Discard that dirty token to get a good new one :)
        static::$token = null;
    }
}
