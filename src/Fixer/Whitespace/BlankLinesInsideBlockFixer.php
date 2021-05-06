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

namespace PhpCsFixer\Fixer\Whitespace;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class BlankLinesInsideBlockFixer extends AbstractFixer
{
    private $bracesFixerCompatibility;

    /**
     * @param bool $bracesFixerCompatibility
     */
    public function __construct($bracesFixerCompatibility = false)
    {
        parent::__construct();

        $this->bracesFixerCompatibility = $bracesFixerCompatibility;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'There must not be blank lines at start and end of braces blocks.',
            [
                new CodeSample(
                    '<?php
class Foo {

    public function foo() {

        if ($baz == true) {

            echo "foo";

        }

    }

}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound('{');
    }

    /**
     * {@inheritdoc}
     *
     * Must run after ControlStructureBracesFixer.
     */
    public function getPriority()
    {
        return parent::getPriority();
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isWhitespace()) {
                continue;
            }

            if (
                !$tokens[$index - 1]->equals('{')
                && (!isset($tokens[$index + 1]) || !$tokens[$index + 1]->equals('}'))
            ) {
                continue;
            }

            if (
                $this->bracesFixerCompatibility
                && $tokens[$index - 1]->equals('{')
                && $tokens[$tokens->getNextNonWhitespace($index)]->isComment()
            ) {
                continue;
            }

            $content = Preg::replace('/^.*?(\R\h*)$/Ds', '$1', $token->getContent());

            $tokens[$index] = new Token([$token->getId(), $content]);
        }
    }
}
