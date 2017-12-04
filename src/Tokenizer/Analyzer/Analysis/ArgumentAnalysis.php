<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tokenizer\Analyzer\Analysis;

/**
 * @internal
 */
final class ArgumentAnalysis
{
    /**
     * The default value of the argument.
     *
     * @var null|string
     */
    private $default;

    /**
     * The name of the argument.
     *
     * @var string
     */
    private $name;

    /**
     * The index where the name is located in the supplied Tokens object.
     *
     * @var int
     */
    private $nameIndex;

    /**
     * The type of the argument.
     *
     * @var null|string
     */
    private $type;

    /**
     * @var null|int
     */
    private $typeIndexStart;

    /**
     * @var null|int
     */
    private $typeIndexEnd;

    /**
     * ArgumentAnalysis constructor.
     *
     * @param string      $name
     * @param int         $nameIndex
     * @param null|string $default
     * @param null|string $type
     * @param null|int    $typeIndexStart
     * @param null|int    $typeIndexEnd
     */
    public function __construct($name, $nameIndex, $default, $type, $typeIndexStart, $typeIndexEnd)
    {
        $this->name = (string) $name;
        $this->nameIndex = (int) $nameIndex;
        $this->default = $default ? (string) $default : null;
        $this->type = $type ? (string) $type : null;
        $this->typeIndexStart = $typeIndexStart ? (int) $typeIndexStart : null;
        $this->typeIndexEnd = $typeIndexEnd ? (int) $typeIndexEnd : null;
    }

    /**
     * @return null|string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function hasDefault()
    {
        return null !== $this->default;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getNameIndex()
    {
        return $this->nameIndex;
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasType()
    {
        return null !== $this->type;
    }

    /**
     * @return null|int
     */
    public function getTypeIndexStart()
    {
        return $this->typeIndexStart;
    }

    /**
     * @return null|int
     */
    public function getTypeIndexEnd()
    {
        return $this->typeIndexEnd;
    }
}
