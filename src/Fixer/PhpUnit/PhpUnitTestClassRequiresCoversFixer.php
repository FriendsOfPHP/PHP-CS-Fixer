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

namespace PhpCsFixer\Fixer\PhpUnit;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Indicator\PhpUnitIndicator;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpUnitTestClassRequiresCoversFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHPUnit test class requires any of `covers*` annotation.',
            array(
                new CodeSample(
'<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testSomeTest()
    {
        $this->assertSame(a(), b());
    }
}
'
                ),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $phpUnitIndicator = new PhpUnitIndicator();
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if (!$tokens[$index]->isGivenKind(T_CLASS)) {
                continue;
            }

            if (!$phpUnitIndicator->isPhpUnitClass($tokens, $index)) {
                continue;
            }

            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            $index = $tokens[$prevIndex]->isGivenKind(array(T_FINAL, T_ABSTRACT)) ? $prevIndex : $index;

            $indent = $tokens[$index - 1]->isGivenKind(T_WHITESPACE)
                ? (
                    false !== strpos($tokens[$index - 1]->getContent(), "\n")
                    ? preg_replace('/^.*\v*/', '', $tokens[$index - 1]->getContent())
                    : $tokens[$index - 1]->getContent()
                )
                : '';

            $prevIndex = $tokens->getPrevNonWhitespace($index);
            $doc = null;
            $docIndex = null;

            if ($tokens[$prevIndex]->isGivenKind(T_DOC_COMMENT)) {
                $docIndex = $prevIndex;
                $docContent = $tokens[$docIndex]->getContent();

                // ignore one-line phpdocs like `/** foo */`, as there is no place to put new annotations
                if (false === strpos($docContent, "\n")) {
                    continue;
                }

                $doc = new DocBlock($docContent);

                // skip if already has annotation
                if (!empty($doc->getAnnotationsOfType(array(
                    'covers',
                    'coversDefaultClass',
                    'coversNothing',
                )))) {
                    continue;
                }
            } else {
                $docIndex = $index;
                $tokens->insertAt($docIndex, array(
                    new Token(array(T_DOC_COMMENT, sprintf('/**%s%s */', $this->whitespacesConfig->getLineEnding(), $indent))),
                    new Token(array(T_WHITESPACE, sprintf('%s%s', $this->whitespacesConfig->getLineEnding(), $indent))),
                ));

                if (!$tokens[$docIndex - 1]->isGivenKind(T_WHITESPACE)) {
                    $extraNewLines = $this->whitespacesConfig->getLineEnding();

                    if (!$tokens[$docIndex - 1]->isGivenKind(T_OPEN_TAG)) {
                        $extraNewLines .= $this->whitespacesConfig->getLineEnding();
                    }

                    $tokens->insertAt($docIndex, array(
                        new Token(array(T_WHITESPACE, $extraNewLines.$indent)),
                    ));
                    ++$docIndex;
                }

                $doc = new DocBlock($tokens[$docIndex]->getContent());
            }

            $lines = $doc->getLines();
            array_splice(
                $lines,
                count($lines) - 1,
                0,
                array(
                    new Line(sprintf(
                        '%s * @coversNothing%s',
                        $indent,
                        $this->whitespacesConfig->getLineEnding()
                    )),
                )
            );

            $tokens[$docIndex]->setContent(implode('', $lines));
        }
    }
}
