<?php

namespace App\Utils;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

abstract class ApiFormat
{
    public const JSON = 'application/json';
    public const XML = 'application/xml';

    /**
     * @var array
     */
    protected static $encoderFormats = [
        self::JSON => JsonEncoder::FORMAT,
        self::XML => 'xml',
    ];

    /**
     * @param $format
     *
     * @return EncoderInterface|DecoderInterface
     */
    public static function getEncoder($format)
    {
        switch ($format) {
            case self::JSON:
                return new JsonEncoder();
            case self::XML:
                return new XmlEncoder();
            default:
        }

        throw new \InvalidArgumentException(sprintf('Format "%s" unrecognized', $format));
    }

    /**
     * Write data to format.
     *
     * @param $data
     * @param $format
     *
     * @return bool|float|int|string
     */
    public static function writeData($data, $format)
    {
        return static::getEncoder($format)->encode($data, static::$encoderFormats[$format]);
    }

    /**
     * Write data from format.
     *
     * @param $data
     * @param $format
     *
     * @return mixed
     */
    public static function readData($data, $format)
    {
        return static::getEncoder($format)->decode($data, static::$encoderFormats[$format]);
    }
}
