<?php

declare(strict_types=1);

class CapabilityBrightnessOnlyController
{
    const capabilityPrefix = 'BrightnessOnlyController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperDimDevice;

    public static function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.BrightnessController',
                'name'                      => 'brightness',
                'value'                     => $value,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return self::computePropertiesForValue(self::getDimValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'Brightness Variable',
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
            return self::getDimCompatibility($configuration[self::capabilityPrefix . 'ID']);
        }
    }

    public static function getStatusPrefix()
    {
        return 'Brightness: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setDimValue = function ($configuration, $value, $emulateStatus) {
            if (self::dimDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = self::computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != self::getDimValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'AdjustBrightness':
                return $setDimValue($configuration, self::getDimValue($configuration[self::capabilityPrefix . 'ID']) + $payload['brightnessDelta'], $emulateStatus);

            case 'SetBrightness':
                return $setDimValue($configuration, $payload['brightness'], $emulateStatus);

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'AdjustBrightness',
            'SetBrightness'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.BrightnessController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        if ($configuration[self::capabilityPrefix . 'ID'] == 0) {
            return null;
        } else {
            return [
                'brightness'
            ];
        }
    }
}
