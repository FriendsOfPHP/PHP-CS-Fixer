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

namespace PhpCsFixer\Fixer\Alias;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NoAliasFunctionsFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var array<string, array<int|string>|string> stores alias (key) - master (value) functions mapping */
    private $aliases = [];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $internalSet = [
        'dir' => 'getdir',
        'diskfreespace' => 'disk_free_space',

        'checkdnsrr' => 'dns_check_record',
        'getmxrr' => 'dns_get_mx',

        'session_commit' => 'session_write_close',

        'stream_register_wrapper' => 'stream_wrapper_register',
        'set_file_buffer' => 'stream_set_write_buffer',
        'socket_set_blocking' => 'stream_set_blocking',
        'socket_get_status' => 'stream_get_meta_data',
        'socket_set_timeout' => 'stream_set_timeout',
        'socket_getopt' => 'socket_get_option',
        'socket_setopt' => 'socket_set_option',

        'chop' => 'rtrim',
        'close' => 'closedir',
        'doubleval' => 'floatval',
        'fputs' => 'fwrite',
        'get_required_files' => 'get_included_files',
        'ini_alter' => 'ini_set',
        'is_double' => 'is_float',
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_writeable' => 'is_writable',
        'join' => 'implode',
        'key_exists' => 'array_key_exists',
        'magic_quotes_runtime' => 'set_magic_quotes_runtime',
        'pos' => 'current',
        'show_source' => 'highlight_file',
        'sizeof' => 'count',
        'strchr' => 'strstr',
        'user_error' => 'trigger_error',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $imapSet = [
        'imap_create' => 'imap_createmailbox',
        'imap_fetchtext' => 'imap_body',
        'imap_header' => 'imap_headerinfo',
        'imap_listmailbox' => 'imap_list',
        'imap_listsubscribed' => 'imap_lsub',
        'imap_rename' => 'imap_renamemailbox',
        'imap_scan' => 'imap_listscan',
        'imap_scanmailbox' => 'imap_listscan',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $snmpSet = [
        'snmpwalkoid' => 'snmprealwalk',
        'snmp_set_oid_numeric_print' => 'snmp_set_oid_output_format',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $ldapSet = [
        'ldap_close' => 'ldap_unbind',
        'ldap_get_values' => 'ldap_get_values_len',
        'ldap_modify' => 'ldap_mod_replace',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $mysqliSet = [
        'mysqli_execute' => 'mysqli_stmt_execute',
        'mysqli_set_opt' => 'mysqli_options',
        'mysqli_escape_string' => 'mysqli_real_escape_string',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $pgSet = [
        'pg_exec' => 'pg_query',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $ociSet = [
        'oci_free_cursor' => 'oci_free_statement',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $odbcSet = [
        'odbc_do' => 'odbc_exec',
        'odbc_field_precision' => 'odbc_field_len',
    ];

    /** @var array<string, string> stores alias (key) - master (value) functions mapping */
    private static $mbregSet = [
        'mbereg' => 'mb_ereg',
        'mbereg_match' => 'mb_ereg_match',
        'mbereg_replace' => 'mb_ereg_replace',
        'mbereg_search' => 'mb_ereg_search',
        'mbereg_search_getpos' => 'mb_ereg_search_getpos',
        'mbereg_search_getregs' => 'mb_ereg_search_getregs',
        'mbereg_search_init' => 'mb_ereg_search_init',
        'mbereg_search_pos' => 'mb_ereg_search_pos',
        'mbereg_search_regs' => 'mb_ereg_search_regs',
        'mbereg_search_setpos' => 'mb_ereg_search_setpos',
        'mberegi' => 'mb_eregi',
        'mberegi_replace' => 'mb_eregi_replace',
        'mbregex_encoding' => 'mb_regex_encoding',
        'mbsplit' => 'mb_split',
    ];

    private static $opensslSet = [
        'openssl_get_publickey' => 'openssl_pkey_get_public',
        'openssl_get_privatekey' => 'openssl_pkey_get_private',
    ];

    private static $sodiumSet = [
        'sodium_crypto_scalarmult_base' => 'sodium_crypto_box_publickey_from_secretkey',
    ];

    private static $exifSet = [
        'read_exif_data' => 'exif_read_data',
    ];

    private static $ftpSet = [
        'ftp_quit' => 'ftp_close',
    ];

    private static $posixSet = [
        'posix_errno' => 'posix_get_last_error',
    ];

    private static $pcntlSet = [
        'pcntl_errno' => 'pcntl_get_last_error',
    ];

    private static $timeSet = [
        'mktime' => ['time', 0],
        'gmmktime' => ['time', 0],
    ];

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->aliases = [];
        foreach ($this->configuration['sets'] as $set) {
            if ('@all' === $set) {
                $this->aliases = self::$internalSet;
                $this->aliases = array_merge($this->aliases, self::$imapSet);
                $this->aliases = array_merge($this->aliases, self::$mbregSet);
                $this->aliases = array_merge($this->aliases, self::$timeSet);
                $this->aliases = array_merge($this->aliases, self::$exifSet);
                $this->aliases = array_merge($this->aliases, self::$snmpSet);
                $this->aliases = array_merge($this->aliases, self::$ldapSet);
                $this->aliases = array_merge($this->aliases, self::$mysqliSet);
                $this->aliases = array_merge($this->aliases, self::$pgSet);
                $this->aliases = array_merge($this->aliases, self::$ociSet);
                $this->aliases = array_merge($this->aliases, self::$odbcSet);
                $this->aliases = array_merge($this->aliases, self::$opensslSet);
                $this->aliases = array_merge($this->aliases, self::$sodiumSet);
                $this->aliases = array_merge($this->aliases, self::$ftpSet);
                $this->aliases = array_merge($this->aliases, self::$posixSet);
                $this->aliases = array_merge($this->aliases, self::$pcntlSet);

                break;
            }

            if ('@internal' === $set) {
                $this->aliases = array_merge($this->aliases, self::$internalSet);
            } elseif ('@IMAP' === $set) {
                $this->aliases = array_merge($this->aliases, self::$imapSet);
            } elseif ('@mbreg' === $set) {
                $this->aliases = array_merge($this->aliases, self::$mbregSet);
            } elseif ('@time' === $set) {
                $this->aliases = array_merge($this->aliases, self::$timeSet);
            } elseif ('@exif' === $set) {
                $this->aliases = array_merge($this->aliases, self::$exifSet);
            } elseif ('@snmp' === $set) {
                $this->aliases = array_merge($this->aliases, self::$snmpSet);
            } elseif ('@ldap' === $set) {
                $this->aliases = array_merge($this->aliases, self::$ldapSet);
            } elseif ('@mysqli' === $set) {
                $this->aliases = array_merge($this->aliases, self::$mysqliSet);
            } elseif ('@pg' === $set) {
                $this->aliases = array_merge($this->aliases, self::$pgSet);
            } elseif ('@oci' === $set) {
                $this->aliases = array_merge($this->aliases, self::$ociSet);
            } elseif ('@odbc' === $set) {
                $this->aliases = array_merge($this->aliases, self::$odbcSet);
            } elseif ('@openssl' === $set) {
                $this->aliases = array_merge($this->aliases, self::$opensslSet);
            } elseif ('@sodium' === $set) {
                $this->aliases = array_merge($this->aliases, self::$sodiumSet);
            } elseif ('@ftp' === $set) {
                $this->aliases = array_merge($this->aliases, self::$ftpSet);
            } elseif ('@posix' === $set) {
                $this->aliases = array_merge($this->aliases, self::$posixSet);
            } elseif ('@pcntl' === $set) {
                $this->aliases = array_merge($this->aliases, self::$pcntlSet);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Master functions shall be used instead of aliases.',
            [
                new CodeSample(
                    '<?php
$a = chop($b);
close($b);
$a = doubleval($b);
$a = fputs($b, $c);
$a = get_required_files();
ini_alter($b, $c);
$a = is_double($b);
$a = is_integer($b);
$a = is_long($b);
$a = is_real($b);
$a = is_writeable($b);
$a = join($glue, $pieces);
$a = key_exists($key, $array);
magic_quotes_runtime($new_setting);
$a = pos($array);
$a = show_source($filename, true);
$a = sizeof($b);
$a = strchr($haystack, $needle);
$a = imap_header($imap_stream, 1);
user_error($message);
mbereg_search_getregs();
'
                ),
                new CodeSample(
                    '<?php
$a = is_double($b);
mbereg_search_getregs();
',
                    ['sets' => ['@mbreg']]
                ),
            ],
            null,
            'Risky when any of the alias functions are overridden.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before ImplodeCallFixer, PhpUnitDedicateAssertFixer.
     */
    public function getPriority()
    {
        return 40;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $functionsAnalyzer = new FunctionsAnalyzer();
        $argumentsAnalyzer = new ArgumentsAnalyzer();

        /** @var Token $token */
        foreach ($tokens->findGivenKind(T_STRING) as $index => $token) {
            // check mapping hit
            $tokenContent = strtolower($token->getContent());
            if (!isset($this->aliases[$tokenContent])) {
                continue;
            }

            // skip expressions without parameters list
            $openParenthesis = $tokens->getNextMeaningfulToken($index);

            if (!$tokens[$openParenthesis]->equals('(')) {
                continue;
            }

            if (!$functionsAnalyzer->isGlobalFunctionCall($tokens, $index)) {
                continue;
            }

            if (\is_array($this->aliases[$tokenContent])) {
                list($alias, $numberOfArguments) = $this->aliases[$tokenContent];

                $count = $argumentsAnalyzer->countArguments($tokens, $openParenthesis, $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis));

                if ($numberOfArguments !== $count) {
                    continue;
                }
            } else {
                $alias = $this->aliases[$tokenContent];
            }

            $tokens[$index] = new Token([T_STRING, $alias]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        $sets = [
            '@all' => 'all listed sets',
            '@internal' => 'native functions',
            '@IMAP' => 'IMAP functions',
            '@mbreg' => 'from `ext-mbstring`',
            '@time' => 'time functions',
            '@exif' => 'EXIF functions',
            '@snmp' => 'SNMP functions',
            '@ldap' => 'LDAP functions',
            '@mysqli' => 'mysqli functions',
            '@pg' => 'pg functions',
            '@oci' => 'oci functions',
            '@odbc' => 'odbc functions',
            '@openssl' => 'openssl functions',
            '@sodium' => 'libsodium functions',
            '@ftp' => 'FTP functions',
            '@posix' => 'POSIX functions',
            '@pcntl' => 'PCNTL functions',
        ];

        $list = '';
        foreach ($sets as $set => $description) {
            $list .= "* `{$set}` ({$description})\n";
        }

        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('sets', "List of sets to fix. Defined sets are:\n\n{$list}"))
                ->setAllowedTypes(['array'])
                ->setAllowedValues([new AllowedValueSubset(array_keys($sets))])
                ->setDefault(['@internal', '@IMAP', '@pg'])
                ->getOption(),
        ]);
    }
}
