<?php

declare(strict_types=1);

class CapabilityRangeControllerShutter extends Capability
{
    use HelperDimDevice;
    use HelperShutterDevice;
    public const capabilityPrefix = 'RangeControllerShutter';

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            if ($this->hasShutterProfile($configuration)) {
                return $this->computePropertiesForValue($this->getShutterOpen($configuration[self::capabilityPrefix . 'ID']) ? 100 : 0);
            } else {
                return $this->computePropertiesForValue(100 - $this->getDimValue($configuration[self::capabilityPrefix . 'ID']));
            }
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Shutter Variable',
                'name'    => self::capabilityPrefix . 'ID',
                'width'   => '250px',
                'add'     => 0,
                'edit'    => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        $dimCompatibility = $this->getDimCompatibility($configuration[self::capabilityPrefix . 'ID']);
        $shutterCompatibility = $this->getShutterCompatibility($configuration[self::capabilityPrefix . 'ID']);

        if ($dimCompatibility != 'OK') {
            return $shutterCompatibility;
        } else {
            return 'OK';
        }
    }

    public function getStatusPrefix()
    {
        return 'Shutter: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setRangeValue = function ($configuration, $value, $emulateStatus)
        {
            if ($this->hasShutterProfile($configuration)) {
                $open = ($value < 50);
                if ($this->setShutterOpen($configuration[self::capabilityPrefix . 'ID'], $open)) {
                    $properties = [];
                    if ($emulateStatus) {
                        $properties = $this->computePropertiesForValue(100 - $value);
                    } else {
                        $i = 0;
                        while (($open != $this->getShutterOpen($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $properties = $this->computeProperties($configuration);
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
                            'type'    => 'HARDWARE_MALFUNCTION',
                            'message' => ob_get_contents()
                        ],
                        'eventName'      => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                }
            } else {
                if ($this->dimDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                    $properties = [];
                    if ($emulateStatus) {
                        $properties = $this->computePropertiesForValue(100 - $value);
                    } else {
                        $i = 0;
                        while (($value != $this->getDimValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
                            $i++;
                            usleep(100000);
                        }
                        $properties = $this->computeProperties($configuration);
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
                            'type'    => 'HARDWARE_MALFUNCTION',
                            'message' => ob_get_contents()
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
                    'properties'     => $this->computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'AdjustRangeValue':
                $delta = -$payload['rangeValueDelta'];
                // 25% steps for percentage profiles if the user gave no specific delta
                if (!$this->hasShutterProfile($configuration) && $payload['rangeValueDeltaDefault']) {
                    $delta *= 25 / abs($delta);
                }
                $value = 0;
                if ($this->hasShutterProfile($configuration)) {
                    $value = $this->getShutterOpen($configuration[self::capabilityPrefix . 'ID']) ? 0 : 100;
                } else {
                    $value = $this->getDimValue($configuration[self::capabilityPrefix . 'ID']);
                }
                $value += $delta;
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

    public function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
    }

    public function supportedDirectives()
    {
        return [
            'ReportState',
            'SetRangeValue',
            'AdjustRangeValue'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.RangeController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        return ['rangeValue'];
    }

    public function getCapabilityInformation($configuration)
    {
        $info = parent::getCapabilityInformation($configuration);
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
        $shutterProfile = $this->hasShutterProfile($configuration);
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

    protected function getSupportedProfiles()
    {
        return [
            self::capabilityPrefix . 'ID' => ['~ShutterMoveStop', '~ShutterMoveStep', '~Intensity.100', '~Intensity.255', '~Intensity.1']
        ];
    }

    protected function getSupportedPresentations()
    {
        return [
            self::capabilityPrefix . 'ID' => [
                VARIABLE_PRESENTATION_SHUTTER => ['USAGE_TYPE' => 0],
                VARIABLE_PRESENTATION_LEGACY  => ['PROFILE' => ['~ShutterMoveStop', '~ShutterMoveStep', '~Intensity.100', '~Intensity.255', '~Intensity.1']]
            ]
        ];
    }

    private function hasShutterProfile($configuration)
    {
        return $this->getShutterCompatibility($configuration[self::capabilityPrefix . 'ID']) == 'OK';
    }

    private function computePropertiesForValue($value)
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
}
