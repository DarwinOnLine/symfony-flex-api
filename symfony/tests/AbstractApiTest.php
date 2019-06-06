<?php

namespace App\Tests;

use App\Utils\ApiFormat;
use App\Utils\ApiOutput;
use App\Utils\ApiProblem;
use App\Utils\FileBag;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractApiTest extends WebTestCase
{
    use ApiTestTrait;

    // region Constants

    public const USER_TEST_USERNAME = '[API-TESTS]';
    public const USER_TEST_EMAIL = 'api-tests@example.com';
    public const USER_TEST_PASSWORD = 'IloveToBreakYourH0pes!';

    public const TOKEN_ROUTE_NAME = 'fos_user_security_check';

    public const DEBUG_LEVEL_SIMPLE = 1;
    public const DEBUG_LEVEL_ADVANCED = 2;

    // endregion

    // region Settings

    /**
     * @var bool
     */
    protected static $debug = false;

    /**
     * @var int
     */
    protected static $debugLevel = self::DEBUG_LEVEL_SIMPLE;

    /**
     * @var int
     */
    protected static $debugTop = 1;

    /**
     * Indicates if you want launch setup on all tests in your test class.
     *
     * @var bool
     */
    protected static $executeSetupOnAllTest = true;

    /**
     * Indicates if you want launch cleanup on all tests in your test class.
     *
     * @var bool
     */
    protected static $executeCleanupOnAllTest = true;

    /**
     * Indicates if the first launch need to launch.
     *
     * @var bool
     */
    protected static $launchFirstSetup = true;

    // endregion

    // region Parameters

    /**
     * User API username.
     *
     * @var string
     */
    protected static $user = self::USER_TEST_USERNAME;

    /**
     * User API password.
     *
     * @var string
     */
    protected static $password = self::USER_TEST_PASSWORD;

    /**
     * User API token.
     *
     * @var string
     */
    protected static $token;

    // endregion

    // region Utils

    /**
     * simulates a browser and makes requests to a Kernel object.
     *
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected static $entityManager;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected static $router;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected static $filesystem;

    /**
     * @var string
     */
    protected static $databasePlatformName;

    /**
     * Container for the names of the files created during tests.
     *
     * @var array
     */
    protected static $filesCreated = [];

    /**
     * Writing folders, to manage for tests.
     *
     * @var array
     */
    protected static $writingFolders = [];

    /**
     * List fields.
     *
     * @var array
     */
    protected static $listFields = ['items', 'total'];

    /**
     * Kernel directory.
     *
     * @var string
     */
    protected static $kernelDir;

    /**
     * Entity code.
     *
     * @var string
     */
    protected static $entityCode;

    /**
     * Entity total.
     *
     * @var int
     */
    protected static $entityTotal;

    /**
     * Closure to execute some actions after initialization.
     *
     * @var \Closure
     */
    protected static $actionsPostInitialize;

    /**
     * Check if engine is initialized.
     *
     * @return bool
     */
    final protected static function isInitialized(): bool
    {
        return
            null !== self::$client
            && null !== self::$entityManager
            && null !== self::$router
            ;
    }

    /**
     * Initialize engine.
     *
     * @throws \Exception
     */
    final protected static function initialize(): void
    {
        self::logStep();
        self::initializeKernel();

        global $argv;
        if (\in_array('--debug', $argv, true)) {
            self::$debug = true;
        }

        self::setWritingFoldersReadable();

        self::executeSQLScript('reset-all.sql');
    }

    /**
     * Initialize engine.
     *
     * @throws \Exception
     */
    final protected static function initializeKernel(): void
    {
        self::$client = self::createClient(['debug' => false]);
        self::$kernelDir = self::$container->getParameter('kernel.project_dir');
        self::$filesystem = self::$container->get('filesystem');
        self::$entityManager = self::$container->get('doctrine.orm.entity_manager');
        self::$databasePlatformName = self::$container->get('doctrine')->getConnection()->getDatabasePlatform()->getName();
        self::$router = self::$container->get('router');
    }

    /**
     * Sets writing folders readable.
     *
     * @throws \Exception
     */
    protected static function setWritingFoldersReadable(): void
    {
        if (!empty(static::$writingFolders)) {
            foreach (static::$writingFolders as $folder) {
                self::$filesystem->chmod($folder, 0777);
            }
        }
    }

    /**
     * Run a Symfony command.
     *
     * @param string $commandName The command name
     * @param array  $attributes  Command attributes
     *
     * @throws \Exception
     *
     * @return string
     */
    final protected static function runCommand(string $commandName, array $attributes = []): string
    {
        $application = new Application(static::createKernel());
        $command = $application->find($commandName);
        $commandTester = new CommandTester($command);

        $commandTester->execute(array_merge([
            'command' => $command->getName(),
        ], $attributes));

        $output = $commandTester->getDisplay();

        self::logDebug(
            sprintf(
                "\e[33m[CMD]\e[0m\t⚙ Run \e[34m%s\e[0m\nResponse : \n%s",
                $command->getName(),
                $output
            )
        );

        self::initializeKernel();

        return $output;
    }

    /**
     * Show where you are (Class::method()).
     *
     * @param bool $debugNewLine Adds a new line before debug log
     */
    final protected static function logStep(bool $debugNewLine = false): void
    {
        if (true === static::$debug) {
            $backTrace = debug_backtrace()[1];
            self::logDebug(
                sprintf("\e[42;31m[STEP]\e[0m 👁️ \e[92m%s::%s()\e[0m",
                    $backTrace['class'], $backTrace['function']
                ),
                self::DEBUG_LEVEL_ADVANCED, $debugNewLine
            );
        }
    }

    /**
     * Show a debug line, if debug activated.
     *
     * @param string $message      The message to log
     * @param int    $debugLevel   Debug level
     * @param bool   $debugNewLine Adds a new line before debug log
     * @param string $startWith    Symbol to start the line with
     */
    final protected static function logDebug(string $message, int $debugLevel = self::DEBUG_LEVEL_SIMPLE, bool $debugNewLine = false, string $startWith = '🐞'): void
    {
        if (true === static::$debug && $debugLevel <= static::$debugLevel) {
            fwrite(STDOUT, sprintf(
                "%s\e[33m%s%s\e[0m %s\n",
                $debugNewLine ? "\n" : '',
                $startWith,
                (self::DEBUG_LEVEL_ADVANCED === static::$debugLevel) ? ' ['.str_pad(self::$debugTop++, 5, '0', STR_PAD_LEFT).']' : '',
                $message));
        }
    }

    /**
     * Show an error line.
     *
     * @param $message
     */
    final protected static function logError(string $message): void
    {
        fwrite(STDOUT, sprintf("\e[31m✘\e[91m %s\e[0m\n", $message));
    }

    /**
     * Execute some SQL statements (Tests purposes ONLY), giving SQL test filename.
     *
     * @param string $filename     SQL script filename
     * @param bool   $debugNewLine Adds a new line before debug log
     *
     * @throws \Exception
     */
    final protected static function executeSQLScript(string $filename, bool $debugNewLine = false): void
    {
        $sql = file_get_contents(
            self::$kernelDir.
            \DIRECTORY_SEPARATOR.'tests'.
            \DIRECTORY_SEPARATOR.'sql'.
            \DIRECTORY_SEPARATOR.self::$databasePlatformName.
            \DIRECTORY_SEPARATOR.$filename
        );  // Read file contents
        self::logDebug(sprintf("\e[32m[SQL]\e[0m ▶ \e[32m%s\e[0m", $filename), self::DEBUG_LEVEL_SIMPLE, $debugNewLine);
        self::executeSQLQuery($sql, $debugNewLine);
    }

    /**
     * Execute SQL query (Tests purposes ONLY), giving SQL query.
     *
     * @param string $query        SQL query
     * @param bool   $debugNewLine Adds a new line before debug log
     * @param bool   $showQuery    Show query (debug mode)
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    final protected static function executeSQLQuery(string $query, bool $debugNewLine = false, bool $showQuery = false): void
    {
        if ($showQuery) {
            self::logDebug(sprintf("\e[32m[SQL]\e[0m ▶ \e[32m%s\e[0m", $query), self::DEBUG_LEVEL_ADVANCED);
        }
        try {
            self::logDebug(
                sprintf(
                    "\t\t🎌 \e[32m%d\e[0m affected rows",
                    self::$entityManager->getConnection()->exec(self::prepareQuery($query)) // Execute native SQL
                ),
                self::DEBUG_LEVEL_ADVANCED, $debugNewLine
            );
        } catch (\Exception $e) {
            self::logError($e->getMessage());
            // STOP
            die(E_CORE_ERROR);
        }

        self::$entityManager->flush();
    }

    /**
     * Prepare a SQL query before execution.
     *
     * @param string $query the query to prepare
     *
     * @return string
     */
    final protected static function prepareQuery(string $query): string
    {
        if ('oci8' === self::$databasePlatformName) {
            return sprintf("BEGIN\n%s\nEND;", $query);
        }

        return $query;
    }

    /**
     * Count entities.
     *
     * @param string $entityName
     * @param string $condition
     * @param array  $parameters
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return int
     */
    final protected static function countEntities(string $entityName, $condition = null, $parameters = []): int
    {
        $qb = self::$entityManager->getRepository($entityName)
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
        ;
        if (null !== $condition) {
            $qb->where($condition);
        }
        if (null !== $parameters && !empty($parameters)) {
            $qb->setParameters($parameters);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $className
     *
     * @return int
     */
    protected static function getLastEntityId($className): int
    {
        $entity = self::$entityManager->getRepository($className)->findOneBy([], ['id' => 'DESC']);

        return $entity ? $entity->getId() : null;
    }

    /**
     * @param $className
     *
     * @return int|null
     */
    protected static function getNextEntityId($className): ?int
    {
        return ($id = self::getLastEntityId($className)) ? ++$id : null;
    }

    // endregion

    // region User management

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        self::logStep();
        self::doSetup();

        self::$launchFirstSetup = false;

        if (\is_callable(static::$actionsPostInitialize)) {
            \call_user_func(static::$actionsPostInitialize);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function setUp()
    {
        $testName = $this->getName();
        if ('test403' === $testName && !static::$requiredProfile) {
            static::markTestSkipped('No profile required.');
        } elseif ('test401' === $testName && !static::$tokenRequired) {
            static::markTestSkipped('No token required.');
        }

        self::logStep();
        if (true === static::$executeSetupOnAllTest && true === static::$launchFirstSetup) {
            self::doSetup();
        } elseif (true === static::$launchFirstSetup) {
            // If no reset rollback user test & its rights
            self::defineUserPassword();
        }
        static::$launchFirstSetup = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function tearDown()
    {
        self::logStep();
        if (true === static::$executeCleanupOnAllTest) {
            self::doCleanup();
        }
        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public static function tearDownAfterClass()
    {
        self::logStep();
        if (false === static::$executeCleanupOnAllTest) {
            self::doCleanup();
        }
        self::$executeSetupOnAllTest = true;
        self::$executeCleanupOnAllTest = true;

        self::$token = null;
    }

    /**
     * Performs setup operations.
     *
     * @throws \Exception
     */
    final protected static function doSetup(): void
    {
        self::logStep();
        if (!self::isInitialized()) {
            self::initialize();
        }
        self::executeSQLScript('init.sql');
    }

    /**
     * Performs cleanup operations.
     *
     * @throws \Exception
     */
    final protected static function doCleanup(): void
    {
        self::logStep();
        if (self::isInitialized()) {
            self::executeSQLScript('reset-all.sql');

            self::setWritingFoldersReadable();
            if (!empty(self::$filesCreated)) {
                self::logDebug("\e[33m[SYS]\e[0m\t🗑️ \e[31mRemove\e[0m file(s) : ".implode(',', self::$filesCreated));
                self::$filesystem->remove(self::$filesCreated);
                self::$filesCreated = [];
            }
        }
        self::defineUserPassword();
    }

    /**
     * Define user & password for tests.
     *
     * @param string|null $user
     * @param string|null $password
     */
    protected static function defineUserPassword($user = null, $password = null): void
    {
        self::logStep();
        if (!self::$user || (!$user && !$password)) {
            if (self::USER_TEST_USERNAME === self::$user) {
                // No need to change that.
                return;
            }
            self::$user = self::USER_TEST_USERNAME;
            self::$password = self::USER_TEST_PASSWORD;
        } else {
            self::$user = $user;
            self::$password = $password;
        }
        self::logDebug(sprintf("\e[32m[USR]\e[0m😀 User is now : \e[32m%s\e[0m with password \e[32m%s\e[0m", self::$user, self::$password));

        self::$token = null;
    }

    // endregion

    // region Requests management

    /**
     * Login via API with a specific user and password.
     *
     * @param string $username
     * @param string $password
     *
     * @throws \LogicException
     *
     * @return string
     */
    protected static function loginHttp(string $username, string $password): string
    {
        $credentials = [
            'username' => $username,
            'password' => $password,
        ];

        $response = self::executeRequest('POST', 'fos_user_security_check', $credentials, false);
        $tokenAuth = $response->getData();

        if (null === $tokenAuth) {
            throw new \LogicException('Tests : Token is null');
        }
        if (Response::HTTP_UNAUTHORIZED === $response->getResponse()->getStatusCode()) {
            self::logError('Unable to get token : Bad credentials');
            throw new \LogicException('Tests : Unable to get token : Bad credentials');
        }
        self::logDebug(sprintf("\e[35m[TKN]\e[0m\t\e[92m✔\e[0m Generated Token : \e[35m%s\e[0m", $tokenAuth['token']));

        return $tokenAuth['token'];
    }

    /**
     * Get authentication token.
     *
     * @throws \LogicException
     *
     * @return string
     */
    protected static function getToken(): string
    {
        if (null === static::$token) {
            static::$token = self::loginHttp(self::$user, self::$password);
        }

        return static::$token;
    }

    /**
     * Executes HEAD request for an URL with a token to get.
     *
     * @param string|array $route            Route to perform the GET
     * @param bool         $withToken        Defines if a token is required or not (need to login first)
     * @param array        $extraHttpHeaders Extra HTTP headers to use (can override Accept and Content-Type
     *                                       defined by formatIn and formatOut if necessary)
     *
     * @return ApiOutput
     */
    public static function httpHead($route, bool $withToken = true, array $extraHttpHeaders = []): ApiOutput
    {
        return self::executeRequest('HEAD', $route, null, $withToken, null, null, $extraHttpHeaders);
    }

    /**
     * Executes GET request for an URL with a token to get.
     *
     * @param string|array $route            Route to perform the GET
     * @param bool         $withToken        Defines if a token is required or not (need to login first)
     * @param string       $formatOut        Output data format <=> Accept header (Default : JSON)
     * @param array        $extraHttpHeaders Extra HTTP headers to use (can override Accept and Content-Type
     *                                       defined by formatIn and formatOut if necessary)
     *
     * @return ApiOutput
     */
    public static function httpGet($route, bool $withToken = true,
                                   $formatOut = ApiFormat::JSON, array $extraHttpHeaders = []): ApiOutput
    {
        return self::executeRequest('GET', $route, null, $withToken, null, $formatOut, $extraHttpHeaders);
    }

    /**
     * Executes POST request for an URL with a token to get.
     *
     * @param string|array $route            Route to perform the POST
     * @param mixed        $content          Content to submit
     * @param bool         $withToken        Defines if a token is required or not (need to login first)
     * @param string       $formatIn         Input data format <=> Content-type header (Default : JSON)
     * @param string       $formatOut        Output data format <=> Accept header (Default : JSON)
     * @param array        $extraHttpHeaders Extra HTTP headers to use (can override Accept and Content-Type
     *                                       defined by formatIn and formatOut if necessary)
     *
     * @return ApiOutput
     */
    public static function httpPost($route, $content = [], bool $withToken = true,
                                    $formatIn = ApiFormat::JSON, $formatOut = ApiFormat::JSON, array $extraHttpHeaders = []): ApiOutput
    {
        return self::executeRequest('POST', $route, $content, $withToken, $formatIn, $formatOut, $extraHttpHeaders);
    }

    /**
     * Executes PUT request for an URL with a token to get.
     *
     * @param string|array $route            Route to perform the POST
     * @param mixed        $content          Content to submit
     * @param bool         $withToken        Defines if a token is required or not (need to login first)
     * @param string       $formatIn         Input data format <=> Content-type header (Default : JSON)
     * @param string       $formatOut        Output data format <=> Accept header (Default : JSON)
     * @param array        $extraHttpHeaders Extra HTTP headers to use (can override Accept and Content-Type
     *                                       defined by formatIn and formatOut if necessary)
     *
     * @return ApiOutput
     */
    public static function httpPut($route, $content = [], bool $withToken = true,
                                   $formatIn = ApiFormat::JSON, $formatOut = ApiFormat::JSON, array $extraHttpHeaders = []): ApiOutput
    {
        return self::executeRequest('PUT', $route, $content, $withToken, $formatIn, $formatOut, $extraHttpHeaders);
    }

    /**
     * Executes DELETE request for an URL with a token to get.
     *
     * @param string|array $route     Route to perform the DELETE
     * @param bool         $withToken Defines if a token is required or not (need to login first)
     *
     * @return ApiOutput
     */
    public static function httpDelete($route, bool $withToken = true): ApiOutput
    {
        return self::executeRequest('DELETE', $route, null, $withToken);
    }

    /**
     * Executes a request with a method, an url, a token, a content body and a format.
     *
     * @param string       $method           HTTP method
     * @param string|array $route            Route to call
     * @param mixed        $content          Content body if needed
     * @param bool         $withToken        Defines if a token is required or not (need to login first)
     * @param string       $formatIn         Input data format <=> Content-type header, see {@link Format} (Default : JSON)
     * @param string       $formatOut        Output data format <=> Accept header, see {@link Format} (Default : JSON)
     * @param array        $extraHttpHeaders Extra HTTP headers to use (can override Accept and Content-Type
     *                                       defined by formatIn and formatOut if necessary)
     *
     * @return ApiOutput
     */
    public static function executeRequest(string $method, $route, $content = null, bool $withToken = true,
                                          $formatIn = ApiFormat::JSON, $formatOut = ApiFormat::JSON,
                                          array $extraHttpHeaders = []): ApiOutput
    {
        //Headers initialization
        $server = [];
        if (null !== $formatIn && !($content instanceof FileBag)) {
            $server['CONTENT_TYPE'] = $formatIn;
        }
        if (null !== $formatOut) {
            $server['HTTP_ACCEPT'] = $formatOut;
        }
        foreach ($extraHttpHeaders as $key => $value) {
            if ('content-type' === mb_strtolower($key)) {
                $server['CONTENT_TYPE'] = $value;

                continue;
            }

            $server['HTTP_'.mb_strtoupper(str_replace('-', '_', $key))] = $value;
        }

        $url = \is_string($route) && 0 === mb_strpos($route, 'http') ? $route : self::getUrl($route);

        // Token
        if (true === $withToken) {
            $server['HTTP_AUTHORIZATION'] = sprintf(
                '%s %s',
                $_ENV['JWT_TOKEN_AUTHORIZATION_HEADER_PREFIX'],
                self::getToken()
            );
        }

        // Body
        $body = null !== $content && !($content instanceof FileBag) ? ApiFormat::writeData($content, $formatIn) : null;
        $files = ($content instanceof FileBag) ? $content->getData() : [];

        self::$client->request($method, $url, [], $files, $server, $body);
        $output = new ApiOutput(self::$client->getResponse(), $formatOut);

        self::logDebug(
            sprintf(
                "\e[33m[API]\e[0m\t🌐 [\e[33m%s\e[0m]%s\e[34m%s\e[0m%s%s%s\n\t\t\tStatus : \e[33m%d\e[0m\n\t\t\tResponse : \e[33m%s\e[0m",
                mb_strtoupper($method),
                mb_strlen($method) > 3 ? "\t" : "\t\t",
                $url,
                (true === $withToken && self::DEBUG_LEVEL_ADVANCED === static::$debugLevel) ? sprintf(
                    "\n\t\t\tToken : \e[33m%s\e[0m",
                    $server['HTTP_AUTHORIZATION']
                ) : '',
                (null !== $body && self::DEBUG_LEVEL_ADVANCED === static::$debugLevel) ? sprintf(
                    "\n\t\t\tSubmitted data : \e[33m%s\e[0m",
                    $body
                ) : '',
                self::DEBUG_LEVEL_ADVANCED === static::$debugLevel ? sprintf(
                    "\n\t\t\tHeaders : \e[33m%s\e[0m",
                    array_reduce(array_keys($server), function ($carry, $key) use ($server) {
                        return "{$carry}\n\t\t\t\t- {$key} = ".$server[$key];
                    })
                ) : '',
                $output->getResponse()->getStatusCode(),
                $output->getData(true)
            )
        );

        return $output;
    }

    /**
     * Gets URI from Symfony route.
     *
     * @param string|array $route
     *
     * @return string
     */
    protected static function getUrl($route): string
    {
        if (\is_array($route)) {
            $routeName = $route['name'] ?? '';
            $routeParams = $route['params'] ?? [];
            $url = $route['url'] ?? null;
        } else {
            $routeName = $route;
            $routeParams = [];
            $url = null;
        }

        return $url ?? self::$router->generate(
                $routeName,
                $routeParams,
                UrlGeneratorInterface::ABSOLUTE_URL
            );
    }

    /**
     * Get FileBag for the filename.
     *
     * @param array $filenames
     *
     * @return FileBag
     */
    protected function getFileBag(array $filenames): FileBag
    {
        $fileDir = self::$kernelDir.
            \DIRECTORY_SEPARATOR.'tests'.
            \DIRECTORY_SEPARATOR.'artifacts'
        ;
        $fileBag = new FileBag();
        foreach ($filenames as $field => $filename) {
            $fileBag->addFile($field, $fileDir.\DIRECTORY_SEPARATOR.$filename, true, $filename);
        }

        return $fileBag;
    }

    // endregion

    // region Assertions

    /**
     * Determine if two arrays are similar.
     *
     * @param array         $a
     * @param array         $b
     * @param callable|null $callbackFunction
     * @param string        $message
     */
    protected static function assertArraysAreSimilar(array $a, array $b, callable $callbackFunction = null, $message = ''): void
    {
        if ($callbackFunction) {
            usort($a, $callbackFunction);
            usort($b, $callbackFunction);
        } else {
            sort($a);
            sort($b);
        }

        static::assertSame($a, $b, $message);
    }

    /**
     * Determine if two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array $a
     * @param array $b
     */
    protected static function assertAssociativeArraysAreSimilar(array $a, array $b): void
    {
        // Indexes must match
        static::assertCount(\count($a), $b, 'The array have not the same size');

        // Compare values
        foreach ($a as $k => $v) {
            static::assertTrue(\array_key_exists($k, $b), sprintf('The second array have not the key "%s"', $k));
            if (\is_array($v)) {
                static::assertArraysAreSimilar($v, $b[$k]);
            } else {
                static::assertSame($v, $b[$k], sprintf('Values for "%s" key do not match', $k));
            }
        }
    }

    /**
     * Asserts an API problem standard error.
     *
     * @param int       $expectedStatus
     * @param array     $messages
     * @param ApiOutput $apiOutput
     */
    protected static function assertApiProblemError(ApiOutput $apiOutput, int $expectedStatus, array $messages): void
    {
        static::assertSame($expectedStatus, $apiOutput->getResponse()->getStatusCode());
        $error = $apiOutput->getData();
        static::assertArrayHasKey('errors', $error);
        array_walk($messages, function (&$message) { $message = ApiProblem::PREFIX.$message; });
        static::assertArraysAreSimilar($messages, $error['errors']);
    }

    /**
     * Asserts an API entity standard result.
     *
     * @param ApiOutput $apiOutput      API output
     * @param int       $expectedStatus Expected status
     * @param array     $fields         Expected fields
     * @param bool      $atLeast        Entity can have more fields?
     */
    protected static function assertApiEntityResult(ApiOutput $apiOutput, int $expectedStatus, array $fields, bool $atLeast = false): void
    {
        static::assertSame($expectedStatus, $apiOutput->getResponse()->getStatusCode());
        static::assertFields($fields, $apiOutput->getData(), $atLeast);
    }

    /**
     * @param ApiOutput $apiOutput     API output
     * @param int       $expectedCount Expected count
     */
    protected static function assertApiCountResult(ApiOutput $apiOutput, int $expectedCount): void
    {
        self::assertApiEntityResult($apiOutput, Response::HTTP_OK, ['count']);
        self::assertSame($expectedCount, $apiOutput->getData()['count']);
    }

    /**
     * Asserts an API entity standard result.
     *
     * @param ApiOutput $apiOutput      API output
     * @param int       $expectedStatus Expected status
     * @param int       $count          List count
     * @param int       $total          Entity total
     * @param array     $fields         Expected fields
     * @param bool      $atLeast        Each entity can have more fields?
     */
    protected static function assertApiEntityListResult(ApiOutput $apiOutput, int $expectedStatus, int $count, int $total, array $fields, bool $atLeast = false): void
    {
        static::assertSame($expectedStatus, $apiOutput->getResponse()->getStatusCode());
        $data = $apiOutput->getData();
        static::assertFields(static::$listFields, $data);
        static::assertCount($count, $data['items'], sprintf('Expected list size : %d, get %d', $count, \count($data['items'])));
        static::assertSame($total, $data['total'], sprintf('Expected total : %d, get %d', $total, $data['total']));
        foreach ($data['items'] as $entity) {
            static::assertFields($fields, $entity, $atLeast);
        }
    }

    /**
     * Asserts that entity contains exactly theses fields.
     *
     * @param array $fields  Expected fields
     * @param array $entity  JSON entity as array
     * @param bool  $atLeast The entity can have more fields?
     */
    protected static function assertFields(array $fields, array $entity, bool $atLeast = false): void
    {
        static::assertNotNull($entity, 'The entity should not be null !');
        if (!$atLeast) {
            static::assertCount(\count($fields), $entity, sprintf('Expected field count : %d, get %d', \count($fields), \count($entity)));
        }
        foreach ($fields as $field) {
            static::assertArrayHasKey($field, $entity, sprintf('Entity must have this field : %s', $field));
        }
    }

    /**
     * Asserts that the date is ISO 8601 formatted.
     *
     * @param string $date The date as string
     */
    protected static function assertISO8601Date(string $date): void
    {
        static::assertRegExp('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|[+-](?:2[0-3]|[01][0-9])(?::?(?:[0-5][0-9]))?)$/', $date, 'The date is not ISO 8601 compliant');
    }

    /**
     * Check persistence after POST.
     *
     * @param string $route          GET route
     * @param array  $dataOutPost    Data output from POST request
     * @param array  $expectedFields Expected fields
     */
    protected static function checkPersistence(string $route, array $dataOutPost, array $expectedFields): void
    {
        $apiOutputGetOne = self::httpGet([
            'name' => $route,
            'params' => ['uuid' => $dataOutPost['uuid']],
        ]);
        self::assertApiEntityResult($apiOutputGetOne, Response::HTTP_OK, $expectedFields);
        self::assertAssociativeArraysAreSimilar($dataOutPost, $apiOutputGetOne->getData());
    }

    // endregion

    // region Automatic tests

    /**
     * Tested route.
     *
     * @var array|string
     */
    protected static $testedRoute;

    /**
     * HTTP method.
     *
     * @var string
     */
    protected static $currentMethod;

    /**
     * Required API profile.
     *
     * @var string
     */
    protected static $requiredProfile;

    /**
     * Indicates if the $token is required.
     *
     * @var bool
     */
    protected static $tokenRequired = true;

    /**
     * Error case - No token.
     *
     * @throws \LogicException
     */
    public function test401(): void
    {
        if (null === static::$testedRoute) {
            throw new \LogicException('Please define static parameter "$testedRoute" !');
        }
        if (null === static::$currentMethod) {
            throw new \LogicException('Please define static parameter "$currentMethod" !');
        }
        $apiOutput = self::executeRequest(static::$currentMethod, static::$testedRoute, null, false);
        static::assertApiProblemError($apiOutput, Response::HTTP_UNAUTHORIZED, [ApiProblem::JWT_NOT_FOUND]);
    }

    /**
     * Error case - 403 - No required profile for it.
     */
    public function test403(): void
    {
        // Demote user
        // (change role, profile, whatever)

        $apiOutput = self::executeRequest(static::$currentMethod, static::$testedRoute);
        static::assertApiProblemError($apiOutput, Response::HTTP_FORBIDDEN, [ApiProblem::RESTRICTED_ACCESS]);

        // Restore admin profile
        // (initial role, profile, whatever)
    }

    // endregion
}
