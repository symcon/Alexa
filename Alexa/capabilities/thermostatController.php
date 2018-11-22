<?php

declare(strict_types=1);

class CapabilityThermostatController
{
    const capabilityPrefix = 'ThermostatController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperFloatDevice;

    private static function computePropertiesForValue($value)
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
            ]
        ];
    }

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return self::computePropertiesForValue(self::getFloatValue($configuration[self::capabilityPrefix . 'ID']));
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

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setTemperature = function ($configuration, $value, $emulateStatus) {
            if (self::setFloatValue($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = self::computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != self::getFloatValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
                        $i++;
                        usleep(100000);
                    }
                    $properties = self::computeProperties($configuration);
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
                    'properties'     => self::computeProperties($configuration),
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
                    return $setTemperature($configuration, self::getFloatValue($configuration[self::capabilityPrefix . 'ID']) + payload['targetSetpointDelta']['value'], $emulateStatus);
                }

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
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
