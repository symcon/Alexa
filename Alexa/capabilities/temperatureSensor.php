<?php

declare(strict_types=1);

class CapabilityTemperatureSensor
{
    use HelperCapabilityDiscovery;
    use HelperGetFloatDevice;
    const capabilityPrefix = 'TemperatureSensor';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.TemperatureSensor',
                    'name'                      => 'temperature',
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
                'label' => 'SensorVariable',
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
        return 'Temperature Sensor: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
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

    public static function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
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

    public static function supportedProperties($realCapability, $configuration)
    {
        return [
            'temperature'
        ];
    }
}
