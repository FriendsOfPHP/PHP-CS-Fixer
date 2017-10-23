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

namespace PhpCsFixer\Fixer\StringNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class EscapeImplicitBackslashesFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $codeSamble = <<<'EOF'
<?php

$singleQuoted = 'String with \" and My\Prefix\\';

$doubleQuoted = "Interpret my \n but not my \a";

$hereDoc = <<<HEREDOC
Interpret my \100 but not my \999
HEREDOC;

EOF;

        return new FixerDefinition(
            'Escape implicit backslashes in strings and heredocs to increase code readability.',
            [
                new CodeSample($codeSamble),
                new CodeSample(
                    $codeSamble,
                    ['single_quoted' => true]
                ),
                new CodeSample(
                    $codeSamble,
                    ['double_quoted' => false]
                ),
                new CodeSample(
                    $codeSamble,
                    ['heredoc_syntax' => false]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // Should run before single_quote and heredoc_to_nowdoc
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        static $singleQuotedRegex = '/(?<!\\\\)\\\\(?![\\\'\\\\])/';
        static $doubleQuotedRegex = '/(?<!\\\\)\\\\(?![efnrtv$"\\\\0-7]|x[0-9A-Fa-f]|u{)/';
        static $heredocSyntaxRegex = '/(?<!\\\\)\\\\(?![efnrtv$\\\\0-7]|x[0-9A-Fa-f]|u{)/';

        $doubleQuoteOpened = false;
        foreach ($tokens as $index => $token) {
            $content = $token->getContent();
            if ($token->equals('"')) {
                $doubleQuoteOpened = !$doubleQuoteOpened;
            }
            if (!$token->isGivenKind([T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING]) || false === strpos($content, '\\')) {
                continue;
            }

            // Nowdoc syntax
            if ($token->isGivenKind(T_ENCAPSED_AND_WHITESPACE) && '\'' === substr(rtrim($tokens[$index - 1]->getContent()), -1)) {
                continue;
            }

            $isSingleQuotedString = $token->isGivenKind(T_CONSTANT_ENCAPSED_STRING) && '\'' === $content[0];
            $isDoubleQuotedString = (
                ($token->isGivenKind(T_CONSTANT_ENCAPSED_STRING) && '"' === $content[0])
                || ($token->isGivenKind(T_ENCAPSED_AND_WHITESPACE) && $doubleQuoteOpened)
            );
            $isHeredocSyntax = !$isSingleQuotedString && !$isDoubleQuotedString;
            if (
                (false === $this->configuration['single_quoted'] && $isSingleQuotedString)
                || (false === $this->configuration['double_quoted'] && $isDoubleQuotedString)
                || (false === $this->configuration['heredoc_syntax'] && $isHeredocSyntax)
            ) {
                continue;
            }

            $regex = $heredocSyntaxRegex;
            if ($isSingleQuotedString) {
                $regex = $singleQuotedRegex;
            } elseif ($isDoubleQuotedString) {
                $regex = $doubleQuotedRegex;
            }

            $newContent = preg_replace($regex, '\\\\\\\\', $content);
            if ($newContent !== $content) {
                $tokens[$index] = new Token([$token->getId(), $newContent]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('single_quoted', 'Whether to fix single-quoted strings.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(false)
                ->getOption(),
            (new FixerOptionBuilder('double_quoted', 'Whether to fix double-quoted strings.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
            (new FixerOptionBuilder('heredoc_syntax', 'Whether to fix heredoc syntax.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
        ]);
    }
}
