<?php

declare(strict_types=1);

class CapabilityLockController
{
    const capabilityPrefix = 'LockController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperSwitchDevice;

    private static function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.LockController',
                'name'                      => 'lockState',
                'value'                     => ($value ? 'LOCKED' : 'UNLOCKED'),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return self::computePropertiesForValue(self::getSwitchValue($configuration[self::capabilityPrefix . 'ID']));
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
        return self::getSwitchCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function getStatusPrefix()
    {
        return 'Lock: ';
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

            case 'Lock':
            case 'Unlock':
                $newValue = ($directive == 'Lock');
                if (self::switchDevice($configuration[self::capabilityPrefix . 'ID'], $newValue)) {
                    $properties = [];
                    if ($emulateStatus) {
                        $properties = self::computePropertiesForValue($newValue);
                    } else {
                        $i = 0;
                        while (($newValue != self::getSwitchValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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
                        'payload' => [
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
            'Lock',
            'Unlock'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.LockController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        return [
            'lockState'
        ];
    }
}
