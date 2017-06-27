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

namespace PhpCsFixer\Tests\AutoReview;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerConfiguration\FixerOption;
use PhpCsFixer\FixerDefinition\FileSpecificCodeSampleInterface;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSampleInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 *
 * @internal
 *
 * @coversNothing
 * @group auto-review
 */
final class FixerTest extends TestCase
{
    // do not modify this structure without prior discussion
    private $allowedRequiredOptions = array(
        'header_comment' => array('header' => true),
    );

    /**
     * @param FixerInterface $fixer
     *
     * @dataProvider provideFixerDefinitionsCases
     */
    public function testFixerDefinitions(FixerInterface $fixer)
    {
        $this->assertInstanceOf('PhpCsFixer\Fixer\DefinedFixerInterface', $fixer);

        /** @var DefinedFixerInterface $fixer */
        $fixerName = $fixer->getName();
        $definition = $fixer->getDefinition();
        $fixerIsConfigurable = $fixer instanceof ConfigurationDefinitionFixerInterface;

        $this->assertRegExp('/^[A-Z@].*\.$/', $definition->getSummary(), sprintf('[%s] Description must start with capital letter or an @ and end with dot.', $fixerName));

        $samples = $definition->getCodeSamples();
        $this->assertNotEmpty($samples, sprintf('[%s] Code samples are required.', $fixerName));

        $configSamplesProvided = array();
        $dummyFileInfo = new StdinFileInfo();
        foreach ($samples as $sampleCounter => $sample) {
            $this->assertInstanceOf('PhpCsFixer\FixerDefinition\CodeSampleInterface', $sample, sprintf('[%s] Sample #%d', $fixerName, $sampleCounter));
            $this->assertInternalType('int', $sampleCounter);

            $code = $sample->getCode();
            $this->assertStringIsNotEmpty($code, sprintf('[%s] Sample #%d', $fixerName, $sampleCounter));

            $config = $sample->getConfiguration();
            if (null !== $config) {
                $this->assertTrue($fixerIsConfigurable, sprintf('[%s] Sample #%d has configuration, but the fixer is not configurable.', $fixerName, $sampleCounter));
                $this->assertInternalType('array', $config, sprintf('[%s] Sample #%d configuration must be an array or null.', $fixerName, $sampleCounter));

                $configSamplesProvided[$sampleCounter] = $config;
            } elseif ($fixerIsConfigurable) {
                if (!$sample instanceof VersionSpecificCodeSampleInterface) {
                    $this->assertArrayNotHasKey('default', $configSamplesProvided, sprintf('[%s] Multiple non-versioned samples with default configuration.', $fixerName));
                }

                $configSamplesProvided['default'] = true;
            }

            if ($sample instanceof VersionSpecificCodeSampleInterface && !$sample->isSuitableFor(PHP_VERSION_ID)) {
                continue;
            }

            if ($fixerIsConfigurable) {
                // always re-configure as the fixer might have been configured with diff. configuration form previous sample
                $fixer->configure(null === $config ? array() : $config);
            }

            Tokens::clearCache();
            $tokens = Tokens::fromCode($code);
            $fixer->fix(
                $sample instanceof FileSpecificCodeSampleInterface ? $sample->getSplFileInfo() : $dummyFileInfo,
                $tokens
            );

            $this->assertTrue($tokens->isChanged(), sprintf('[%s] Sample #%d is not changed during fixing.', $fixerName, $sampleCounter));

            $duplicatedCodeSample = array_search(
                $sample,
                array_slice($samples, 0, $sampleCounter),
                false
            );

            $this->assertFalse(
                $duplicatedCodeSample,
                sprintf('[%s] Sample #%d duplicates #%d.', $fixerName, $sampleCounter, $duplicatedCodeSample)
            );
        }

        if ($fixerIsConfigurable) {
            if (isset($configSamplesProvided['default'])) {
                reset($configSamplesProvided);
                $this->assertSame('default', key($configSamplesProvided), sprintf('[%s] First sample must be for the default configuration.', $fixerName));
            } else {
                $this->assertArrayHasKey($fixerName, $this->allowedRequiredOptions, sprintf('[%s] Has no sample for default configuration.', $fixerName));
            }

            // test no duplicate configuration sample

            foreach ($configSamplesProvided as $sampleCount => $config) {
                foreach ($configSamplesProvided as $sampleCount2 => $config2) {
                    if ($sampleCount === $sampleCount2) {
                        continue;
                    }

                    $this->assertNotSame($config, $config2, sprintf('[%s] Has multiple samples with the same configuration, please merges the samples.', $fixerName));
                }
            }

            // test that all configuration options are used at least one time in the samples

            /** @var FixerOption[] $options */
            $options = $fixer->getConfigurationDefinition()->getOptions();

            if (count($options)) {
                $optionsNames = array();

                foreach ($options as $option) {
                    $optionsNames[$option->getName()] = 0;
                }

                foreach ($samples as $sample) {
                    $config = $sample->getConfiguration();

                    if (is_array($config)) {
                        foreach ($config as $name => $value) {
                            $this->assertArrayHasKey($name, $optionsNames, sprintf('[%s] Sample uses unknown option with name "%s", expected any of "%s".', $fixerName, $name, implode('","', array_keys($optionsNames))));
                            ++$optionsNames[$name];
                        }
                    }
                }

                $optionsNames = array_filter(
                    $optionsNames,
                    function ($count) {
                        return 0 === $count;
                    }
                );

                $this->assertCount(0, $optionsNames, sprintf('[%s] No sample with configuration options "%s".', $fixerName, implode('", "', array_keys($optionsNames))));
            }
        }

        if ($fixer->isRisky()) {
            $this->assertStringIsNotEmpty($definition->getRiskyDescription(), sprintf('[%s] Risky reasoning is required.', $fixerName));
        } else {
            $this->assertNull($definition->getRiskyDescription(), sprintf('[%s] Fixer is not risky so no description of it expected.', $fixerName));
        }
    }

    /**
     * @param FixerInterface $fixer
     *
     * @group legacy
     * @dataProvider provideFixerDefinitionsCases
     * @expectedDeprecation PhpCsFixer\FixerDefinition\FixerDefinition::getConfigurationDescription is deprecated and will be removed in 3.0.
     * @expectedDeprecation PhpCsFixer\FixerDefinition\FixerDefinition::getDefaultConfiguration is deprecated and will be removed in 3.0.
     */
    public function testLegacyFixerDefinitions(FixerInterface $fixer)
    {
        $definition = $fixer->getDefinition();

        $this->assertNull($definition->getConfigurationDescription(), sprintf('[%s] No configuration description expected.', $fixer->getName()));
        $this->assertNull($definition->getDefaultConfiguration(), sprintf('[%s] No default configuration expected.', $fixer->getName()));
    }

    /**
     * @dataProvider provideFixerDefinitionsCases
     */
    public function testFixersAreFinal(FixerInterface $fixer)
    {
        $reflection = new \ReflectionClass($fixer);

        $this->assertTrue(
            $reflection->isFinal(),
            sprintf('Fixer "%s" must be declared "final".', $fixer->getName())
        );
    }

    /**
     * @dataProvider provideFixerDefinitionsCases
     */
    public function testFixersAreDefined(FixerInterface $fixer)
    {
        $this->assertInstanceOf('PhpCsFixer\Fixer\DefinedFixerInterface', $fixer);
    }

    public function provideFixerDefinitionsCases()
    {
        return array_map(function (FixerInterface $fixer) {
            return array($fixer);
        }, $this->getAllFixers());
    }

    /**
     * @param ConfigurationDefinitionFixerInterface $fixer
     *
     * @dataProvider provideFixerConfigurationDefinitionsCases
     */
    public function testFixerConfigurationDefinitions(ConfigurationDefinitionFixerInterface $fixer)
    {
        $configurationDefinition = $fixer->getConfigurationDefinition();

        $this->assertInstanceOf('PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface', $configurationDefinition);

        foreach ($configurationDefinition->getOptions() as $option) {
            $this->assertInstanceOf('PhpCsFixer\FixerConfiguration\FixerOption', $option);
            $this->assertNotEmpty($option->getDescription());

            $this->assertSame(
                !isset($this->allowedRequiredOptions[$fixer->getName()][$option->getName()]),
                $option->hasDefault(),
                sprintf(
                    $option->hasDefault()
                        ? 'Option `%s` of fixer `%s` is wrongly listed in `$allowedRequiredOptions` structure, as it is not required. If you just changed that option to not be required anymore, please adjust mentioned structure.'
                        : 'Option `%s` of fixer `%s` shall not be required. If you want to introduce new required option please adjust `$allowedRequiredOptions` structure.',
                    $option->getName(),
                    $fixer->getName()
                )
            );
        }
    }

    public function provideFixerConfigurationDefinitionsCases()
    {
        $fixers = array_filter($this->getAllFixers(), function (FixerInterface $fixer) {
            return $fixer instanceof ConfigurationDefinitionFixerInterface;
        });

        return array_map(function (FixerInterface $fixer) {
            return array($fixer);
        }, $fixers);
    }

    private function getAllFixers()
    {
        $factory = new FixerFactory();

        return $factory->registerBuiltInFixers()->getFixers();
    }

    /**
     * copy paste from GeckoPackages/GeckoPHPUnit StringsAssertTrait, to replace with Trait when possible.
     *
     * @param mixed  $actual
     * @param string $message
     */
    private static function assertStringIsNotEmpty($actual, $message = '')
    {
        self::assertInternalType('string', $actual, $message);
        self::assertNotEmpty($actual, $message);
    }
}
