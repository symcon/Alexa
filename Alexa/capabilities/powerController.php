<?php

declare(strict_types=1);

class CapabilityPowerController
{
    const capabilityPrefix = 'PowerController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperSwitchDevice;

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.PowerController',
                    'name'                      => 'powerState',
                    'value'                     => (self::getSwitchValue($configuration[self::capabilityPrefix . 'ID']) ? 'ON' : 'OFF'),
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
                'label' => 'Switch Variable',
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
        return self::getSwitchCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function getStatusPrefix()
    {
        return 'Power: ';
    }

    public static function doDirective($configuration, $directive, $data)
    {
        $switchValue = function ($configuration, $value) {
            if (self::switchDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $i = 0;
                while (($value != self::getSwitchValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'TurnOn':
            case 'TurnOff':
                $newValue = ($directive == 'TurnOn');
                return $switchValue($configuration, $newValue);

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'TurnOn',
            'TurnOff'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.PowerController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        return [
            'powerState'
        ];
    }
}
