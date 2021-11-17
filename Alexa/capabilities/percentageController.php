<?php

declare(strict_types=1);

class CapabilityPercentageController extends Capability
{
    use HelperDimDevice;
    const capabilityPrefix = 'PercentageController';

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return $this->computePropertiesForValue($this->getDimValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Variable',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        return $this->getDimCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Percentage: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setDimValue = function ($configuration, $value, $emulateStatus)
        {
            if ($this->dimDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != $this->getDimValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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
                    'payload'        => [
                        'type' => 'NO_SUCH_ENDPOINT'
                    ],
                    'eventName'      => 'ErrorResponse',
                    'eventNamespace' => 'Alexa'
                ];
            }
        };

        switch ($directive) {
            case 'ReportState':
                return [
                    'properties'     => $this->computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'AdjustPercentage':
                return $setDimValue($configuration, $this->getDimValue($configuration[self::capabilityPrefix . 'ID']) + $payload['percentageDelta'], $emulateStatus);

            case 'SetPercentage':
                return $setDimValue($configuration, $payload['percentage'], $emulateStatus);

            case 'TurnOn':
            case 'TurnOff':
                $value = ($directive == 'TurnOn' ? 100 : 0);
                return $setDimValue($configuration, $value, $emulateStatus);

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
            'AdjustPercentage',
            'SetPercentage',
            'TurnOn',
            'TurnOff'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.PercentageController',
            'Alexa.PowerController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        switch ($realCapability) {
            case 'Alexa.PercentageController':
                return [
                    'percentage'
                ];

            case 'Alexa.PowerController':
                return [
                    'powerState'
                ];
        }
    }

    private function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.PowerController',
                'name'                      => 'powerState',
                'value'                     => ($value > 0 ? 'ON' : 'OFF'),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ],
            [
                'namespace'                 => 'Alexa.PercentageController',
                'name'                      => 'percentage',
                'value'                     => $value,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
