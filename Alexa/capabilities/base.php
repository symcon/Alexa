<?php

declare(strict_types=1);

abstract class Capability extends CommonCapability
{
    protected const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    public function getCapabilityInformation($configuration)
    {
        $capabilitiesInfo = [];
        $capabilities = $this->supportedCapabilities();
        foreach ($capabilities as $realCapability) {
            $supportedProperties = [];
            $supportedPropertiesNames = $this->supportedProperties($realCapability, $configuration);
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

    abstract public function computeProperties($configuration);
    abstract public function doDirective($configuration, $directive, $payload, $emulateStatus);
    abstract public function supportedDirectives();
    abstract public function supportedCapabilities();
    abstract public function supportedProperties($realCapability, $configuration);
}
