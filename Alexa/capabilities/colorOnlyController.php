<?php

declare(strict_types=1);

class CapabilityColorOnlyController
{
    use HelperCapabilityDiscovery;
    use HelperColorDevice;
    const capabilityPrefix = 'ColorOnlyController';
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
                'label' => 'Color Variable',
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
            return self::getColorCompatibility($configuration[self::capabilityPrefix . 'ID']);
        }
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
            'SetColor'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.ColorController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        if ($configuration[self::capabilityPrefix . 'ID'] == 0) {
            return null;
        } else {
            return [
                'color'
            ];
        }
    }

    private static function computePropertiesForValue($value)
    {
        return [
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
