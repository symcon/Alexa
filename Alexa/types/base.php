<?php

declare(strict_types=1);

abstract class DeviceType
{
    protected $instanceID = 0;
    protected $implementedCapabilities = [];

    protected $displayedCategories = [];

    protected $displayStatusPrefix = false;
    protected $skipMissingStatus = false;
    protected $columnWidth = '';

    public function __construct(int $instanceID)
    {
        $this->instanceID = $instanceID;
    }

    public function getColumns()
    {
        $columns = [];
        foreach ($this->implementedCapabilities as $capability) {
            $newColumns = $this->generateCapabilityObject($capability)->getColumns();
            if ($this->columnWidth !== '') {
                foreach ($newColumns as &$newColumn) {
                    $newColumn['width'] = $this->columnWidth;
                }
            }
            $columns = array_merge($columns, $newColumns);
        }
        return $columns;
    }

    public function getStatus($configuration)
    {
        if ($configuration['Name'] == '') {
            return 'No name';
        }

        $okFound = false;

        foreach ($this->implementedCapabilities as $capability) {
            $capabilityObject = $this->generateCapabilityObject($capability);
            $status = $capabilityObject->getStatus($configuration);
            if (($status != 'OK') && (($status != 'Missing') || !$this->skipMissingStatus)) {
                if ($this->displayStatusPrefix) {
                    return $capabilityObject->getStatusPrefix() . $status;
                } else {
                    return $status;
                }
            } elseif ($status == 'OK') {
                $okFound = true;
            }
        }

        if ($okFound) {
            return 'OK';
        } else {
            return 'Missing';
        }
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

    public function getObjectIDs($configuration)
    {
        $result = [];
        foreach ($this->implementedCapabilities as $capability) {
            $result = array_unique(array_merge($result, $this->generateCapabilityObject($capability)->getObjectIDs($configuration)));
        }

        return $result;
    }

    public function getDetectedDevices()
    {
        $result = [];
        foreach (IPS_GetInstanceList() as $instanceID) {
            $instanceResult = [];
            foreach ($this->implementedCapabilities as $capability) {
                $detectedVariables = $this->generateCapabilityObject($capability)->getDetectedVariables($instanceID);
                if ($detectedVariables === false) {
                    $instanceResult = false;
                    break;
                }
                foreach ($detectedVariables as $name => $value) {
                    $instanceResult[$name] = $value;
                }
            }

            if ($instanceResult !== false) {
                $result[$instanceID] = $instanceResult;
            }
        }
        return $result;
    }

    public function isExpertDevice()
    {
        return false;
    }

    abstract public function getPosition();
    abstract public function getCaption();
    abstract public function getTranslations();

    private function generateCapabilityObject(string $capabilityName)
    {
        $capabilityClass = 'Capability' . $capabilityName;
        $capabilityObject = new $capabilityClass($this->instanceID);
        return $capabilityObject;
    }
}
