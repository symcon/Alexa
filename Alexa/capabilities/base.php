<?php

declare(strict_types=1);

trait HelperCapabilityDiscovery
{
    public static function getCapabilityInformation($configuration)
    {
        $capabilitiesInfo = [];
        $capabilities = self::supportedCapabilities();
        foreach ($capabilities as $realCapability) {
            $supportedProperties = [];
            $supportedPropertiesNames = self::supportedProperties($realCapability, $configuration);
            if ($supportedPropertiesNames !== null) {
                foreach ($supportedPropertiesNames as $property) {
                    $supportedProperties[] = [
                        'name' => $property
                    ];
                }
                $capabilitiesInfo[] = [
                    'type'       => 'AlexaInterface',
                    'interface'  => $realCapability,
                    'version'    => '3',
                    'properties' => [
                        'supported'           => $supportedProperties,
                        'proactivelyReported' => false,
                        'retrievable'         => true
                    ]

                ];
            }
        }
        return $capabilitiesInfo;
    }
}
