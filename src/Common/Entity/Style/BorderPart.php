<?php

namespace OpenSpout\Common\Entity\Style;

use OpenSpout\Writer\Exception\Border\InvalidNameException;
use OpenSpout\Writer\Exception\Border\InvalidStyleException;
use OpenSpout\Writer\Exception\Border\InvalidWidthException;

class BorderPart
{
    /**
     * @var string the style of this border part
     */
    protected string $style;

    /**
     * @var string the name of this border part
     */
    protected string $name;

    /**
     * @var string the color of this border part
     */
    protected string $color;

    /**
     * @var string the width of this border part
     */
    protected string $width;

    /**
     * @var array allowed style constants for parts
     */
    protected static array $allowedStyles = [
        'none',
        'solid',
        'dashed',
        'dotted',
        'double',
    ];

    /**
     * @var array allowed names constants for border parts
     */
    protected static array $allowedNames = [
        'left',
        'right',
        'top',
        'bottom',
    ];

    /**
     * @var array allowed width constants for border parts
     */
    protected static array $allowedWidths = [
        'thin',
        'medium',
        'thick',
    ];

    /**
     * @param string $name  @see  BorderPart::$allowedNames
     * @param string $color A RGB color code
     * @param string $width @see BorderPart::$allowedWidths
     * @param string $style @see BorderPart::$allowedStyles
     *
     * @throws InvalidNameException
     * @throws InvalidStyleException
     * @throws InvalidWidthException
     */
    public function __construct(string $name, string $color = Color::BLACK, string $width = Border::WIDTH_MEDIUM, string $style = Border::STYLE_SOLID)
    {
        $this->setName($name);
        $this->setColor($color);
        $this->setWidth($width);
        $this->setStyle($style);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name The name of the border part @see BorderPart::$allowedNames
     *
     * @throws InvalidNameException
     */
    public function setName(string $name)
    {
        if (!\in_array($name, self::$allowedNames, true)) {
            throw new InvalidNameException($name);
        }
        $this->name = $name;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style The style of the border part @see BorderPart::$allowedStyles
     *
     * @throws InvalidStyleException
     */
    public function setStyle(string $style)
    {
        if (!\in_array($style, self::$allowedStyles, true)) {
            throw new InvalidStyleException($style);
        }
        $this->style = $style;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $color The color of the border part @see Color::rgb()
     */
    public function setColor(string $color)
    {
        $this->color = $color;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    /**
     * @param string $width The width of the border part @see BorderPart::$allowedWidths
     *
     * @throws InvalidWidthException
     */
    public function setWidth(string $width)
    {
        if (!\in_array($width, self::$allowedWidths, true)) {
            throw new InvalidWidthException($width);
        }
        $this->width = $width;
    }

    public static function getAllowedStyles(): array
    {
        return self::$allowedStyles;
    }

    public static function getAllowedNames(): array
    {
        return self::$allowedNames;
    }

    public static function getAllowedWidths(): array
    {
        return self::$allowedWidths;
    }
}
