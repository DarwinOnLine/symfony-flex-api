<?php

namespace App\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

class UuidListener
{
    /**
     * @param LifecycleEventArgs $event
     *
     * @throws \Exception
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        if (property_exists($entity, 'uuid')) {
            /** @var UuidFactory $factory */
            $factory = clone Uuid::getFactory();
            $factory->setCodec(new OrderedTimeCodec($factory->getUuidBuilder()));
            $entity->setUuid($factory->uuid1());
        }
    }
}
