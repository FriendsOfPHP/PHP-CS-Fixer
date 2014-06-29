<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class Tokens extends \SplFixedArray
{
    /**
     * Create token collection from array.
     *
     * @param  array  $array       the array to import
     * @param  bool   $saveIndexes save the numeric indexes used in the original array, default is yes
     * @return Tokens
     */
     public static function fromArray($array, $saveIndexes = null)
     {
        $tokens = new Tokens(count($array));

        if (null === $saveIndexes || $saveIndexes) {
            foreach ($array as $key => $val) {
                $tokens[$key] = $val;
            }

            return $tokens;
        }

        $index = 0;

        foreach ($array as $val) {
            $tokens[$index++] = $val;
        }

        return $tokens;
     }

    /**
     * Create token collection directly from code.
     *
     * @param  string $code PHP code
     * @return Tokens
     */
    public static function fromCode($code)
    {
        return static::fromArray(token_get_all($code));
    }

    /**
     * Check if token is one of type cast tokens.
     *
     * @param  string|array $token token element generated by token_get_all
     * @return bool
     */
    public static function isCast($token)
    {
        static $castTokens = array(
            T_INT_CAST, T_BOOL_CAST, T_DOUBLE_CAST, T_DOUBLE_CAST, T_DOUBLE_CAST,
            T_STRING_CAST, T_ARRAY_CAST, T_OBJECT_CAST, T_UNSET_CAST
        );

        return is_array($token) && in_array($token[0], $castTokens);
    }

    /**
     * Check if token is one of classy tokens: T_CLASS, T_INTERFACE or T_TRAIT.
     *
     * @param  string|array $token token element generated by token_get_all
     * @return bool
     */
    public static function isClassy($token)
    {
        static $classTokens = array('T_CLASS', 'T_INTERFACE', 'T_TRAIT');

        return is_array($token) && in_array(token_name($token[0]), $classTokens);
    }

    /**
     * Check if token is one of comment tokens: T_COMMENT or T_DOC_COMMENT.
     *
     * @param  string|array $token token element generated by token_get_all
     * @return bool
     */
    public static function isComment($token)
    {
        static $commentTokens = array(T_COMMENT, T_DOC_COMMENT);

        return is_array($token) && in_array($token[0], $commentTokens);
    }

    /**
     * Check if token is a native PHP constant: true, false or null.
     *
     * @param  string|array $token token element generated by token_get_all
     * @return bool
     */
    public static function isNativeConstant($token)
    {
        static $nativeConstantStrings = array("true", "false", "null");

        return is_array($token) && in_array(strtolower($token[1]), $nativeConstantStrings);
    }

    /**
     * Check if token is a keyword.
     *
     * @param  string|array $token token element generated by token_get_all
     * @return bool
     */
    public static function isKeyword($token)
    {
        $keywords = static::getKeywords();

        return is_array($token) && isset($keywords[$token[0]]);
    }

    /**
     * Check if token is a whitespace.
     *
     * @param  string|array $token               token element generated by token_get_all
     * @param  array        $opts                array of extra options
     * @param  string       $opts['whitespaces'] string determining whitespaces chars, default is " \t"
     * @return bool
     */
    public static function isWhitespace($token, array $opts = array())
    {
        $whitespaces = isset($opts['whitespaces']) ? $opts['whitespaces'] : " \t";

        return
            (is_string($token) && '' === trim($token, $whitespaces))
                ||
            (is_array($token) && T_WHITESPACE === $token[0] && '' === trim($token[1], $whitespaces))
        ;
    }

    /**
     * Generate keywords array contains all keywords that exists in used PHP version.
     *
     * @return array
     */
    public static function getKeywords()
    {
        static $keywords = null;

        if (null === $keywords) {
            $keywords = array();
            $keywordsStrings = array('T_ABSTRACT', 'T_ARRAY', 'T_AS', 'T_BREAK', 'T_CALLABLE', 'T_CASE',
                'T_CATCH', 'T_CLASS', 'T_CLONE', 'T_CONST', 'T_CONTINUE', 'T_DECLARE', 'T_DEFAULT', 'T_DO',
                'T_ECHO', 'T_ELSE', 'T_ELSEIF', 'T_EMPTY', 'T_ENDDECLARE', 'T_ENDFOR', 'T_ENDFOREACH',
                'T_ENDIF', 'T_ENDSWITCH', 'T_ENDWHILE', 'T_EVAL', 'T_EXIT', 'T_EXTENDS', 'T_FINAL',
                'T_FINALLY', 'T_FOR', 'T_FOREACH', 'T_FUNCTION', 'T_GLOBAL', 'T_GOTO', 'T_HALT_COMPILER',
                'T_IF', 'T_IMPLEMENTS', 'T_INCLUDE', 'T_INCLUDE_ONCE', 'T_INSTANCEOF', 'T_INSTEADOF',
                'T_INTERFACE', 'T_ISSET', 'T_LIST', 'T_LOGICAL_AND', 'T_LOGICAL_OR', 'T_LOGICAL_XOR',
                'T_NAMESPACE', 'T_NEW', 'T_PRINT', 'T_PRIVATE', 'T_PROTECTED', 'T_PUBLIC', 'T_REQUIRE',
                'T_REQUIRE_ONCE', 'T_RETURN', 'T_STATIC', 'T_SWITCH', 'T_THROW', 'T_TRAIT', 'T_TRY',
                'T_UNSET', 'T_USE', 'T_VAR', 'T_WHILE', 'T_YIELD'
            );

            foreach ($keywordsStrings as $keywordName) {
                if (defined($keywordName)) {
                    $keyword = constant($keywordName);
                    $keywords[$keyword] = $keyword;
                }
            }
        }

        return $keywords;
    }

    /**
     * Apply token attributes.
     * Token at given index is prepended by attributes.
     *
     * @param int   $index   token index
     * @param array $attribs array of token attributes
     */
    public function applyAttribs($index, $attribs)
    {
        $attribsString = '';

        foreach ($attribs as $attrib) {
            if ($attrib) {
                $attribsString .= $attrib.' ';
            }
        }

        $this[$index] = $attribsString.$this[$index][1];
    }

    /**
     * Clear token at given index.
     * Clearing means override token by empty string.
     *
     * @param int $index token index
     */
    public function clear($index)
    {
        $this[$index] = '';
    }

    /**
     * Generate code from tokens.
     *
     * @return string
     */
    public function generateCode()
    {
        $code = '';
        $this->rewind();

        foreach ($this as $token) {
            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    /**
     * Get closest sibling token which is non whitespace.
     *
     * @param  string|array $index     token index
     * @param  int          $direction direction for looking, +1 or -1
     * @param  array        $opts      array of extra options for isWhitespace method
     * @return string|array token
     */
    public function getNonWhitespaceSibling($index, $direction, array $opts = array())
    {
        while (true) {
            $index += $direction;

            if (!$this->offsetExists($index)) {
                return null;
            }

            $token = $this[$index];

            if (!static::isWhitespace($token, $opts)) {
                return $token;
            }
        }
    }

    /**
     * Get closest next token which is non whitespace.
     * This method is shorthand for getNonWhitespaceSibling method.
     *
     * @param  string|array $index token index
     * @param  array        $opts  array of extra options for isWhitespace method
     * @return string|array token
     */
    public function getNextNonWhitespace($index, array $opts = array())
    {
        return $this->getNonWhitespaceSibling($index, 1, $opts);
    }

    /**
     * Get closest previous token which is non whitespace.
     * This method is shorthand for getNonWhitespaceSibling method.
     *
     * @param  string|array $index token index
     * @param  array        $opts  array of extra options for isWhitespace method
     * @return string|array token
     */
    public function getPrevNonWhitespace($index, array $opts = array())
    {
        return $this->getNonWhitespaceSibling($index, -1, $opts);
    }

    /**
     * Grab attributes before token at gixen index.
     * Grabbed attributes are cleared by overriding them with empty string and should be manually applied with applyTokenAttribs method.
     *
     * @param  int   $index           token index
     * @param  array $tokenAttribsMap token to attribute name map
     * @param  array $attribs         array of token attributes
     * @return array array of grabbed attributes
     */
    public function grabAttribsBeforeToken($index, $tokenAttribsMap, $attribs)
    {
        while (true) {
            $token = $this[--$index];

            if (!is_array($token)) {
                if (in_array($token, array('{', '}', '(', ')', ))) {
                    break;
                }

                continue;
            }

            // if token is attribute
            if (array_key_exists($token[0], $tokenAttribsMap)) {
                // set token attribute if token map defines attribute name for token
                if ($tokenAttribsMap[$token[0]]) {
                    $attribs[$tokenAttribsMap[$token[0]]] = $token[1];
                }

                // clear the token and whitespaces after it
                $this->clear($index);
                $this->clear($index + 1);

                continue;
            }

            if (in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, ))) {
                continue;
            }

            break;
        }

        return $attribs;
    }

    /**
     * Grab attributes before method token at gixen index.
     * It's a shorthand for grabAttribsBeforeToken method.
     *
     * @param  int   $index token index
     * @return array array of grabbed attributes
     */
    public function grabAttribsBeforeMethodToken($index)
    {
        static $tokenAttribsMap = array(
            T_PRIVATE => 'visibility',
            T_PROTECTED => 'visibility',
            T_PUBLIC => 'visibility',
            T_ABSTRACT => 'abstract',
            T_FINAL => 'final',
            T_STATIC => 'static',
        );

        return $this->grabAttribsBeforeToken(
            $index,
            $tokenAttribsMap,
            array(
                'abstract' => '',
                'final' => '',
                'visibility' => 'public',
                'static' => '',
            )
        );
    }

    /**
     * Grab attributes before property token at gixen index.
     * It's a shorthand for grabAttribsBeforeToken method.
     *
     * @param  int   $index token index
     * @return array array of grabbed attributes
     */
    public function grabAttribsBeforePropertyToken($index)
    {
        static $tokenAttribsMap = array(
            T_VAR => null, // destroy T_VAR token!
            T_PRIVATE => 'visibility',
            T_PROTECTED => 'visibility',
            T_PUBLIC => 'visibility',
            T_STATIC => 'static',
        );

        return $this->grabAttribsBeforeToken(
            $index,
            $tokenAttribsMap,
            array(
                'visibility' => 'public',
                'static' => '',
            )
        );
    }
}
