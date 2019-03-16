<?php

namespace App\Request\ParamConverter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Convert an array of entities from request.
 *
 * @author Matthieu Poignant <matthieu@highlight.pro>
 */
class ArrayCollectionConverter implements ParamConverterInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $objectManager;

    /**
     * @var array
     */
    private $defaultOptions = [
        'entity_class' => null,
        'identifier' => 'id',
        'delimiter' => ',',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $name = $configuration->getName();
        $options = $this->getOptions($configuration);

        // If request parameter is missing or empty then return
        if (null === ($val = $request->get($name)) || 0 === mb_strlen(trim($val))) {
            return false;
        }

        // If splitted values is an empty array then return
        if (!($items = preg_split('/\s*'.$options['delimiter'].'\s*/', $val,
            0, PREG_SPLIT_NO_EMPTY))) {
            return false;
        }

        $this->getManager($options['entity_class']);

        /** @var EntityRepository $repository */
        $repository = $this->objectManager->getRepository($options['entity_class']);

        $collection = new ArrayCollection();
        foreach ($items as $item) {
            if ($entity = $repository->findOneBy([$options['identifier'] => $item])) {
                $collection->add($entity);
            }
        }

        $request->attributes->set($name, $collection);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        // if there is no manager, this means that only Doctrine DBAL is configured
        if (null === $this->registry || !\count($this->registry->getManagerNames())) {
            return false;
        }

        $options = $this->getOptions($configuration);

        if (!$options['entity_class']) {
            return false;
        }

        // Doctrine Entity?
        $this->getManager($options['entity_class']);
        if (null === $this->objectManager) {
            return false;
        }

        // Entity field ?
        return \in_array(
            $options['identifier'],
            $this->objectManager->getClassMetadata($options['entity_class'])->getFieldNames(), true
        );
    }

    protected function getOptions(ParamConverter $configuration)
    {
        return array_replace(
            $this->defaultOptions,
            $configuration->getOptions()
        );
    }

    /**
     * Get the object manager.
     *
     * @param $class    string Entity class
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getManager($class)
    {
        if (null === $this->objectManager) {
            $this->objectManager = $this->registry->getManagerForClass($class);
        }

        return $this->objectManager;
    }
}
