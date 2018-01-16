<?php

declare(strict_types=1);

class CapabilityBrightnessController
{
    const capabilityPrefix = 'BrightnessController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperDimDevice;

    private static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.PowerController',
                    'name'                      => 'powerState',
                    'value'                     => (self::getDimValue($configuration[self::capabilityPrefix . 'ID']) > 0 ? 'ON' : 'OFF'),
                    'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                    'uncertaintyInMilliseconds' => 0
                ],
                [
                    'namespace'                 => 'Alexa.BrightnessController',
                    'name'                      => 'brightness',
                    'value'                     => self::getDimValue($configuration[self::capabilityPrefix . 'ID']),
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
                'label' => 'VariableID',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        return self::getDimCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function doDirective($configuration, $directive, $payload)
    {
        switch ($directive) {
            case 'ReportState':
                return [
                    'properties' => self::computeProperties($configuration),
                    'payload'    => new stdClass(),
                    'eventName'  => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'AdjustBrightness':
                if (self::dimDevice($configuration[self::capabilityPrefix . 'ID'], self::getDimValue($configuration[self::capabilityPrefix . 'ID']) + $payload['brightnessDelta'])) {
                    return [
                        'properties' => self::computeProperties($configuration),
                        'payload'    => new stdClass(),
                        'eventName'  => 'Response',
                        'eventNamespace' => 'Alexa'
                    ];
                } else {
                    return [
                        'payload' => [
                            'type' => 'NO_SUCH_ENDPOINT'
                        ],
                        'eventName' => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                }
                break;

            case 'SetBrightness':
                if (self::dimDevice($configuration[self::capabilityPrefix . 'ID'], $payload['brightness'])) {
                    return [
                        'properties' => self::computeProperties($configuration),
                        'payload'    => new stdClass(),
                        'eventName'  => 'Response',
                        'eventNamespace' => 'Alexa'
                    ];
                } else {
                    return [
                        'payload' => [
                            'type' => 'NO_SUCH_ENDPOINT'
                        ],
                        'eventName' => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                }
                break;

            case 'TurnOn':
            case 'TurnOff':
                $value = ($directive == 'TurnOn' ? 100 : 0);
                if (self::dimDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                    return [
                        'properties' => self::computeProperties($configuration),
                        'payload'    => new stdClass(),
                        'eventName'  => 'Response',
                        'eventNamespace' => 'Alexa'
                    ];
                } else {
                    return [
                        'payload' => [
                            'type' => 'NO_SUCH_ENDPOINT'
                        ],
                        'eventName' => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                }
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'AdjustBrightness',
            'SetBrightness',
            'TurnOn',
            'TurnOff'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.BrightnessController',
            'Alexa.PowerController'
        ];
    }

    public static function supportedProperties($realCapability)
    {
        switch ($realCapability) {
            case 'Alexa.BrightnessController':
                return [
                    'brightness'
                ];

            case 'Alexa.PowerController':
                return [
                    'powerState'
                ];
        }
    }
}
