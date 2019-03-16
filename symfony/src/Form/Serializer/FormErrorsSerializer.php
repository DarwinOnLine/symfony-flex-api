<?php

namespace App\Form\Serializer;

use Monolog\Logger;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Class FormErrorsSerializer.
 */
class FormErrorsSerializer
{
    /** @var Logger */
    private $logger;

    /**
     * FormErrorsSerializer constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param FormInterface $form
     * @param bool          $flatArray
     * @param bool          $addFormName
     * @param bool          $deep
     * @param string        $glueKeys
     *
     * @return array
     */
    public function serializeFormErrors(FormInterface $form, $flatArray = false, $addFormName = false, $deep = false, $glueKeys = '_')
    {
        $errors = [];

        foreach ($form->getErrors($deep) as $error) {
            $cause = $error->getCause();

            if ($cause instanceof ConstraintViolation && ($causeEx = $cause->getCause()) instanceof \Exception) {
                $this->logger->addWarning(
                    sprintf('%s at line %d : %s', $causeEx->getFile(), $causeEx->getLine(), $causeEx->getMessage()),
                    ['form' => $form->getName()]
                );
            }

            $errors[] = $error->getMessage();
        }

        $errors = array_merge($errors, $this->serialize($form));

        if ($flatArray) {
            $errors = $this->arrayFlatten(
                $errors,
                $glueKeys,
                (($addFormName) ? $form->getName() : '')
            );
        }

        return $errors;
    }

    /**
     * @param FormInterface $form
     *
     * @return array
     */
    public function serialize(FormInterface $form)
    {
        $localErrors = [];
        if ($form instanceof Form) {
            foreach ($form->getIterator() as $key => $child) {
                foreach ($child->getErrors() as $error) {
                    $localErrors[$key] = $error->getMessage();
                }

                if (($child instanceof Form) && (\count($child->getIterator()) > 0)) {
                    $localErrors[$key] = $this->serialize($child);
                }
            }
        }

        return $localErrors;
    }

    /**
     * @param $array
     * @param string $separator
     * @param string $flattenedKey
     *
     * @return array
     */
    private function arrayFlatten($array, $separator = '_', $flattenedKey = '')
    {
        $flattenedArray = [];
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $flattenedArray = array_merge(
                    $flattenedArray,
                    $this->arrayFlatten(
                        $value,
                        $separator,
                        (mb_strlen($flattenedKey) > 0 ? $flattenedKey.$separator : '').$key
                    )
                );
            } else {
                $flattenedArray[(mb_strlen($flattenedKey) > 0 ? $flattenedKey.$separator : '').$key] = $value;
            }
        }

        return array_unique($flattenedArray);
    }
}
