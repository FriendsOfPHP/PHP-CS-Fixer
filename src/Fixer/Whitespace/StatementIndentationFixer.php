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
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\AlternativeSyntaxAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class StatementIndentationFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
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
            'Each statement must be indented.',
            [
                new CodeSample(
                    '<?php
if ($baz == true) {
  echo "foo";
}
else {
      echo "bar";
}
'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before HeredocIndentationFixer.
     * Must run after ClassAttributesSeparationFixer, MethodSeparationFixer.
     */
    public function getPriority()
    {
        return parent::getPriority();
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $alternativeSyntaxAnalyzer = new AlternativeSyntaxAnalyzer();
        $endIndex = \count($tokens) - 1;
        if ($tokens[$endIndex]->isWhitespace()) {
            --$endIndex;
        }

        $scopes = [
            [
                'type' => 'block',
                'end_index' => $endIndex,
                'initial_indent' => $this->getLineIndentation($tokens, 0),
                'is_indented_block' => false,
            ],
        ];

        $previousLineInitialIndent = '';
        $previousLineNewIndent = '';
        $alternativeBlockStarts = [];
        $caseBlockStarts = [];

        foreach ($tokens as $index => $token) {
            $currentScope = \count($scopes) - 1;

            if ($token->isComment()) {
                continue;
            }

            if (
                $token->equalsAny(['{', [T_EXTENDS], [T_IMPLEMENTS], [CT::T_GROUP_IMPORT_BRACE_OPEN]])
                || ($token->equals('(') && !$tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind(T_ARRAY))
                || isset($alternativeBlockStarts[$index])
                || isset($caseBlockStarts[$index])
            ) {
                if ($token->isGivenKind([T_EXTENDS, T_IMPLEMENTS])) {
                    $endIndex = $tokens->getNextTokenOfKind($index, ['{']);
                } elseif ($token->equals(':')) {
                    if (isset($caseBlockStarts[$index])) {
                        $endIndex = $this->findCaseBlockEnd($tokens, $index);
                    } else {
                        $endIndex = $alternativeSyntaxAnalyzer->findAlternativeSyntaxBlockEnd($tokens, $alternativeBlockStarts[$index]);
                    }
                } elseif ($token->isGivenKind(CT::T_GROUP_IMPORT_BRACE_OPEN)) {
                    $endIndex = $tokens->getNextTokenOfKind($index, [[CT::T_GROUP_IMPORT_BRACE_CLOSE]]);
                } else {
                    $endIndex = $tokens->findBlockEnd(
                        $token->equals('{') ? Tokens::BLOCK_TYPE_CURLY_BRACE : Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                        $index
                    );
                }

                if ('block_signature' === $scopes[$currentScope]['type']) {
                    $initialIndent = $scopes[$currentScope]['initial_indent'];
                } else {
                    $initialIndent = $this->getLineIndentation($tokens, $index);
                }

                $scopes[] = [
                    'type' => 'block',
                    'end_index' => $endIndex,
                    'initial_indent' => $initialIndent,
                    'is_indented_block' => true,
                ];

                continue;
            }

            if ($token->isGivenKind([T_USE, T_IF, T_ELSE, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_SWITCH, T_CASE, T_DEFAULT, T_TRY, T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT])) {
                for ($endIndex = $index + 1, $max = \count($tokens); $endIndex < $max; ++$endIndex) {
                    if ($tokens[$endIndex]->equals('(')) {
                        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $endIndex);

                        continue;
                    }

                    if ($tokens[$endIndex]->equalsAny(['{', ';'])) {
                        break;
                    }

                    if ($tokens[$endIndex]->equals(':')) {
                        if ($token->isGivenKind([T_CASE, T_DEFAULT])) {
                            $caseBlockStarts[$endIndex] = $index;
                        } else {
                            $alternativeBlockStarts[$endIndex] = $index;
                        }

                        break;
                    }
                }

                $scopes[] = [
                    'type' => 'block_signature',
                    'end_index' => $endIndex,
                    'initial_indent' => $this->getLineIndentation($tokens, $index),
                    'is_indented_block' => false,
                ];

                continue;
            }

            if (
                $token->isWhitespace()
                || ($index > 0 && $tokens[$index - 1]->isGivenKind(T_OPEN_TAG))
            ) {
                $previousOpenTagContent = $tokens[$index - 1]->isGivenKind(T_OPEN_TAG)
                    ? Preg::replace('/\S/', '', $tokens[$index - 1]->getContent())
                    : ''
                ;

                $content = $previousOpenTagContent.($token->isWhitespace() ? $token->getContent() : '');

                if (!Preg::match('/\R/', $content)) {
                    continue;
                }

                $nextToken = isset($tokens[$index + 1]) ? $tokens[$index + 1] : null;

                if (
                    $this->bracesFixerCompatibility
                    && null !== $nextToken
                    && $nextToken->isComment()
                    && !$this->isCommentWithFixableIndentation($tokens, $index + 1)
                ) {
                    continue;
                }

                if ('block' === $scopes[$currentScope]['type'] || 'block_signature' === $scopes[$currentScope]['type']) {
                    $indent = false;

                    if ($scopes[$currentScope]['is_indented_block']) {
                        $firstMeaningFulTokenIndex = null;
                        $nextNewlineIndex = null;
                        for ($searchIndex = $index + 1, $max = \count($tokens); $searchIndex < $max; ++$searchIndex) {
                            $searchToken = $tokens[$searchIndex];

                            if (!$searchToken->isWhitespace() && !$searchToken->isComment()) {
                                if (null === $firstMeaningFulTokenIndex) {
                                    $firstMeaningFulTokenIndex = $searchIndex;
                                }

                                continue;
                            }

                            if ($searchToken->isWhitespace() && Preg::match('/\R/', $searchToken->getContent())) {
                                $nextNewlineIndex = $searchIndex;

                                break;
                            }
                        }

                        $isIndentableStatement = !$this->isCommentForControlSructureContinuation($tokens, $index + 1);

                        if (
                            $isIndentableStatement
                            && (
                                (null !== $firstMeaningFulTokenIndex && $firstMeaningFulTokenIndex < $scopes[$currentScope]['end_index'])
                                || (null !== $nextNewlineIndex && $nextNewlineIndex < $scopes[$currentScope]['end_index'])
                            )
                        ) {
                            $indent = true;
                        } elseif (null !== $nextNewlineIndex) {
                            for ($parentScope = $currentScope - 1; $parentScope >= 0; --$parentScope) {
                                if ($scopes[$parentScope]['end_index'] < $nextNewlineIndex) {
                                    continue;
                                }

                                if (!isset($scopes[$parentScope]['is_indented_block']) || !$scopes[$parentScope]['is_indented_block']) {
                                    break;
                                }

                                if (
                                    $isIndentableStatement
                                    && $firstMeaningFulTokenIndex < $scopes[$parentScope]['end_index']
                                    && \strlen($scopes[$parentScope]['initial_indent']) >= \strlen($scopes[$currentScope]['initial_indent'])
                                ) {
                                    $indent = true;
                                }

                                break;
                            }
                        }
                    }

                    $previousLineInitialIndent = $this->extractIndent($content);

                    $content = Preg::replace(
                        '/(\R+)\h*$/',
                        '$1'.$scopes[$currentScope]['initial_indent'].($indent ? $this->whitespacesConfig->getIndent() : ''),
                        $content
                    );

                    $previousLineNewIndent = $this->extractIndent($content);
                } else {
                    $content = Preg::replace(
                        '/(\R)'.$scopes[$currentScope]['initial_indent'].'(\h*)$/D',
                        '$1'.$scopes[$currentScope]['new_indent'].'$2',
                        $content
                    );
                }

                if ('' !== $previousOpenTagContent) {
                    $content = Preg::replace("/^{$previousOpenTagContent}/", '', $content);
                }

                if ('' !== $content) {
                    $tokens->ensureWhitespaceAtIndex($index, 0, $content);
                } elseif ($token->isWhitespace()) {
                    $tokens->clearAt($index);
                }

                if (null !== $nextToken && $nextToken->isComment()) {
                    $tokens[$index + 1] = new Token([
                        $nextToken->getId(),
                        Preg::replace(
                            '/(\R)'.preg_quote($previousLineInitialIndent, '/').'(\h*\S+.*)/',
                            '$1'.$previousLineNewIndent.'$2',
                            $nextToken->getContent()
                        ),
                    ]);
                }

                if ($token->isWhitespace()) {
                    continue;
                }
            }

            while ($index >= $scopes[$currentScope]['end_index']) {
                array_pop($scopes);

                if ([] === $scopes) {
                    return;
                }

                --$currentScope;
            }

            if ($token->equalsAny([';', ',', '}', [T_OPEN_TAG], [T_CLOSE_TAG]])) {
                continue;
            }

            if ('statement' !== $scopes[$currentScope]['type'] && 'block_signature' !== $scopes[$currentScope]['type']) {
                $endIndex = $this->findStatementEndIndex($tokens, $index, $scopes[$currentScope]['end_index']);

                if ($endIndex === $index) {
                    continue;
                }

                $scopes[] = [
                    'type' => 'statement',
                    'end_index' => $endIndex,
                    'initial_indent' => $previousLineInitialIndent,
                    'new_indent' => $previousLineNewIndent,
                ];
            }
        }
    }

    private function findStatementEndIndex(Tokens $tokens, $index, $parentScopeEndIndex)
    {
        $endIndex = null;

        for ($searchEndIndex = $index; $searchEndIndex < $parentScopeEndIndex; ++$searchEndIndex) {
            $searchEndToken = $tokens[$searchEndIndex];

            if ($searchEndToken->equalsAny(['(', '{', [CT::T_ARRAY_SQUARE_BRACE_OPEN]])) {
                if ($searchEndToken->equals('(')) {
                    $blockType = Tokens::BLOCK_TYPE_PARENTHESIS_BRACE;
                } elseif ($searchEndToken->equals('{')) {
                    $blockType = Tokens::BLOCK_TYPE_CURLY_BRACE;
                } else {
                    $blockType = Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE;
                }

                $searchEndIndex = $tokens->findBlockEnd($blockType, $searchEndIndex);

                continue;
            }

            if ($searchEndToken->equalsAny([';', ',', '}', [T_CLOSE_TAG]])) {
                $endIndex = $tokens->getPrevMeaningfulToken($searchEndIndex);

                break;
            }
        }

        if (null !== $endIndex) {
            return $endIndex;
        }

        return $tokens->getPrevMeaningfulToken($parentScopeEndIndex);
    }

    private function findCaseBlockEnd(Tokens $tokens, $index)
    {
        $alternativeSyntaxAnalyzer = new AlternativeSyntaxAnalyzer();

        for ($max = \count($tokens); $index < $max; ++$index) {
            if ($tokens[$index]->isGivenKind(T_SWITCH)) {
                $braceIndex = $tokens->getNextMeaningfulToken(
                    $tokens->findBlockEnd(
                        Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                        $tokens->getNextMeaningfulToken($index)
                    )
                );

                if ($tokens[$braceIndex]->equals(':')) {
                    $index = $alternativeSyntaxAnalyzer->findAlternativeSyntaxBlockEnd($tokens, $index);
                } else {
                    $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $braceIndex);
                }

                continue;
            }

            if ($tokens[$index]->equalsAny([[T_CASE], [T_DEFAULT]])) {
                return $index;
            }

            if ($tokens[$index]->equalsAny(['}', [T_ENDSWITCH]])) {
                return $tokens->getPrevMeaningfulToken($index);
            }
        }

        throw new \LogicException('End of case block not found.');
    }

    private function getLineIndentation(Tokens $tokens, $index)
    {
        if (
            $this->bracesFixerCompatibility
            && $tokens[$index]->isGivenKind(T_OPEN_TAG)
            && Preg::match('/\R/', $tokens[$index]->getContent())
            && isset($tokens[$index + 1])
            && $tokens[$index + 1]->isWhitespace()
            && Preg::match('/\h+$/D', $tokens[$index + 1]->getContent())
        ) {
            return Preg::replace('/.*?(\h+)$/D', '$1', $tokens[$index + 1]->getContent());
        }

        $newlineTokenIndex = $this->getPreviousNewlineTokenIndex($tokens, $index);

        if (null === $newlineTokenIndex) {
            return '';
        }

        return $this->extractIndent($this->computeNewLineContent($tokens, $newlineTokenIndex));
    }

    private function extractIndent($content)
    {
        if (Preg::match('/\R([\t ]*)[^\r\n]*$/D', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function getPreviousNewlineTokenIndex(Tokens $tokens, $index)
    {
        while ($index > 0) {
            $index = $tokens->getPrevTokenOfKind($index, [[T_WHITESPACE], [T_INLINE_HTML]]);

            if (null === $index) {
                break;
            }

            if ($this->isNewLineToken($tokens, $index)) {
                return $index;
            }
        }

        return null;
    }

    private function isNewLineToken(Tokens $tokens, $index)
    {
        $token = $tokens[$index];

        if (
            $token->isGivenKind(T_OPEN_TAG)
            && isset($tokens[$index + 1])
            && !$tokens[$index + 1]->isWhitespace()
            && Preg::match('/\R/', $token->getContent())
        ) {
            return true;
        }

        if (!$token->isGivenKind(T_WHITESPACE)) {
            return false;
        }

        return (bool) Preg::match('/\R/', $this->computeNewLineContent($tokens, $index));
    }

    private function computeNewLineContent(Tokens $tokens, $index)
    {
        $content = $tokens[$index]->getContent();

        if (0 !== $index && $tokens[$index - 1]->equalsAny([[T_OPEN_TAG], [T_CLOSE_TAG]])) {
            $content = Preg::replace('/\S/', '', $tokens[$index - 1]->getContent()).$content;
        }

        return $content;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function isCommentForControlSructureContinuation(Tokens $tokens, $index)
    {
        if (!isset($tokens[$index], $tokens[$index + 1])) {
            return false;
        }

        if (!$tokens[$index]->isComment() || 1 !== Preg::match('~^(//|#)~', $tokens[$index]->getContent())) {
            return false;
        }

        if (!$tokens[$index + 1]->isWhitespace() || 1 !== Preg::match('/\R/', $tokens[$index + 1]->getContent())) {
            return false;
        }

        $index = $tokens->getNextMeaningfulToken($index + 1);

        if (null === $index || !$tokens[$index]->equals('}')) {
            return false;
        }

        $index = $tokens->getNextMeaningfulToken($index);

        return null !== $index && $tokens[$index]->equalsAny([[T_ELSE], [T_ELSEIF], ',']);
    }

    /**
     * Returns whether the token at given index is a comment whose indentation
     * can be fixed.
     *
     * Indentation of a comment is not changed when the comment is part of a
     * multi-line message whose lines are all single-line comments and at least
     * one line has meaningful content.
     *
     * @param int $index
     *
     * @return bool
     */
    private function isCommentWithFixableIndentation(Tokens $tokens, $index)
    {
        if (!$tokens[$index]->isComment()) {
            return false;
        }

        if (0 === strpos($tokens[$index]->getContent(), '/*')) {
            return true;
        }

        $indent = preg_quote($this->whitespacesConfig->getIndent(), '~');

        if (1 === Preg::match("~^(//|#)({$indent}.*)?$~", $tokens[$index]->getContent())) {
            return false;
        }

        $firstCommentIndex = $index;
        while (true) {
            $i = $this->getSiblingContinuousSingleLineComment($tokens, $firstCommentIndex, false);
            if (null === $i) {
                break;
            }

            $firstCommentIndex = $i;
        }

        $lastCommentIndex = $index;
        while (true) {
            $i = $this->getSiblingContinuousSingleLineComment($tokens, $lastCommentIndex, true);
            if (null === $i) {
                break;
            }

            $lastCommentIndex = $i;
        }

        if ($firstCommentIndex === $lastCommentIndex) {
            return true;
        }

        for ($i = $firstCommentIndex + 1; $i < $lastCommentIndex; ++$i) {
            if (!$tokens[$i]->isWhitespace() && !$tokens[$i]->isComment()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int  $index
     * @param bool $after
     *
     * @return null|int
     */
    private function getSiblingContinuousSingleLineComment(Tokens $tokens, $index, $after)
    {
        $siblingIndex = $index;
        do {
            if ($after) {
                $siblingIndex = $tokens->getNextTokenOfKind($siblingIndex, [[T_COMMENT]]);
            } else {
                $siblingIndex = $tokens->getPrevTokenOfKind($siblingIndex, [[T_COMMENT]]);
            }

            if (null === $siblingIndex) {
                return null;
            }
        } while (0 === strpos($tokens[$siblingIndex]->getContent(), '/*'));

        $newLines = 0;
        for ($i = min($siblingIndex, $index) + 1, $max = max($siblingIndex, $index); $i < $max; ++$i) {
            if ($tokens[$i]->isWhitespace() && Preg::match('/\R/', $tokens[$i]->getContent())) {
                if (1 === $newLines || Preg::match('/\R.*\R/', $tokens[$i]->getContent())) {
                    return null;
                }

                ++$newLines;
            }
        }

        return $siblingIndex;
    }
}
