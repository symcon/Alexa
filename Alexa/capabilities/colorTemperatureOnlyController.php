<?php

declare(strict_types=1);

class CapabilityColorTemperatureOnlyController
{
    const capabilityPrefix = 'ColorTemperatureOnlyController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    const COLOR_TEMPERATURE_STEPSIZE = 3000;
    const COLOR_TEMPERATURE_MAX = 12000;
    const COLOR_TEMPERATURE_MIN = 1000;

    use HelperCapabilityDiscovery;
    use HelperNumberDevice;

    private static function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.ColorTemperatureController',
                'name'                      => 'colorTemperatureInKelvin',
                'value'                     => $value,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return self::computePropertiesForValue(self::getNumberValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'Color Temperature Variable',
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
        if ($configuration[self::capabilityPrefix . 'ID'] == 0) {
            return 'OK';
        } else {
            return self::getNumberCompatibility($configuration[self::capabilityPrefix . 'ID']);
        }
    }

    public static function getStatusPrefix()
    {
        return 'Color Temperature: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setColorTemperature = function ($configuration, $value, $emulateStatus)
        {
            if (self::setNumberValue($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = self::computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != self::getNumberValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'SetColorTemperature':
                return $setColorTemperature($configuration, $payload['colorTemperatureInKelvin'], $emulateStatus);

            case 'IncreaseColorTemperature': {
                $newValue = self::getNumberValue($configuration[self::capabilityPrefix . 'ID']) + self::COLOR_TEMPERATURE_STEPSIZE;
                $newValue = min($newValue, self::COLOR_TEMPERATURE_MAX);
                return $setColorTemperature($configuration, $newValue, $emulateStatus);
            }

            case 'DecreaseColorTemperature': {
                $newValue = self::getNumberValue($configuration[self::capabilityPrefix . 'ID']) - self::COLOR_TEMPERATURE_STEPSIZE;
                $newValue = max($newValue, self::COLOR_TEMPERATURE_MIN);
                return $setColorTemperature($configuration, $newValue, $emulateStatus);
            }

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function getObjectIDs($configuration)
    {
        // Due to legacy versions, it is possible, that the ID is not set
        if (!isset($configuration[self::capabilityPrefix . 'ID'])) {
            return [];
        }
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'SetColorTemperature',
            'IncreaseColorTemperature',
            'DecreaseColorTemperature'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.ColorTemperatureController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        if ($configuration[self::capabilityPrefix . 'ID'] == 0) {
            return null;
        } else {
            return [
                'colorTemperatureInKelvin'
            ];
        }
    }
}
