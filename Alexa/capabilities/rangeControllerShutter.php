<?php

declare(strict_types=1);

class CapabilityRangeControllerShutter
{
    const capabilityPrefix = 'RangeControllerShutter';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery {
        getCapabilityInformation as getCapabilityInformationBase;
    }
    use HelperDimDevice;
    use HelperShutterDevice;

    private static function hasShutterProfile($configuration)
    {
        return self::getShutterCompatibility($configuration[self::capabilityPrefix . 'ID']) == 'OK';
    }

    private static function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.RangeController',
                'instance'                  => 'Shutter.Position',
                'name'                      => 'rangeValue',
                'value'                     => strval($value),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }

    public static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            if (self::hasShutterProfile($configuration)) {
                return self::computePropertiesForValue(self::getShutterOpen($configuration[self::capabilityPrefix . 'ID']) ? 100 : 0);
            }
            else {
                return self::computePropertiesForValue(100 - self::getDimValue($configuration[self::capabilityPrefix . 'ID']));
            }
        } else {
            return [];
        }
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'Shutter Variable',
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
        $dimCompatibility = self::getDimCompatibility($configuration[self::capabilityPrefix . 'ID']);
        $shutterCompatibility = self::getShutterCompatibility($configuration[self::capabilityPrefix . 'ID']);

        if ($dimCompatibility != 'OK') {
            return $shutterCompatibility;
        } else {
            return 'OK';
        }
    }

    public static function getStatusPrefix()
    {
        return 'Shutter: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setRangeValue = function ($configuration, $value, $emulateStatus)
        {
            if (self::hasShutterProfile($configuration)) {
                $open = ($value < 50);
                if (self::setShutterOpen($configuration[self::capabilityPrefix . 'ID'], $open)) {
                    $properties = [];
                    if ($emulateStatus) {
                        $properties = self::computePropertiesForValue($value);
                    } else {
                        $i = 0;
                        while (($open != self::getShutterOpen($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'AdjustRangeValue':
                $delta = -$payload['rangeValueDelta'];
                // 25% steps for percentage profiles if the user gave no specific delta
                if (!self::hasShutterProfile($configuration) && $payload['rangeValueDeltaDefault']) {
                    $delta *= 25 / abs($delta);
                }
                $value = self::getDimValue($configuration[self::capabilityPrefix . 'ID']) + $delta;
                if ($value > 100) {
                    $value = 100;
                }
                if ($value < 0) {
                    $value = 0;
                }
                return $setRangeValue($configuration, $value, $emulateStatus);

            case 'SetRangeValue':
                return $setRangeValue($configuration, 100 - $payload['rangeValue'], $emulateStatus);

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
            'SetRangeValue',
            'AdjustRangeValue'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.RangeController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        return ['rangeValue'];
    }

    public static function getCapabilityInformation($configuration)
    {
        $info = self::getCapabilityInformationBase($configuration);
        $info[0]['instance'] = 'Shutter.Position';
        $info[0]['properties']['noControllable'] = false;
        $info[0]['capabilityResources'] = [
            'friendlyNames' => [
                [
                    '@type' => 'asset',
                    'value' => [
                        'assetId' => 'Alexa.Setting.Opening'
                    ]
                ]
            ]
        ];
        $shutterProfile = self::hasShutterProfile($configuration);
        $info[0]['configuration'] = [
            'supportedRange' => [
                'minimumValue' => 0,
                'maximumValue' => 100,
                'precision'    => $shutterProfile ? 100 : 1
            ],
            'unitOfMeasure' => 'Alexa.Unit.Percent'
        ];
        $actionMappings = [];

        if ($shutterProfile) {
            $actionMappings = [
                [
                    '@type'     => 'ActionsToDirective',
                    'actions'   => ['Alexa.Actions.Close', 'Alexa.Actions.Lower'],
                    'directive' => [
                        'name'    => 'SetRangeValue',
                        'payload' => [
                            'rangeValue' => 0
                        ]
                    ]
                ],
                [
                    '@type'     => 'ActionsToDirective',
                    'actions'   => ['Alexa.Actions.Open', 'Alexa.Actions.Raise'],
                    'directive' => [
                        'name'    => 'SetRangeValue',
                        'payload' => [
                            'rangeValue' => 100
                        ]
                    ]
                ]
            ];
        } else {
            $actionMappings = [
                [
                    '@type'     => 'ActionsToDirective',
                    'actions'   => ['Alexa.Actions.Close'],
                    'directive' => [
                        'name'    => 'SetRangeValue',
                        'payload' => [
                            'rangeValue' => 0
                        ]
                    ]
                ],
                [
                    '@type'     => 'ActionsToDirective',
                    'actions'   => ['Alexa.Actions.Open'],
                    'directive' => [
                        'name'    => 'SetRangeValue',
                        'payload' => [
                            'rangeValue' => 100
                        ]
                    ]
                ],
                [
                    '@type'     => 'ActionsToDirective',
                    'actions'   => ['Alexa.Actions.Raise'],
                    'directive' => [
                        'name'    => 'AdjustRangeValue',
                        'payload' => [
                            'rangeValueDelta'        => 25,
                            'rangeValueDeltaDefault' => false
                        ]
                    ]
                ],
                [
                    '@type'     => 'ActionsToDirective',
                    'actions'   => ['Alexa.Actions.Lower'],
                    'directive' => [
                        'name'    => 'AdjustRangeValue',
                        'payload' => [
                            'rangeValueDelta'        => -25,
                            'rangeValueDeltaDefault' => false
                        ]
                    ]
                ]
            ];
        }

        $info[0]['semantics'] = [
            'actionMappings' => $actionMappings,
            'stateMappings'  => [
                [
                    '@type'  => 'StatesToValue',
                    'states' => ['Alexa.States.Closed'],
                    'value'  => 0
                ],
                [
                    '@type'  => 'StatesToRange',
                    'states' => ['Alexa.States.Open'],
                    'range'  => [
                        'minimumValue' => 1,
                        'maximumValue' => 100
                    ]
                ]
            ]
        ];
        return $info;
    }
}
