<?php

declare(strict_types=1);

class CapabilityThermostatController extends Capability
{
    use HelperFloatDevice;
    const capabilityPrefix = 'ThermostatController';

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return $this->computePropertiesForValue($this->getFloatValue($configuration[self::capabilityPrefix . 'ID']));
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
        return $this->getGetFloatCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Thermostat: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setTemperature = function ($configuration, $value, $emulateStatus)
        {
            if ($this->setFloatValue($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != $this->getFloatValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'SetTargetTemperature':
                {
                    $value = 0;
                    switch ($payload['targetSetpoint']['scale']) {
                        case 'CELSIUS':
                            $value = $payload['targetSetpoint']['value'];
                            break;

                        case 'FAHRENHEIT':
                            $value = ($payload['targetSetpoint']['value'] - 32) * 5 / 9;
                            break;

                        case 'KELVIN':
                            $value = ($payload['targetSetpoint']['value'] - 273.15);
                            break;

                    }
                    return $setTemperature($configuration, $value, $emulateStatus);
                }

            case 'AdjustTargetTemperature':
                {
                    $delta = 0;
                    switch ($payload['targetSetpointDelta']['scale']) {
                        case 'CELSIUS':
                        case 'KELVIN':
                            $delta = $payload['targetSetpointDelta']['value'];
                            break;

                        case 'FAHRENHEIT':
                            $delta = $payload['targetSetpointDelta']['value'] * 5 / 9;
                            break;

                    }
                    return $setTemperature($configuration, $this->getFloatValue($configuration[self::capabilityPrefix . 'ID']) + $delta, $emulateStatus);
                }

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
            'SetTargetTemperature',
            'AdjustTargetTemperature'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.ThermostatController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        return [
            'targetSetpoint',
            'thermostatMode'
        ];
    }

    public function getCapabilityInformation($configuration)
    {
        $info = parent::getCapabilityInformation($configuration);
        $info[0]['configuration'] = [
            'supportedModes'     => ['HEAT', 'COOL', 'AUTO'],
            'supportsScheduling' => false
        ];
        return $info;
    }

    private function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.ThermostatController',
                'name'                      => 'targetSetpoint',
                'value'                     => [
                    'value' => floatval($value),
                    'scale' => 'CELSIUS'
                ],
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ],
            [
                'namespace'                 => 'Alexa.ThermostatController',
                'name'                      => 'thermostatMode',
                'value'                     => 'HEAT',
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
