<?php

declare(strict_types=1);

class CapabilityLockController extends Capability
{
    use HelperSwitchDevice;
    const capabilityPrefix = 'LockController';

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return $this->computePropertiesForValue($this->getSwitchValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Variable',
                'name'    => self::capabilityPrefix . 'ID',
                'width'   => '250px',
                'add'     => 0,
                'edit'    => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        return $this->getSwitchCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Lock: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        switch ($directive) {
            case 'ReportState':
                return [
                    'properties'     => $this->computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'Lock':
            case 'Unlock':
                $newValue = ($directive == 'Lock');
                if ($this->switchDevice($configuration[self::capabilityPrefix . 'ID'], $newValue)) {
                    $properties = [];
                    if ($emulateStatus) {
                        $properties = $this->computePropertiesForValue($newValue);
                    } else {
                        $i = 0;
                        while (($newValue != $this->getSwitchValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $properties = $this->computeProperties($configuration);
                    }
                    return [
                        'properties'     => $properties,
                        'payload'        => new stdClass(),
                        'eventName'      => 'Response',
                        'eventNamespace' => 'Alexa'
                    ];
                } else {
                    return [
                        'payload' => [
                            'type'    => 'HARDWARE_MALFUNCTION',
                            'message' => ob_get_contents()
                        ],
                        'eventName'      => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                }
                break;
            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
    }

    public function supportedDirectives()
    {
        return [
            'ReportState',
            'Lock',
            'Unlock'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.LockController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        return [
            'lockState'
        ];
    }

    protected function getSupportedProfiles()
    {
        return [
            self::capabilityPrefix . 'ID' => ['~Lock', '~Lock.Reversed', '~Door', '~Door.Reversed']
        ];
    }

    private function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.LockController',
                'name'                      => 'lockState',
                'value'                     => ($value ? 'LOCKED' : 'UNLOCKED'),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
