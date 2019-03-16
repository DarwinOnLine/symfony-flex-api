<?php

namespace App\Repository;

use App\Exception\ApiProblemException;
use App\Utils\ApiProblem;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract repository, provides some common functions and behaviors.
 */
abstract class AbstractRepository extends EntityRepository
{
    /**
     * Regex for sort param.
     *
     * @var string
     */
    const PARAM_SORT_REGEX = '#^(([a-zA-Z0-9\-\_]+\:(asc|desc))|([a-zA-Z0-9\-\_]+\:(asc|desc)\,)+([a-zA-Z0-9\-\_]+\:(asc|desc)))$#';

    /**
     * Default pagination limit.
     *
     * @var int
     */
    const DEFAULT_PAGINATION_LIMIT = 10;

    // region Fields

    /**
     * Result field : uuid.
     *
     * @var string
     */
    const FIELD_ID = 'uuid';

    // endregion

    protected $entityAlias;

    protected $availableSorts = [];
    protected $defaultSorts = [];

    /**
     * AbstractRepository constructor.
     * Entity alias definition.
     *
     * @param $em
     * @param ClassMetadata $class
     */
    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->entityAlias = uniqid('entity_');
    }

    /**
     * Get a valid UUID database value for queries.
     *
     * @param string $uuid
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return string
     */
    protected function convertUUIDFromStringToDBValue(string $uuid)
    {
        return Type::getType(UuidBinaryOrderedTimeType::NAME)->convertToDatabaseValue($uuid, $this->_em->getConnection()->getDatabasePlatform());
    }

    /**
     * Validates pagination params.
     *
     * @param int $page           Page, should be null OR > 0
     * @param int $resultsPerPage Number of results per page, should be null OR > 0, default is 10 if page given
     *
     * @throws ApiProblemException
     *
     * @return array with start and end offsets
     */
    protected function validatePagination(int $page = null, int $resultsPerPage = null)
    {
        if (null !== $page) {
            if (!preg_match('#^[0-9]*[1-9]\d*$#', $page)) {
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::PAGINATION_INCORRECT_PAGE_VALUE)
                );
            }
        }
        if (null !== $resultsPerPage) {
            if (!preg_match('#^[0-9]*[1-9]\d*$#', $resultsPerPage)) {
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::PAGINATION_INCORRECT_RESULT_PER_PAGE_VALUE)
                );
            }
        }

        $n = (null !== $resultsPerPage) ? $resultsPerPage : 0;
        $start = (null === $page) ? 0 : ($page - 1) * $n;

        return [$start, $n];
    }

    /**
     * Validates the sort param.
     *
     * @param array  $fields
     * @param string $sort
     *
     * @throws ApiProblemException
     *
     * @return array|null
     */
    protected function validateSort(array $fields, string $sort = null)
    {
        if (null === $sort) {
            return null;
        } elseif (preg_match(self::PARAM_SORT_REGEX, mb_strtolower($sort))) {
            $strOrders = preg_split('#\,#', $sort);
            $orders = [];
            foreach ($strOrders as $order) {
                $parts = preg_split('#\:#', $order);
                if (isset($fields[$parts[0]])) {
                    $orders[$fields[$parts[0]]] = $parts[1];
                } else {
                    throw new ApiProblemException(
                        new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::RESULT_ORDER_INCORRECT)
                    );
                }
            }

            return $orders;
        }

        throw new ApiProblemException(
            new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::RESULT_SORT_MALFORMED)
        );
    }

    /**
     * Apply sorts on query builder.
     *
     * @param $qb QueryBuilder      SQL query builder
     * @param $request Request      HTTP request
     *
     * @return array|null
     */
    protected function applySorts(QueryBuilder $qb, Request $request)
    {
        $orders = $this->validateSort($this->availableSorts, $request->get('sort'));
        $alias = current($qb->getRootAliases());

        // Order filters
        if (null !== $orders) {
            foreach ($orders as $order => $direction) {
                $this->applySort($qb, $alias, $order, $direction);
            }
        } elseif (!empty($this->defaultSorts)) {
            foreach ($this->defaultSorts as $order => $direction) {
                $this->applySort($qb, $alias, $order, $direction);
            }
        } else {
            $qb->orderBy($alias.'.uuid', 'ASC');
        }

        return $orders;
    }

    /**
     * Apply sort on query builder, make the joins if necessary.
     *
     * @param QueryBuilder $qb        SQL query builder
     * @param string       $alias     Current SQL alias
     * @param string       $order     Sort field order
     * @param string       $direction Sort direction
     */
    protected function applySort(QueryBuilder $qb, string $alias, string $order, string $direction)
    {
        $parts = preg_split('#\.#', $order);

        $orderAlias = $alias;
        if (($cParts = \count($parts)) > 1) {
            for ($i = 0; $i < $cParts - 1; ++$i) {
                $qb->leftJoin($orderAlias.'.'.$parts[$i], $orderAlias.'_'.$parts[$i]);
                $orderAlias .= '_'.$parts[$i];
            }
            $order = $parts[$cParts - 1];
            $qb->addSelect($orderAlias);
        }

        $qb->addOrderBy($orderAlias.'.'.$order, $direction);
    }

    /**
     * Finds entities by a set of criteria, with functions in conditions & orders.
     *
     * @param string     $alias
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array the objects
     */
    public function findByWithFunction(string $alias, array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder($alias);

        foreach ($criteria as $partLeft => $value) {
            $valueParam = 'value_'.uniqid();
            $qb->andWhere(sprintf('%s = :%s', $partLeft, $valueParam))
               ->setParameter($valueParam, $value)
            ;
        }
        if (null !== $orderBy) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy($sort, $order);
            }
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }
        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds a single entity by a set of criteria, with functions in conditions & orders.
     *
     * @param string     $alias
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return object|null the entity instance or NULL if the entity can not be found
     */
    public function findOneByWithFunction(string $alias, array $criteria, array $orderBy = null)
    {
        $qb = $this->createQueryBuilder($alias);

        foreach ($criteria as $partLeft => $value) {
            $valueParam = 'value_'.uniqid();
            $qb->andWhere(sprintf('%s = :%s', $partLeft, $valueParam))
                ->setParameter($valueParam, $value)
            ;
        }
        if (null !== $orderBy) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy($sort, $order);
            }
        }
        $qb->setMaxResults(1);

        $result = $qb->getQuery()->getResult();

        return !empty($result) ? current($result) : null;
    }

    /**
     * Performs a search.
     *
     * @param Request $request
     * @param array   $parameters
     *
     * @return array
     */
    public function search(Request $request, array $parameters = [])
    {
        list($start, $n) = $this->validatePagination($request->get('page'), $request->get('limit'));

        $qb = $this->createQueryBuilder($this->entityAlias)->distinct();

        $this->buildQuery($qb, $request, $parameters);

        if (0 !== $n) {
            $qb->setFirstResult($start)->setMaxResults($n);
        }
        $this->applySorts($qb, $request);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get COUNT of items.
     *
     * @param Request $request
     * @param array   $parameters
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return int
     */
    public function getCount(Request $request, array $parameters = [])
    {
        $qb = $this->createQueryBuilder($this->entityAlias)->select('COUNT(DISTINCT('.$this->entityAlias.'))');

        $this->buildQuery($qb, $request, $parameters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Build query from request.
     *
     * @param QueryBuilder $qb
     * @param Request      $request
     * @param array        $parameters
     */
    abstract protected function buildQuery(QueryBuilder $qb, Request $request, array $parameters);
}
