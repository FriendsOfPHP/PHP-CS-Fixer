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

namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Graham Campbell <graham@alt-three.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocAlignFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * @internal
     */
    const ALIGN_LEFT = 'left';

    /**
     * @internal
     */
    const ALIGN_VERTICAL = 'vertical';

    /**
     * @internal
     */
    const DESCRIPTION_ALIGN_TAG = 'tag';

    /**
     * @internal
     */
    const DESCRIPTION_ALIGN_HINT = 'hint';

    /**
     * @internal
     */
    const DESCRIPTION_ALIGN_NAME = 'name';

    /**
     * @internal
     */
    const DESCRIPTION_ALIGN_DESCRIPTION = 'description';

    /**
     * @var string
     */
    private $regex;

    /**
     * @var string
     */
    private $regexCommentLine;

    /**
     * @var string
     */
    private $align;

    /**
     * @var string
     */
    private $descriptionAlign;

    /**
     * @var int
     */
    private $descriptionExtraIndent;

    private static $alignableTags = [
        'param',
        'property',
        'return',
        'throws',
        'type',
        'var',
        'method',
    ];

    private static $tagsWithName = [
        'param',
        'property',
    ];

    private static $tagsWithMethodSignature = [
        'method',
    ];

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $tagsWithNameToAlign = array_intersect($this->configuration['tags'], self::$tagsWithName);
        $tagsWithMethodSignatureToAlign = array_intersect($this->configuration['tags'], self::$tagsWithMethodSignature);
        $tagsWithoutNameToAlign = array_diff($this->configuration['tags'], $tagsWithNameToAlign, $tagsWithMethodSignatureToAlign);
        $types = [];

        $indent = '(?P<indent>(?: {2}|\t)*)';
        // e.g. @param <hint> <$var>
        if (!empty($tagsWithNameToAlign)) {
            $types[] = '(?P<tag>'.implode('|', $tagsWithNameToAlign).')\s+(?P<hint>[^$]+?)\s+(?P<var>(?:&|\.{3})?\$[^\s]+)';
        }

        // e.g. @return <hint>
        if (!empty($tagsWithoutNameToAlign)) {
            $types[] = '(?P<tag2>'.implode('|', $tagsWithoutNameToAlign).')\s+(?P<hint2>[^\s]+?)';
        }

        // e.g. @method <hint> <signature>
        if (!empty($tagsWithMethodSignatureToAlign)) {
            $types[] = '(?P<tag3>'.implode('|', $tagsWithMethodSignatureToAlign).')(\s+(?P<hint3>[^\s(]+)|)\s+(?P<signature>.+\))';
        }

        // optional <desc>
        $desc = '(?:\s+(?P<desc>\V*))';

        $this->regex = '/^'.$indent.' \* @(?:'.implode('|', $types).')'.$desc.'\s*$/u';
        $this->regexCommentLine = '/^'.$indent.' \*(?! @)(?:\s+(?P<desc>\V+))(?<!\*\/)\r?$/u';
        $this->align = $this->configuration['align'];
        $this->descriptionAlign = $this->configuration['description_align'];
        $this->descriptionExtraIndent = $this->configuration['description_extra_indent'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $code = <<<'EOF'
<?php
/**
 * @param  EngineInterface $templating
 * @param string      $format
 * @param  int  $code       an HTTP response status code
 * @param    bool         $debug
 * @param  mixed    &$reference     a parameter passed by
 *   reference
 */

EOF;

        return new FixerDefinition(
            'All items of the given phpdoc tags must be either left-aligned or (by default) aligned vertically.'
            .' The alignment of multi-line descriptions must be aligned according to "description_align".',
            [
                new CodeSample($code),
                new CodeSample($code, ['align' => self::ALIGN_VERTICAL]),
                new CodeSample($code, ['align' => self::ALIGN_LEFT]),
                new CodeSample($code, ['description_align' => self::DESCRIPTION_ALIGN_TAG]),
                new CodeSample($code, ['description_align' => self::DESCRIPTION_ALIGN_HINT]),
                new CodeSample($code, ['description_align' => self::DESCRIPTION_ALIGN_NAME]),
                new CodeSample($code, ['description_align' => self::DESCRIPTION_ALIGN_DESCRIPTION]),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        /*
         * Should be run after all other docblock fixers. This because they
         * modify other annotations to change their type and or separation
         * which totally change the behavior of this fixer. It's important that
         * annotations are of the correct type, and are grouped correctly
         * before running this fixer.
         */
        return -21;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $content = $token->getContent();
            $newContent = $this->fixDocBlock($content);
            if ($newContent !== $content) {
                $tokens[$index] = new Token([T_DOC_COMMENT, $newContent]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $tags = new FixerOptionBuilder('tags', 'The tags that should be aligned.');
        $tags
            ->setAllowedTypes(['array'])
            ->setAllowedValues([new AllowedValueSubset(self::$alignableTags)])
            /*
             * By default, all tags apart from @property and @method will be aligned for backwards compatibility
             * @TODO 3.0 Align all available tags by default
             */
            ->setDefault([
                'param',
                'return',
                'throws',
                'type',
                'var',
            ])
        ;

        $align = new FixerOptionBuilder('align', 'Align comments');
        $align
            ->setAllowedTypes(['string'])
            ->setAllowedValues([self::ALIGN_LEFT, self::ALIGN_VERTICAL])
            ->setDefault(self::ALIGN_VERTICAL)
        ;
        $descriptionAlign = new FixerOptionBuilder('description_align', 'The alignment of a description running over multiple lines.');
        $descriptionAlign
            ->setAllowedTypes(['string'])
            ->setAllowedValues([
                self::DESCRIPTION_ALIGN_TAG,
                self::DESCRIPTION_ALIGN_HINT,
                self::DESCRIPTION_ALIGN_NAME,
                self::DESCRIPTION_ALIGN_DESCRIPTION,
            ])
            ->setDefault(self::DESCRIPTION_ALIGN_DESCRIPTION)
        ;

        $descriptionExtraIndent = new FixerOptionBuilder('description_extra_indent', 'Extra indent for a description running over multiple lines.');
        $descriptionExtraIndent
            ->setAllowedTypes(['int'])
            ->setDefault(0)
        ;

        return new FixerConfigurationResolver([
            $tags->getOption(),
            $align->getOption(),
            $descriptionAlign->getOption(),
            $descriptionExtraIndent->getOption(),
        ]);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function fixDocBlock($content)
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $lines = Utils::splitLines($content);

        for ($i = 0, $l = count($lines); $i < $l; ++$i) {
            $items = [];
            $matches = $this->getMatches($lines[$i]);

            if (null === $matches) {
                continue;
            }

            $current = $i;
            $items[] = $matches;

            while (true) {
                if (!isset($lines[++$i])) {
                    break 2;
                }

                $matches = $this->getMatches($lines[$i], true);
                if (null === $matches) {
                    break;
                }

                $items[] = $matches;
            }

            // compute the max length of the tag, hint and variables
            $tagMax = 0;
            $hintMax = 0;
            $varMax = 0;

            foreach ($items as $item) {
                if (null === $item['tag']) {
                    continue;
                }

                $tagMax = max($tagMax, strlen($item['tag']));
                $hintMax = max($hintMax, strlen($item['hint']));
                $varMax = max($varMax, strlen($item['var']));
            }

            $currTag = null;

            // update
            foreach ($items as $j => $item) {
                if (null === $item['tag']) {
                    if ('@' === $item['desc'][0]) {
                        $lines[$current + $j] = $item['indent'].' * '.$item['desc'].$lineEnding;

                        continue;
                    }

                    $line =
                        $item['indent']
                        .' * '
                        .\str_repeat(' ', $this->getDescriptionIndent($tagMax, $hintMax, $varMax, $items, $j))
                        .$item['desc']
                        .$lineEnding;

                    $lines[$current + $j] = $line;

                    continue;
                }

                $currTag = $item['tag'];

                $line =
                    $item['indent']
                    .' * @'
                    .$item['tag']
                    .$this->getIndent(
                        $tagMax - strlen($item['tag']) + 1,
                        $item['hint'] ? 1 : 0
                    )
                    .$item['hint']
                ;

                if (!empty($item['var'])) {
                    $line .=
                        $this->getIndent(($hintMax ?: -1) - strlen($item['hint']) + 1)
                        .$item['var']
                        .(
                            !empty($item['desc'])
                            ? $this->getIndent($varMax - strlen($item['var']) + 1).$item['desc'].$lineEnding
                            : $lineEnding
                        )
                    ;
                } elseif (!empty($item['desc'])) {
                    $line .= $this->getIndent($hintMax - strlen($item['hint']) + 1).$item['desc'].$lineEnding;
                } else {
                    $line .= $lineEnding;
                }

                $lines[$current + $j] = $line;
            }
        }

        return implode($lines);
    }

    /**
     * @param string $line
     * @param bool   $matchCommentOnly
     *
     * @return null|string[]
     */
    private function getMatches($line, $matchCommentOnly = false)
    {
        if (Preg::match($this->regex, $line, $matches)) {
            if (!empty($matches['tag2'])) {
                $matches['tag'] = $matches['tag2'];
                $matches['hint'] = $matches['hint2'];
                $matches['var'] = '';
            }

            if (!empty($matches['tag3'])) {
                $matches['tag'] = $matches['tag3'];
                $matches['hint'] = $matches['hint3'];
                $matches['var'] = $matches['signature'];
            }

            if (isset($matches['hint'])) {
                $matches['hint'] = trim($matches['hint']);
            }

            return $matches;
        }

        if ($matchCommentOnly && Preg::match($this->regexCommentLine, $line, $matches)) {
            $matches['tag'] = null;
            $matches['var'] = '';
            $matches['hint'] = '';

            return $matches;
        }
    }

    /**
     * @param int $verticalAlignIndent
     * @param int $leftAlignIndent
     *
     * @return string
     */
    private function getIndent($verticalAlignIndent, $leftAlignIndent = 1)
    {
        $indent = self::ALIGN_VERTICAL === $this->align ? $verticalAlignIndent : $leftAlignIndent;

        return \str_repeat(' ', $indent);
    }

    /**
     * @param int   $maxTagLen
     * @param int   $maxHintLen
     * @param int   $maxVarLen
     * @param array $items
     * @param int   $index
     *
     * @return int
     */
    private function getDescriptionIndent($maxTagLen, $maxHintLen, $maxVarLen, array $items, $index)
    {
        $indent = self::ALIGN_VERTICAL === $this->align
            ? $this->getVerticalAlignDescriptionIndent($maxTagLen, $maxHintLen, $maxVarLen)
            : $this->getLeftAlignedDescriptionIndent($items, $index);

        return max(0, $this->descriptionExtraIndent + $indent);
    }

    /**
     * @param int $maxTagLen
     * @param int $maxHintLen
     * @param int $maxVarLen
     *
     * @return int
     */
    private function getVerticalAlignDescriptionIndent($maxTagLen, $maxHintLen, $maxVarLen)
    {
        if (self::DESCRIPTION_ALIGN_TAG === $this->descriptionAlign) {
            return 0;
        }

        if (self::DESCRIPTION_ALIGN_HINT === $this->descriptionAlign) {
            return $maxTagLen + 2;
        }

        if (self::DESCRIPTION_ALIGN_NAME === $this->descriptionAlign) {
            return $maxTagLen + $maxHintLen + 3;
        }

        // self::DESCRIPTION_ALIGN_DESCRIPTION === $this->descriptionAlign
        $indent = $maxTagLen + $maxHintLen + 3;
        if ($maxVarLen) {
            $indent += $maxVarLen + 1;
        }

        return $indent;
    }

    /**
     * @param array[] $items
     * @param int     $index
     *
     * @return int
     */
    private function getLeftAlignedDescriptionIndent(array $items, $index)
    {
        if (self::DESCRIPTION_ALIGN_TAG === $this->descriptionAlign) {
            return 0;
        }

        // Find last tagged line:
        $item = null;
        for (; $index >= 0; --$index) {
            $item = $items[$index];
            if (null !== $item['tag']) {
                break;
            }
        }

        // No last tag found — no indent:
        if (null === $item) {
            return 0;
        }

        // Indent according to existing values:
        $tagIndent = $this->getSentenceIndent($item['tag']) + 1;
        if (self::DESCRIPTION_ALIGN_HINT === $this->descriptionAlign) {
            return $tagIndent;
        }

        $hintIndent = $this->getSentenceIndent($item['hint']);
        if (self::DESCRIPTION_ALIGN_NAME === $this->descriptionAlign) {
            return $tagIndent + $hintIndent;
        }

        $varIndent = $this->getSentenceIndent($item['var']);
        // self::DESCRIPTION_ALIGN_DESCRIPTION === $this->descriptionAlign
        return $tagIndent + $hintIndent + $varIndent;
    }

    /**
     * Get indent for sentence.
     *
     * @param null|string $sentence
     *
     * @return int
     */
    private function getSentenceIndent($sentence)
    {
        if (null === $sentence) {
            return 0;
        }

        $length = strlen($sentence);

        return 0 === $length ? 0 : $length + 1;
    }
}
