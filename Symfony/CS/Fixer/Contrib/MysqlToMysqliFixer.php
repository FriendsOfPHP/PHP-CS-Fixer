<?php

namespace Symfony\CS\Fixer\Contrib;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;
use SplFileInfo;

class MysqlToMysqliFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(SplFileInfo $file, $content)
    {
        $tokens     = Tokens::fromCode($content);
        $position   = 0;
        $end        = $tokens->count() - 1;

        foreach ($this->deprecatedMysqlFunctions() as $deprecatedMysqlFunction => $replacementInformation) {
            $match = $tokens->findSequence(array(array(T_STRING, $deprecatedMysqlFunction), '('), $position, $end, false);

            if (null === $match) {
                continue;
            }

            $meaningfulPositions = array_keys($match);
            $position = end($meaningfulPositions);
            $replaceIf = $replacementInformation['replaceIf'];
            $numberOfArguments = $this->numberOfArgumentsFrom($position, $tokens);

            if ($replaceIf($numberOfArguments)) {
                $tokens[$meaningfulPositions[0]]->setContent($replacementInformation['replaceFor']);
            }
        }

        return $tokens->generateCode();
    }

    public function getName()
    {
        return 'mysql_to_mysqli';
    }

    /**
     * Returns the description of the fixer.
     *
     * A short one-line description of what the fixer does.
     *
     * @return string The description of the fixer
     */
    public function getDescription()
    {
        return 'Replace deprecated `mysql_*` functions with mysqli_* equivalents. Warning: This could change code behaviour.';
    }

    private function numberOfArgumentsFrom($position, Tokens $tokens)
    {
        $numberOfArguments = 0;
        $index = $tokens->getNextMeaningfulToken($position);

        if ($tokens[$index]->equals(')')) {
            return $numberOfArguments;
        }

        $numberOfArguments++;
        $dontBreakLoop = false;

        while (!$dontBreakLoop) {
            $index = $tokens->getNextMeaningfulToken($position);

            $dontBreakLoop = $tokens[$index]->equals(')');

            if (!$dontBreakLoop) {
                $position = $index;

                if ($tokens[$index]->equals(',')) {
                    $numberOfArguments++;
                }
            }
        }

        return $numberOfArguments;
    }

    private function deprecatedMysqlFunctions()
    {
        return array(
            'mysql_connect' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments <= 3;
                },
                'replaceFor' => 'mysqli_connect',
            ),
            'mysql_affected_rows' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_affected_rows',
            ),
            'mysql_client_encoding' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_character_set_name',
            ),
            'mysql_close' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_close',
            ),
            'mysql_data_seek' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_data_seek',
            ),
            'mysql_errno' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_errno',
            ),
            'mysql_error' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_error',
            ),
            'mysql_fetch_array' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_fetch_array',
            ),
            'mysql_fetch_assoc' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_fetch_assoc',
            ),
            'mysql_fetch_field' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return $numberOfArguments === 1;
                },
                'replaceFor' => 'mysqli_fetch_field',
            ),
            'mysql_fetch_lengths' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_fetch_lengths',
            ),
            'mysql_fetch_object' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_fetch_object',
            ),
            'mysql_fetch_row' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_fetch_row',
            ),
            'mysql_field_seek' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_field_seek',
            ),
            'mysql_free_result' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_free_result',
            ),
            'mysql_get_host_info' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_get_host_info',
            ),
            'mysql_get_proto_info' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_get_proto_info',
            ),
            'mysql_get_server_info' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_get_server_info',
            ),
            'mysql_info' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_info',
            ),
            'mysql_insert_id' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_insert_id',
            ),
            'mysql_num_fields' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_num_fields',
            ),
            'mysql_num_rows' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return true;
                },
                'replaceFor' => 'mysqli_num_rows',
            ),
            'mysql_ping' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_ping',
            ),
            'mysql_stat' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_stat',
            ),
            'mysql_thread_id' => array(
                'replaceIf' => function ($numberOfArguments) {
                    return 1 === $numberOfArguments;
                },
                'replaceFor' => 'mysqli_thread_id',
            ),
        );
    }
}
