<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

/**
 * This class defines rich text in a fluent interface that can be added to a comment.
 */
final class TextRun
{
    private int $fontSize = 10;
    private string $fontColor = '000000';
    private string $fontName = 'Tahoma';
    private bool $bold = false;
    private bool $italic = false;
    private string $text;
    
    public function __construct(string $text) {
        $this->text = $text;
    }

    public function setBold(bool $bold) : self
    {
        $this->bold = $bold;
        return $this;
    }

    public function setItalic(bool $italic) : self
    {
        $this->italic = $italic;
        return $this;
    }

    public function setFontSize(int $size): self
    {
        $this->fontSize = $size;
        return $this;
    }

    public function setFontColor(string $fontColor): self
    {
        $this->fontColor = $fontColor;
        return $this;
    }

    public function setFontName(string $fontName): self
    {
        $this->fontName = $fontName;
        return $this;
    }


    public function getFontName() : string 
    {
        return $this->fontName;
    }

    public function getFontColor() : string
    {
        return $this->fontColor;
    }

    public function getFontSize() : int
    {
        return $this->fontSize;
    }

    public function getBold() : bool
    {
        return $this->bold;
    }

    public function getItalic() : bool
    {
        return $this->italic;
    }

    public function getText() : string
    {
        return $this->text;
    }
}