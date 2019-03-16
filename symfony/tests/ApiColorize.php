<?php

namespace Tests;

abstract class ApiColorize
{
    /*
     * Color constants
     */
    /* FOREGROUNDS */
    const F_BLACK = 'black';
    const F_DARK_GRAY = 'dark_gray';
    const F_BLUE = 'blue';
    const F_LIGHT_BLUE = 'light_blue';
    const F_GREEN = 'green';
    const F_LIGHT_GREEN = 'light_green';
    const F_CYAN = 'cyan';
    const F_LIGHT_CYAN = 'light_cyan';
    const F_RED = 'red';
    const F_LIGHT_RED = 'light_red';
    const F_PURPLE = 'purple';
    const F_LIGHT_PURPLE = 'light_purple';
    const F_BROWN = 'brown';
    const F_YELLOW = 'yellow';
    const F_LIGHT_GRAY = 'light_gray';
    const F_WHITE = 'white';
    /* BACKGROUNDS */
    const B_BLACK = 'black';
    const B_RED = 'red';
    const B_GREEN = 'green';
    const B_YELLOW = 'yellow';
    const B_BLUE = 'blue';
    const B_MAGENTA = 'magenta';
    const B_CYAN = 'cyan';
    const B_LIGHT_GRAY = 'light_gray';

    /**
     * Foreground color codes.
     *
     * @var array
     */
    private static $foregroundColors = [
        self::F_BLACK => '0;30',
        self::F_DARK_GRAY => '1;30',
        self::F_BLUE => '0;34',
        self::F_LIGHT_BLUE => '1;34',
        self::F_GREEN => '0;32',
        self::F_LIGHT_GREEN => '1;32',
        self::F_CYAN => '0;36',
        self::F_LIGHT_CYAN => '1;36',
        self::F_RED => '0;31',
        self::F_LIGHT_RED => '1;31',
        self::F_PURPLE => '0;35',
        self::F_LIGHT_PURPLE => '1;35',
        self::F_BROWN => '0;33',
        self::F_YELLOW => '1;33',
        self::F_LIGHT_GRAY => '0;37',
        self::F_WHITE => '1;37',
    ];

    /**
     * Background color codes.
     *
     * @var array
     */
    private static $backgroundColors = [
        self::B_BLACK => '40',
        self::B_RED => '41',
        self::B_GREEN => '42',
        self::B_YELLOW => '43',
        self::B_BLUE => '44',
        self::B_MAGENTA => '45',
        self::B_CYAN => '46',
        self::B_LIGHT_GRAY => '47',
    ];

    /**
     * @param string      $string     The string to colorize
     * @param string|null $foreground The foreground to apply
     * @param string|null $background The background to apply
     *
     * @return string The colored string
     */
    public static function colorize(string $string, string $foreground = null, string $background = null)
    {
        if (null !== $foreground && !isset(self::$foregroundColors[$foreground])) {
            throw new \UnexpectedValueException(sprintf('ApiColorize : The foreground "%s" color does not exist.', $foreground));
        }
        if (null !== $background && !isset(self::$backgroundColors[$background])) {
            throw new \UnexpectedValueException(sprintf('ApiColorize : The background "%s" color does not exist.', $background));
        }

        return sprintf(
            "%s%s%s\033[0m",
            $foreground ? "\033[".self::$foregroundColors[$foreground].'m' : '',
            $string,
            $background ? "\033[".self::$backgroundColors[$background].'m' : ''
        );
    }
}
