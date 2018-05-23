<?php

declare(strict_types=1);

class CapabilityColorController
{
    const capabilityPrefix = 'ColorController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperColorDevice;

    private static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.PowerController',
                    'name'                      => 'powerState',
                    'value'                     => (self::getColorValue($configuration[self::capabilityPrefix . 'ID']) > 0 ? 'ON' : 'OFF'),
                    'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                    'uncertaintyInMilliseconds' => 0
                ],
                [
                    'namespace'                 => 'Alexa.BrightnessController',
                    'name'                      => 'brightness',
                    'value'                     => self::getColorBrightness($configuration[self::capabilityPrefix . 'ID']),
                    'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                    'uncertaintyInMilliseconds' => 0
                ],
                [
                    'namespace'                 => 'Alexa.ColorController',
                    'name'                      => 'color',
                    'value'                     => self::rgbToHSB(self::getColorValue($configuration[self::capabilityPrefix . 'ID'])),
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
        return self::getColorCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function doDirective($configuration, $directive, $payload)
    {
        switch ($directive) {
            case 'ReportState':
                return [
                    'properties'     => self::computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'SetColor':
                if (self::colorDevice($configuration[self::capabilityPrefix . 'ID'], self::hsbToRGB($payload['color']))) {
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
                break;

            case 'AdjustBrightness':
                {
                    $currentBrightness = self::getColorBrightness($configuration[self::capabilityPrefix . 'ID']);
                    if (self::setColorBrightness($configuration[self::capabilityPrefix . 'ID'], $currentBrightness + $payload['brightnessDelta'])) {
                        return [
                            'properties'     => self::computeProperties($configuration),
                            'payload'        => new stdClass(),
                            'eventName'      => 'Response',
                            'eventNamespace' => 'Alexa'
                        ];
                    } else {
                        return [
                            'payload' => [
                                'type' => 'NO_SUCH_ENDPOINT'
                            ],
                            'eventName'      => 'ErrorResponse',
                            'eventNamespace' => 'Alexa'
                        ];
                    }
                }
                break;

            case 'SetBrightness':
                {
                    if (self::setColorBrightness($configuration[self::capabilityPrefix . 'ID'], $payload['brightness'])) {
                        return [
                            'properties'     => self::computeProperties($configuration),
                            'payload'        => new stdClass(),
                            'eventName'      => 'Response',
                            'eventNamespace' => 'Alexa'
                        ];
                    } else {
                        return [
                            'payload' => [
                                'type' => 'NO_SUCH_ENDPOINT'
                            ],
                            'eventName'      => 'ErrorResponse',
                            'eventNamespace' => 'Alexa'
                        ];
                    }
                }
                break;

            case 'TurnOn':
            case 'TurnOff':
                $value = ($directive == 'TurnOn' ? 0xFFFFFF : 0);
                if (self::colorDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
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
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'SetColor',
            'AdjustBrightness',
            'SetBrightness',
            'TurnOn',
            'TurnOff'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.ColorController',
            'Alexa.BrightnessController',
            'Alexa.PowerController'
        ];
    }

    public static function supportedProperties($realCapability)
    {
        switch ($realCapability) {
            case 'Alexa.ColorController':
                return [
                    'color'
                ];

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
