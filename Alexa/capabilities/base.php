<?php

declare(strict_types=1);

abstract class Capability
{
    protected const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';
    protected $instanceID = 0;

    public function __construct(int $instanceID)
    {
        $this->instanceID = $instanceID;
    }

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

    public function getDetectedVariables($instanceID)
    {
        // Does the capability support automatic detection?
        $supportedProfiles = $this->getSupportedProfiles();
        if (!$supportedProfiles) {
            return false;
        }

        $result = [];
        foreach ($supportedProfiles as $name => $supportedProfile) {
            $result[$name] = false;
        }

        foreach (IPS_GetChildrenIDs($instanceID) as $variableID) {
            if (!IPS_VariableExists($variableID) || !HasAction($variableID)) {
                continue;
            }

            $targetVariable = IPS_GetVariable($variableID);
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];
            }

            foreach ($supportedProfiles as $name => $supportedProfile) {
                if ($profileName === $supportedProfile) {
                    if ($result[$name] === false) {
                        $result[$name] = $variableID;
                    }
                    // If there is more than one supported variable, we do not detect the instance as supported due to ambiguous use
                    else {
                        return false;
                    }
                    break;
                }
            }
        }

        // Check if all properties were filled
        foreach ($result as $name => $value) {
            if ($value === false) {
                return false;
            }
        }

        return $result;
    }

    abstract public function computeProperties($configuration);
    abstract public function getColumns();
    abstract public function getStatus($configuration);
    abstract public function getStatusPrefix();
    abstract public function doDirective($configuration, $directive, $payload, $emulateStatus);
    abstract public function getObjectIDs($configuration);
    abstract public function supportedDirectives();
    abstract public function supportedCapabilities();
    abstract public function supportedProperties($realCapability, $configuration);

    protected function getSupportedProfiles()
    {
        return false;
    }
}
