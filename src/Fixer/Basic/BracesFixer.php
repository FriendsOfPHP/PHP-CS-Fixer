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

namespace PhpCsFixer\Fixer\Basic;

use PhpCsFixer\AbstractProxyFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\ControlStructure\ControlStructureBracesFixer;
use PhpCsFixer\Fixer\ControlStructure\ControlStructureContinuationFixer;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareBracesFixer;
use PhpCsFixer\Fixer\LanguageConstruct\SingleSpaceAfterConstructFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLinesInsideBlockFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Fixer for rules defined in PSR2 ¶4.1, ¶4.4, ¶5.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @deprecated since 2.17
 */
final class BracesFixer extends AbstractProxyFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface, DeprecatedFixerInterface
{
    /**
     * @internal
     */
    const LINE_NEXT = 'next';

    /**
     * @internal
     */
    const LINE_SAME = 'same';

    /**
     * @var null|CurlyBracesPositionFixer
     */
    private $curylBracesPositionFixer;

    /**
     * @var null|ControlStructureContinuationFixer
     */
    private $controlStructureContinuationFixer;

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'The body of each structure MUST be enclosed by braces. Braces should be properly placed. Body of braces should be properly indented.',
            [
                new CodeSample(
                    '<?php

class Foo {
    public function bar($baz) {
        if ($baz = 900) echo "Hello!";

        if ($baz = 9000)
            echo "Wait!";

        if ($baz == true)
        {
            echo "Why?";
        }
        else
        {
            echo "Ha?";
        }

        if (is_array($baz))
            foreach ($baz as $b)
            {
                echo $b;
            }
    }
}
'
                ),
                new CodeSample(
                    '<?php
$positive = function ($item) { return $item >= 0; };
$negative = function ($item) {
                return $item < 0; };
',
                    ['allow_single_line_closure' => true]
                ),
                new CodeSample(
                    '<?php

class Foo
{
    public function bar($baz)
    {
        if ($baz = 900) echo "Hello!";

        if ($baz = 9000)
            echo "Wait!";

        if ($baz == true)
        {
            echo "Why?";
        }
        else
        {
            echo "Ha?";
        }

        if (is_array($baz))
            foreach ($baz as $b)
            {
                echo $b;
            }
    }
}
',
                    ['position_after_functions_and_oop_constructs' => self::LINE_SAME]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before HeredocIndentationFixer.
     * Must run after ClassAttributesSeparationFixer, ClassDefinitionFixer, MethodSeparationFixer, NoAlternativeSyntaxFixer, NoEmptyStatementFixer, NoUselessElseFixer, SingleLineThrowFixer, SingleSpaceAfterConstructFixer, SingleTraitInsertPerStatementFixer.
     */
    public function getPriority()
    {
        return 35;
    }

    /**
     * {@inheritdoc}
     */
    public function getSuccessorsNames()
    {
        return array_keys($this->proxyFixers);
    }

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->getCurlyBracesPositionFixer()->configure([
            'control_structures_opening_brace' => $this->translatePositionOption($this->configuration['position_after_control_structures']),
            'functions_opening_brace' => $this->translatePositionOption($this->configuration['position_after_functions_and_oop_constructs']),
            'anonymous_functions_opening_brace' => $this->translatePositionOption($this->configuration['position_after_anonymous_constructs']),
            'classes_opening_brace' => $this->translatePositionOption($this->configuration['position_after_functions_and_oop_constructs']),
            'anonymous_classes_opening_brace' => $this->translatePositionOption($this->configuration['position_after_anonymous_constructs']),
            'allow_single_line_empty_anonymous_classes' => $this->configuration['allow_single_line_anonymous_class_with_empty_body'],
            'allow_single_line_anonymous_functions' => $this->configuration['allow_single_line_closure'],
        ]);

        $this->getControlStructureContinuationFixer()->configure([
            'keyword_position' => self::LINE_NEXT === $this->configuration['position_after_control_structures']
                ? ControlStructureContinuationFixer::NEXT_LINE
                : ControlStructureContinuationFixer::SAME_LINE,
        ]);

        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = \count($tokens) - 1; $index > 0; --$index) {
            if ($tokens[$index]->isGivenKind(CT::T_USE_LAMBDA)) {
                $tokens->ensureWhitespaceAtIndex($index - 1, 1, ' ');
            }
        }

        parent::applyFix($file, $tokens);
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('allow_single_line_anonymous_class_with_empty_body', 'Whether single line anonymous class with empty body notation should be allowed.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
            (new FixerOptionBuilder('allow_single_line_closure', 'Whether single line lambda notation should be allowed.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
            (new FixerOptionBuilder('position_after_functions_and_oop_constructs', 'whether the opening brace should be placed on "next" or "same" line after classy constructs (non-anonymous classes, interfaces, traits, methods and non-lambda functions).'))
                ->setAllowedValues([self::LINE_NEXT, self::LINE_SAME])
                ->setDefault(self::LINE_NEXT)
                ->getOption(),
            (new FixerOptionBuilder('position_after_control_structures', 'whether the opening brace should be placed on "next" or "same" line after control structures.'))
                ->setAllowedValues([self::LINE_NEXT, self::LINE_SAME])
                ->setDefault(self::LINE_SAME)
                ->getOption(),
            (new FixerOptionBuilder('position_after_anonymous_constructs', 'whether the opening brace should be placed on "next" or "same" line after anonymous constructs (anonymous classes and lambda functions).'))
                ->setAllowedValues([self::LINE_NEXT, self::LINE_SAME])
                ->setDefault(self::LINE_SAME)
                ->getOption(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function createProxyFixers()
    {
        $singleSpaceAfterConstructFixer = new SingleSpaceAfterConstructFixer();
        $singleSpaceAfterConstructFixer->configure([
            'constructs' => ['elseif', 'for', 'foreach', 'if', 'match', 'while', 'use_lambda'],
        ]);

        return [
            $singleSpaceAfterConstructFixer,
            new ControlStructureBracesFixer(true),
            new BlankLinesInsideBlockFixer(true),
            $this->getCurlyBracesPositionFixer(),
            $this->getControlStructureContinuationFixer(),
            new DeclareBracesFixer(),
            new NoMultipleStatementsPerLineFixer(),
            new StatementIndentationFixer(true),
        ];
    }

    private function getCurlyBracesPositionFixer()
    {
        if (null === $this->curylBracesPositionFixer) {
            $this->curylBracesPositionFixer = new CurlyBracesPositionFixer();
        }

        return $this->curylBracesPositionFixer;
    }

    private function getControlStructureContinuationFixer()
    {
        if (null === $this->controlStructureContinuationFixer) {
            $this->controlStructureContinuationFixer = new ControlStructureContinuationFixer();
        }

        return $this->controlStructureContinuationFixer;
    }

    private function translatePositionOption($option)
    {
        return self::LINE_NEXT === $option
            ? CurlyBracesPositionFixer::NEXT_LINE_UNLESS_NEWLINE_AT_SIGNATURE_END
            : CurlyBracesPositionFixer::SAME_LINE
        ;
    }
}
