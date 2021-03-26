<?php

declare(strict_types=1);

class CapabilityColorController
{
    use HelperCapabilityDiscovery;
    use HelperColorDevice;
    const capabilityPrefix = 'ColorController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return self::computePropertiesForValue(self::getColorValue($configuration[self::capabilityPrefix . 'ID']));
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

    public static function getStatusPrefix()
    {
        return 'Color: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setColor = function ($configuration, $value, $emulateStatus)
        {
            if (self::colorDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = self::computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != self::getColorValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'SetColor':
                return $setColor($configuration, self::hsbToRGB($payload['color']), $emulateStatus);

            case 'AdjustBrightness':
                {
                    $currentBrightness = self::getColorBrightness($configuration[self::capabilityPrefix . 'ID']);
                    return $setColor($configuration, self::computeColorBrightness($configuration[self::capabilityPrefix . 'ID'], $currentBrightness + $payload['brightnessDelta']), $emulateStatus);
                }
                break;

            case 'SetBrightness':
                return $setColor($configuration, self::computeColorBrightness($configuration[self::capabilityPrefix . 'ID'], $payload['brightness']), $emulateStatus);

            case 'TurnOn':
            case 'TurnOff':
                $value = ($directive == 'TurnOn' ? 0xFFFFFF : 0);
                return $setColor($configuration, $value, $emulateStatus);

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

    public static function supportedProperties($realCapability, $configuration)
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

    private static function computePropertiesForValue($value)
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
                'namespace'                 => 'Alexa.BrightnessController',
                'name'                      => 'brightness',
                'value'                     => self::getColorBrightnessByValue($value),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ],
            [
                'namespace'                 => 'Alexa.ColorController',
                'name'                      => 'color',
                'value'                     => self::rgbToHSB($value),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
