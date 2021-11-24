<?php

declare(strict_types=1);

abstract class DeviceType extends CommonType
{
    protected $displayedCategories = [];

    public function __construct(int $instanceID)
    {
        parent::__construct($instanceID, 'Capability');
    }

    public function doDiscovery($configuration)
    {
        $discovery = [
            'endpointId'        => strval($configuration['ID']),
            'friendlyName'      => $configuration['Name'],
            'description'       => $this->getCaption() . ' by IP-Symcon',
            'manufacturerName'  => 'Symcon GmbH',
            'displayCategories' => $this->displayedCategories,
            'cookie'            => new stdClass(),
            'capabilities'      => [
            ]
        ];

        foreach ($this->implementedCapabilities as $capability) {
            $capabilityObject = $this->generateCapabilityObject($capability);
            if ($capabilityObject->getStatus($configuration) == 'OK') {
                $capabilitiesInformation = $capabilityObject->getCapabilityInformation($configuration);
                foreach ($capabilitiesInformation as $capabilityInformation) {
                    $discovery['capabilities'][] = $capabilityInformation;
                }
            }
        }

        return $discovery;
    }

    public function doDirective($configuration, $directiveName, $payload, $emulateStatus)
    {
        // Report State needs to check properties of all capabilities
        if ($directiveName == 'ReportState') {
            $properties = [];

            foreach ($this->implementedCapabilities as $capability) {
                $properties = array_merge($properties, $this->generateCapabilityObject($capability)->computeProperties($configuration));
            }

            return [
                'properties'     => $properties,
                'payload'        => new stdClass(),
                'eventName'      => 'StateReport',
                'eventNamespace' => 'Alexa'
            ];
        }

        foreach ($this->implementedCapabilities as $capability) {
            $capabilityObject = $this->generateCapabilityObject($capability);
            if (in_array($directiveName, $capabilityObject->supportedDirectives())) {
                return $capabilityObject->doDirective($configuration, $directiveName, $payload, $emulateStatus);
            }
        }

        return [
            'payload'        => [
                'type' => 'NO_SUCH_ENDPOINT'
            ],
            'eventName'      => 'ErrorResponse',
            'eventNamespace' => 'Alexa'
        ];
    }
}
