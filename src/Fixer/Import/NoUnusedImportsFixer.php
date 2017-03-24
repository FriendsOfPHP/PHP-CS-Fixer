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

namespace PhpCsFixer\Fixer\Import;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NoUnusedImportsFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);
        $useDeclarationsIndexes = $tokensAnalyzer->getImportUseIndexes();

        if (0 === count($useDeclarationsIndexes)) {
            return;
        }

        $useDeclarations = $this->getNamespaceUseDeclarations($tokens, $useDeclarationsIndexes);
        $namespaceDeclarations = $this->getNamespaceDeclarations($tokens);
        $contentWithoutUseDeclarations = $this->generateCodeWithoutPartials($tokens, array_merge($namespaceDeclarations, $useDeclarations));
        $useUsages = $this->detectUseUsages($contentWithoutUseDeclarations, $useDeclarations);

        $this->removeUnusedUseDeclarations($tokens, $useDeclarations, $useUsages);
        $this->removeUsesInSameNamespace($tokens, $useDeclarations, $namespaceDeclarations);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Unused use statements must be removed.',
            array(new CodeSample("<?php\nuse \\DateTime;\nuse \\Exception;\n\nnew DateTime();"))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be run after the SingleImportPerStatementFixer
        return -10;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_USE);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(\SplFileInfo $file)
    {
        $path = $file->getPathname();

        // some fixtures are auto-generated by Symfony and may contain unused use statements
        if (false !== strpos($path, DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR) &&
            false === strpos($path, DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param string $content
     * @param array  $useDeclarations
     *
     * @return array
     */
    private function detectUseUsages($content, array $useDeclarations)
    {
        $usages = array();

        foreach ($useDeclarations as $shortName => $useDeclaration) {
            $usages[$shortName] = (bool) preg_match('/(?<![\$\\\\])(?<!->)\b'.preg_quote($shortName).'\b/i', $content);
        }

        return $usages;
    }

    /**
     * @param Tokens $tokens
     * @param array  $partials
     *
     * @return string
     */
    private function generateCodeWithoutPartials(Tokens $tokens, array $partials)
    {
        $content = '';

        foreach ($tokens as $index => $token) {
            $allowToAppend = true;

            foreach ($partials as $partial) {
                if ($partial['start'] <= $index && $index <= $partial['end']) {
                    $allowToAppend = false;
                    break;
                }
            }

            if ($allowToAppend) {
                $content .= $token->getContent();
            }
        }

        return $content;
    }

    private function getNamespaceDeclarations(Tokens $tokens)
    {
        $namespaces = array();

        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_NAMESPACE)) {
                continue;
            }

            $declarationEndIndex = $tokens->getNextTokenOfKind($index, array(';', '{'));

            $namespaces[] = array(
                'name' => trim($tokens->generatePartialCode($index + 1, $declarationEndIndex - 1)),
                'start' => $index,
                'end' => $declarationEndIndex,
            );
        }

        return $namespaces;
    }

    private function getNamespaceUseDeclarations(Tokens $tokens, array $useIndexes)
    {
        $uses = array();

        foreach ($useIndexes as $index) {
            $declarationEndIndex = $tokens->getNextTokenOfKind($index, array(';', array(T_CLOSE_TAG)));
            $declarationContent = $tokens->generatePartialCode($index + 1, $declarationEndIndex - 1);
            if (
                false !== strpos($declarationContent, ',')    // ignore multiple use statements that should be split into few separate statements (for example: `use BarB, BarC as C;`)
                || false !== strpos($declarationContent, '{') // do not touch group use declarations until the logic of this is added (for example: `use some\a\{ClassD};`)
            ) {
                continue;
            }

            $declarationParts = preg_split('/\s+as\s+/i', $declarationContent);

            if (1 === count($declarationParts)) {
                $fullName = $declarationContent;
                $declarationParts = explode('\\', $fullName);
                $shortName = end($declarationParts);
                $aliased = false;
            } else {
                list($fullName, $shortName) = $declarationParts;
                $declarationParts = explode('\\', $fullName);
                $aliased = $shortName !== end($declarationParts);
            }

            $shortName = trim($shortName);

            $uses[$shortName] = array(
                'fullName' => trim($fullName),
                'shortName' => $shortName,
                'aliased' => $aliased,
                'start' => $index,
                'end' => $declarationEndIndex,
            );
        }

        return $uses;
    }

    private function removeUnusedUseDeclarations(Tokens $tokens, array $useDeclarations, array $useUsages)
    {
        foreach ($useDeclarations as $shortName => $useDeclaration) {
            if (!$useUsages[$shortName]) {
                $this->removeUseDeclaration($tokens, $useDeclaration);
            }
        }
    }

    private function removeUseDeclaration(Tokens $tokens, array $useDeclaration)
    {
        for ($index = $useDeclaration['end'] - 1; $index >= $useDeclaration['start']; --$index) {
            $tokens->clearTokenAndMergeSurroundingWhitespace($index);
        }

        if ($tokens[$useDeclaration['end']]->equals(';')) {
            $tokens[$useDeclaration['end']]->clear();
        }

        $prevToken = $tokens[$useDeclaration['start'] - 1];

        if ($prevToken->isWhitespace()) {
            $prevToken->setContent(rtrim($prevToken->getContent(), " \t"));
        }

        if (!isset($tokens[$useDeclaration['end'] + 1])) {
            return;
        }

        $nextIndex = $useDeclaration['end'] + 1;
        $nextToken = $tokens[$nextIndex];

        if ($nextToken->isWhitespace()) {
            $content = ltrim($nextToken->getContent(), " \t");

            $content = preg_replace(
                "#^\r\n|^\n#",
                '',
                $content,
                1
            );

            $nextToken->setContent($content);
        }

        if ($prevToken->isWhitespace() && $nextToken->isWhitespace()) {
            $tokens->overrideAt($nextIndex, array(T_WHITESPACE, $prevToken->getContent().$nextToken->getContent()));
            $prevToken->clear();
        }
    }

    private function removeUsesInSameNamespace(Tokens $tokens, array $useDeclarations, array $namespaceDeclarations)
    {
        // safeguard for files with multiple namespaces to avoid breaking them until we support this case
        if (1 !== count($namespaceDeclarations)) {
            return;
        }

        $namespace = $namespaceDeclarations[0]['name'];
        $nsLength = strlen($namespace.'\\');

        foreach ($useDeclarations as $useDeclaration) {
            if ($useDeclaration['aliased']) {
                continue;
            }

            $useDeclarationFullName = ltrim($useDeclaration['fullName'], '\\');

            if (0 !== strpos($useDeclarationFullName, $namespace.'\\')) {
                continue;
            }

            $partName = substr($useDeclarationFullName, $nsLength);

            if (false === strpos($partName, '\\')) {
                $this->removeUseDeclaration($tokens, $useDeclaration);
            }
        }
    }
}
