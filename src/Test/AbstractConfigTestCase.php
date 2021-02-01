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

namespace PhpCsFixer\Test;

use PhpCsFixer\ConfigInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet;
use PHPUnit\Framework\TestCase;

abstract class AbstractConfigTestCase extends TestCase
{
    final protected function doTestAllBuiltInRulesAreConfigured(ConfigInterface $config)
    {
        $configuredRules = $config->getRules();

        $configuredAndResolvedRules = $this->resolveConfiguredRules($configuredRules);
        $availableRules = $this->getOrderedAvailableRules($config);

        $this->assertAllAvailbleRulesAreConfigured($configuredAndResolvedRules, $availableRules);
        $this->assertAllConfiguredRulesExist($configuredAndResolvedRules, $availableRules);
        $this->assertSetDefinitionsAreConfiguredBeforeRules($configuredRules);
        $this->assertSetDefinitionsAreOrderedAsTheyAppearInRuleSetClass($configuredRules);
        $this->assertConfiguredPlainRulesAreInAlphabeticalOrder($configuredRules);
    }

    private function resolveConfiguredRules(array $configuredRules)
    {
        $ruleSet = new RuleSet($configuredRules);
        $ruleSetResolvedRules = $ruleSet->getRules();

        // RuleSet strips all disabled rules
        foreach ($configuredRules as $name => $value) {
            if ('@' === $name[0]) {
                continue;
            }
            $ruleSetResolvedRules[$name] = $value;
        }

        $configuredAndResolvedRules = array_keys($ruleSetResolvedRules);
        sort($configuredAndResolvedRules);

        return $configuredAndResolvedRules;
    }

    private function getOrderedAvailableRules(ConfigInterface $config)
    {
        $fixerFactory = new FixerFactory();
        $fixerFactory->registerBuiltInFixers();
        $fixerFactory->registerCustomFixers($config->getCustomFixers());
        $fixers = $fixerFactory->getFixers();

        $availableFixers = array_filter($fixers, static function (FixerInterface $fixer) {
            return !$fixer instanceof DeprecatedFixerInterface;
        });
        $availableRules = array_map(static function (FixerInterface $fixer) {
            return $fixer->getName();
        }, $availableFixers);
        sort($availableRules);

        return $availableRules;
    }

    private function assertAllAvailbleRulesAreConfigured(array $configuredAndResolvedRules, array $availableRules)
    {
        $diff = array_diff($availableRules, $configuredAndResolvedRules);
        static::assertEmpty($diff, sprintf("The following fixers are missing:\n- %s", implode(\PHP_EOL.'- ', $diff)));
    }

    private function assertAllConfiguredRulesExist(array $configuredAndResolvedRules, array $availableRules)
    {
        $diff = array_diff($configuredAndResolvedRules, $availableRules);
        static::assertEmpty($diff, sprintf("The following fixers are specified but non existing or deprecated:\n- %s", implode(\PHP_EOL.'- ', $diff)));
    }

    private function assertSetDefinitionsAreConfiguredBeforeRules(array $configuredRules)
    {
        $configuredSetDefinitions = $this->extractConfiguredSetDefinitionsAsValues($configuredRules);
        $configuredPlainRules = $this->extractConfiguredPlainRulesAsValues($configuredRules);
        $setDefinitionsBeforeRulesOrderedRules = array_merge($configuredSetDefinitions, $configuredPlainRules);

        static::assertSame($setDefinitionsBeforeRulesOrderedRules, array_keys($configuredRules), 'Set definitions must appear before rules to allow rule overwrite');
    }

    private function assertSetDefinitionsAreOrderedAsTheyAppearInRuleSetClass(array $configuredRules)
    {
        $configuredSetDefinitions = $this->extractConfiguredSetDefinitionsAsValues($configuredRules);
        $defaultSetDefinitions = (new RuleSet())->getSetDefinitionNames();
        $intersectSets = array_values(array_intersect($defaultSetDefinitions, $configuredSetDefinitions));
        static::assertSame($intersectSets, $configuredSetDefinitions, sprintf('Set definitions must be ordered as they appear in %s to ensure rules are configured in progressive enhancement', RuleSet::class));
    }

    private function assertConfiguredPlainRulesAreInAlphabeticalOrder(array $configuredRules)
    {
        $configuredPlainRules = $this->extractConfiguredPlainRulesAsValues($configuredRules);
        $orderedRules = $configuredPlainRules;
        sort($orderedRules);
        static::assertSame($orderedRules, $configuredPlainRules, 'Fixers must appear in alphabetical order');
    }

    private function extractConfiguredSetDefinitionsAsValues(array $configuredRules)
    {
        return array_values(array_filter(array_keys($configuredRules), static function ($fixerName) {
            return isset($fixerName[0]) && '@' === $fixerName[0];
        }));
    }

    private function extractConfiguredPlainRulesAsValues(array $configuredRules)
    {
        return array_values(array_filter(array_keys($configuredRules), static function ($fixerName) {
            return isset($fixerName[0]) && '@' !== $fixerName[0];
        }));
    }
}
