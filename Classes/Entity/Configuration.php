<?php

declare(strict_types=1);

/*
 * This file is part of the "matomo_integration" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Brotkrueml\MatomoIntegration\Entity;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
final class Configuration
{
    private const SITE_CONFIGURATION_PREFIX = 'matomoIntegration';

    /** @readonly */
    public string $url = '';
    /** @readonly */
    public int $siteId = 0;
    /** @readonly */
    public bool $noScript = false;
    /** @readonly */
    public bool $cookieTracking = false;
    /** @readonly */
    public bool $doNotTrack = false;
    /** @readonly */
    public bool $heartBeatTimer = false;
    /** @readonly */
    public bool $linkTracking = false;
    /** @readonly */
    public bool $performanceTracking = false;
    /** @readonly */
    public bool $trackAllContentImpressions = false;
    /** @readonly */
    public bool $trackErrorPages = false;
    /** @readonly */
    public bool $trackVisibleContentImpressions = false;
    /** @readonly */
    public string $tagManagerContainerId = '';
    /** @readonly */
    public bool $tagManagerDebugMode = false;

    private function __construct()
    {
    }

    /**
     * @param array<string,bool|int|string> $siteConfiguration
     */
    public static function createFromSiteConfiguration(array $siteConfiguration): self
    {
        $configuration = new self();
        foreach ($siteConfiguration as $name => $value) {
            if (!\str_starts_with($name, self::SITE_CONFIGURATION_PREFIX)) {
                continue;
            }

            self::processConfiguration($configuration, $name, $value);
        }

        return $configuration;
    }

    /**
     * @param bool|int|string $value
     */
    private static function processConfiguration(self $configuration, string $name, $value): void
    {
        $property = self::mapConfigurationNameToClassProperty($name);
        if ($property === 'options') {
            self::processOptions($configuration, (string)$value);
            return;
        }

        if (!\property_exists(self::class, $property)) {
            return;
        }

        if ($property === 'url') {
            $value = self::normaliseUrl((string)$value);
        }

        self::setConfiguration($configuration, $property, $value);
    }

    private static function mapConfigurationNameToClassProperty(string $name): string
    {
        return \lcfirst(\substr($name, \strlen(self::SITE_CONFIGURATION_PREFIX)));
    }

    private static function processOptions(self $configuration, string $commaDelimitedOptions): void
    {
        $options = GeneralUtility::trimExplode(',', $commaDelimitedOptions, true);
        foreach ($options as $option) {
            self::setConfiguration($configuration, $option, true);
        }
    }

    /**
     * @param bool|int|string $value
     */
    private static function setConfiguration(Configuration $configuration, string $property, $value): void
    {
        try {
            // @phpstan-ignore-next-line
            $type = (new \ReflectionProperty(self::class, $property))->getType()->getName();
            if ($type === 'string') {
                $configuration->$property = (string)$value;
            } elseif ($type === 'int') {
                $configuration->$property = (int)$value;
            } elseif ($type === 'bool') {
                $configuration->$property = (bool)$value;
            }
        } catch (\ReflectionException $e) {
        }
    }

    private static function normaliseUrl(string $url): string
    {
        return \rtrim($url, '/') . '/';
    }
}
