<?php

declare(strict_types=1);

class CapabilityTemperatureSensor
{
    const capabilityPrefix = 'TemperatureSensor';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;

    private static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.TemperatureSensor',
                    'name'                      => 'temperature',
                    'value'                     => [
                        'value' => GetValue($configuration[self::capabilityPrefix . 'ID']),
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
                'label' => 'SensorVariableID',
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
        return self::getSwitchCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function doDirective($configuration, $directive, $data)
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

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.TemperatureSensor'
        ];
    }

    public static function supportedProperties($realCapability)
    {
        return [
            'temperature'
        ];
    }
}
