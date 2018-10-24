<?php

declare(strict_types=1);

class CapabilityThermostatController
{
    const capabilityPrefix = 'ThermostatController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperFloatDevice;

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.ThermostatController',
                    'name'                      => 'targetSetpoint',
                    'value'                     => [
                        'value' => floatval(self::getFloatValue($configuration[self::capabilityPrefix . 'ID'])),
                        'scale' => 'CELSIUS'
                    ],
                    'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                    'uncertaintyInMilliseconds' => 0
                ]
            ];
        } else {
            return [];
        }
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'Variable',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        return self::getGetFloatCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function getStatusPrefix()
    {
        return 'Thermostat: ';
    }

    public static function doDirective($configuration, $directive, $data)
    {
        $setTemperature = function ($configuration, $value) {
            if (self::setFloatValue($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $i = 0;
                while (($value != self::getFloatValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
                    $i++;
                    usleep(100000);
                }
                return [
                    'properties'     => self::computeProperties($configuration),
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
                    'properties'     => self::computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'SetTargetTemperature':
                {
                    $value = 0;
                    switch ($data['targetSetpoint']['scale']) {
                        case 'CELSIUS':
                            $value = $data['targetSetpoint']['value'];
                            break;

                        case 'FAHRENHEIT':
                            $value = ($data['targetSetpoint']['value'] - 32) * 5 / 9;
                            break;

                        case 'KELVIN':
                            $value = ($data['targetSetpoint']['value'] - 273.15);
                            break;

                    }
                    return $setTemperature($configuration, $value);
                }

            case 'AdjustTargetTemperature':
                {
                    $delta = 0;
                    switch ($data['targetSetpointDelta']['scale']) {
                        case 'CELSIUS':
                        case 'KELVIN':
                            $delta = $data['targetSetpointDelta']['value'];
                            break;

                        case 'FAHRENHEIT':
                            $delta = $data['targetSetpointDelta']['value'] * 5 / 9;
                            break;

                    }
                    return $setTemperature($configuration, self::getFloatValue($configuration[self::capabilityPrefix . 'ID']) + $data['targetSetpointDelta']['value']);
                }

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'SetTargetTemperature',
            'AdjustTargetTemperature'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.ThermostatController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        return [
            'targetSetpoint'
        ];
    }
}
