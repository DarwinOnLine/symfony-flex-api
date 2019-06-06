<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Response;

/**
 * A wrapper for holding data to be used for a application/problem+json response.
 *
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-06
 */
class ApiProblem
{
    public const PREFIX = 'error.';
    public const WARNING = 'warning.';

    // region Error constants

    public const UNEXPECTED_ERROR = 'something.went.wrong';
    public const FORM_EXTRA_FIELDS_ERROR = 'form.extra_fields';

    public const INVALID_FORMAT = 'invalid.format.message';
    public const INVALID_DATA_SUBMITTED = 'invalid.data.submitted';
    public const ROUTE_NOT_FOUND = 'route.not_found';
    public const ENTITY_NOT_FOUND = '%s.not_found';
    public const ENTITY_NOT_ACCESSIBLE = '%s.not_accessible';

    public const UPLOAD_UNABLE_TO_WRITE_DIRECTORY = 'upload.unable.to.write.directory';
    public const UNABLE_TO_CREATE_ZIP_FILE = 'unable.to.create.zip_file';

    public const RESULT_ORDER_INCORRECT = 'order.incorrect_order';
    public const RESULT_SORT_MALFORMED = 'sort.malformed';

    public const PAGINATION_INCORRECT_PAGE_VALUE = 'pagination.incorrect_page_value';
    public const PAGINATION_INCORRECT_RESULT_PER_PAGE_VALUE = 'pagination.incorrect_results_per_page_value';

    public const MISSING_FIELD_REQUIRED = '%s.required';

    // region Entities

    public const ENTITY_DUPLICATED = '%s.duplicated';
    public const ENTITY_NOT_EDITABLE = '%s.not_editable';
    public const ENTITY_NOT_DELETABLE = '%s.not_deletable';

    public const ENTITY_FIELD_REQUIRED = '%s.%s.required';
    public const ENTITY_FIELD_AT_LEAST_REQUIRED = '%s.%s_or_%s.required';
    public const ENTITY_FIELD_MIN_VALUE = '%s.%s.min_value';
    public const ENTITY_FIELD_TOO_SHORT = '%s.%s.too_short';
    public const ENTITY_FIELD_TOO_LONG = '%s.%s.too_long';
    public const ENTITY_FIELD_TOO_MUCH = '%s.%s.too_much';
    public const ENTITY_FIELD_UNIQUE = '%s.%s.unique';
    public const ENTITY_FIELD_INVALID = '%s.%s.invalid';
    public const ENTITY_FIELD_DIFFERENT = '%s.%s.different';
    public const ENTITY_FIELD_XOR = '%s.%s_xor_%s';

    public const ENTITY_NOT_IN_YOUR_SCOPE = '%s.not_in_your_scope';

    // endregion

    // region Password

    public const PASSWORD_NEW_VALUE_MUST_BE_DIFFERENT = 'password.new_value_must_be_different';
    public const PASSWORD_TOO_SHORT = 'password.too_short';
    public const PASSWORD_SPACES_NOT_ALLOWED = 'password.spaces_not_allowed';
    public const PASSWORD_MISSING_LETTERS = 'password.missing_letters';
    public const PASSWORD_REQUIRE_CASE_DIFF = 'password.require_case_diff';
    public const PASSWORD_MISSING_NUMBERS = 'password.missing_numbers';
    public const PASSWORD_MISSING_SPECIAL_CHARS = 'password.missing_special_chars';
    public const PASSWORD_MAX_CHAR_OCCURRENCES = 'password.max_char_occurrences';

    // endregion

    // region JWT

    public const AUTHENTICATION_FAILURE = 'bad_credentials';
    public const RESTRICTED_ACCESS = 'restricted_access';
    public const CHANGE_PASSWORD_REQUIRED = 'change_password_required';
    public const JWT_INVALID = 'invalid_token';
    public const JWT_NOT_FOUND = 'missing_token';
    public const JWT_EXPIRED = 'token_expired';

    // endregion

    // region Custom errors

    // Custom errors here.

    // endregion

    // endregion

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $extraData = [];

    /**
     * ApiProblem constructor.
     *
     * @param int          $statusCode
     * @param string|array $errors
     * @param bool         $prefix
     */
    public function __construct(int $statusCode, $errors, bool $prefix = true)
    {
        $this->statusCode = $statusCode;
        if (!\is_array($errors)) {
            $errors = [$errors];
        }

        $this->normalizeErrors($statusCode, $errors, $prefix);
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(
            $this->extraData,
            [
                'errors' => $this->errors,
            ]
        );
    }

    /**
     * Set some extra data.
     *
     * @param $name
     * @param $value
     */
    public function set($name, $value): void
    {
        $this->extraData[$name] = $value;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Normalize error message.
     *
     * @param int   $statusCode
     * @param array $errors
     * @param bool  $prefix
     */
    private function normalizeErrors(int $statusCode, array $errors, bool $prefix): void
    {
        foreach ($errors as $error) {
            $this->errors[] = $this->normalizeError($statusCode, $error, $prefix);
        }
    }

    /**
     * Normalize error message.
     *
     * @param int    $statusCode
     * @param string $type
     * @param bool   $prefix
     *
     * @return string
     */
    private function normalizeError(int $statusCode, string $type, bool $prefix): string
    {
        // 400
        if (Response::HTTP_BAD_REQUEST === $statusCode) {
            if (preg_match('#^Invalid [a-zA-Z]+ message received$#', $type)) {
                $type = self::INVALID_FORMAT;
            }
            // 401
        } elseif (Response::HTTP_UNAUTHORIZED === $statusCode) {
            if (preg_match('#^A Token was not found in the TokenStorage#', $type)) {
                $type = self::JWT_NOT_FOUND;
            }
            // 403
        } elseif (Response::HTTP_FORBIDDEN === $statusCode) {
            if (preg_match('#^Token does not have the required roles#', $type)
                || preg_match('#^Access Denied.$#', $type)) {
                $type = self::RESTRICTED_ACCESS;
            }
            // 404
        } elseif (Response::HTTP_NOT_FOUND === $statusCode) {
            // Unknown entity ?
            if (preg_match('#^(.*\\\Entity\\\(.*)) object not found#', $type, $matches)) {
                $type = mb_strtolower(
                    sprintf(self::ENTITY_NOT_FOUND, ApiTools::normalizeClassName($matches[2]))
                );
                // Unknown route or resource
            } elseif (preg_match('#^No route found for#', $type)) {
                $type = self::ROUTE_NOT_FOUND;
            }
            // 405
        } elseif (Response::HTTP_METHOD_NOT_ALLOWED === $statusCode) {
            $type = self::ROUTE_NOT_FOUND; // Generic message :)
        }

        $this->statusCode = $statusCode;

        return $prefix ? self::PREFIX.$type : $type;
    }
}
