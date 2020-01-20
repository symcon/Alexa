<?php

declare(strict_types=1);

class CapabilityChannelController
{
    const capabilityPrefix = 'ChannelController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperAssociationDevice;

    private static function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.ChannelController',
                'name'                      => 'channel',
                'value'                     => $value,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return self::computePropertiesForValue([
                'number'   => strval(self::getAssociationNumber($configuration[self::capabilityPrefix . 'ID'])),
                'callSign' => self::getAssociationString($configuration[self::capabilityPrefix . 'ID'])
            ]);
        } else {
            return [];
        }
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'Channel Variable',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '150px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        return self::getAssociationCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function getStatusPrefix()
    {
        return 'Channel: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $switchChannel = function ($configuration, $value, $emulateStatus)
        {
            $variableID = $configuration[self::capabilityPrefix . 'ID'];
            if (isset($value['channel']['number'])) {
                $valueNumber = intval($value['channel']['number']);
                if (!self::isValidAssociationNumber($variableID, $valueNumber)) {
                    return [
                        'payload'        => [
                            'type' 		 => 'INVALID_VALUE',
                            'message'	=> 'Channel not found in profile associations'
                        ],
                        'eventName'      => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                } elseif (self::setAssociationNumber($variableID, $valueNumber)) {
                    $properties = [];
                    if ($emulateStatus) {
                        $properties = self::computePropertiesForValue($value['channel']);
                    } else {
                        $i = 0;
                        while (($valueNumber != self::getAssociationNumber($variableID)) && $i < 10) {
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
            } else {
                $valueString = '';
                if (isset($value['channel']['callSign'])) {
                    $valueString = $value['channel']['callSign'];
                } elseif (isset($value['channel']['affiliateCallSign'])) {
                    $valueString = $value['channel']['affiliateCallSign'];
                } elseif (isset($value['channelMetadata']['name'])) {
                    $valueString = $value['channelMetadata']['name'];
                } elseif (isset($value['channel']['uri'])) {
                    $valueString = $value['channel']['uri'];
                }
                if (!self::isValidAssociationString($variableID, $valueString)) {
                    return [
                        'payload'        => [
                            'type' 		 => 'INVALID_VALUE',
                            'message'	=> 'Channel not found in profile associations'
                        ],
                        'eventName'      => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                } elseif (self::setAssociationString($variableID, $valueString)) {
                    $properties = [];
                    if ($emulateStatus) {
                        $properties = self::computePropertiesForValue($value['channel']);
                    } else {
                        $i = 0;
                        while (($valueString != self::getAssociationString($variableID)) && $i < 10) {
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
            }
        };

        $skipChannels = function ($configuration, $value, $emulateStatus)
        {
            $currentValue = self::getAssociationNumber($configuration[self::capabilityPrefix . 'ID']);
            if (self::incrementAssociation($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = self::computePropertiesForValue(['number' => strval($currentValue + $value)]);
                } else {
                    $i = 0;
                    // We don't check for the correct value as that computation would be quite complex, as it needs to consider the profile
                    // We merely wait for the current value to change (Which could not happen if increment = Number of associations, but then we just wait a second)
                    while (($currentValue == self::getAssociationNumber($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'ChangeChannel':
                return $switchChannel($configuration, $payload, $emulateStatus);

            case 'SkipChannels':
                return $skipChannels($configuration, $payload['channelCount'], $emulateStatus);

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
            'ChangeChannel',
            'SkipChannels'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.ChannelController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        return [
            'channel'
        ];
    }
}
