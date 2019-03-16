<?php

namespace App\Utils;

/**
 * Some tools for a prettier API.
 */
abstract class ApiTools
{
    /**
     * Normalize class name for JSON
     * Ex : normalizeClassName("One\Two\ThreeFour") => "one.two.three_four".
     *
     * @param string $className
     *
     * @return string
     */
    public static function normalizeClassName(string $className)
    {
        preg_match_all('#([A-Z\\\][A-Z0-9\\\]*(?=$|[A-Z\\\][a-z0-9\\\])|[A-Za-z\\\][a-z0-9\\\]+)#', $className, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === mb_strtoupper($match) ? mb_strtolower($match) : lcfirst($match);
        }

        return preg_replace('#\\\_#', '.', implode('_', $ret));
    }

    /**
     * Normalize entity name for JSON from its class name.
     *
     * @param string $entityClassName
     *
     * @return string
     */
    public static function normalizeEntityClassName(string $entityClassName)
    {
        return self::normalizeClassName(
            mb_substr($entityClassName, mb_strpos($entityClassName, 'Entity') + mb_strlen('Entity') + 1)
        );
    }
}
