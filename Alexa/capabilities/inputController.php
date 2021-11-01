<?php

declare(strict_types=1);

class CapabilityInputController extends Capability
{
    use HelperStringDevice;
    const capabilityPrefix = 'InputController';

    const VALID_INPUTS = ['AUX 1', 'AUX 2', 'AUX 3', 'AUX 4', 'AUX 5', 'AUX 6', 'AUX 7', 'BLURAY', 'CABLE', 'CD', 'COAX 1', 'COAX 2',
        'COMPOSITE 1', 'DVD', 'GAME', 'HD RADIO', 'HDMI 1', 'HDMI 2', 'HDMI 3', 'HDMI 4', 'HDMI 5', 'HDMI 6', 'HDMI 7',
        'HDMI 8', 'HDMI 9', 'HDMI 10', 'HDMI ARC', 'INPUT 1', 'INPUT 2', 'INPUT 3', 'INPUT 4', 'INPUT 5', 'INPUT 6',
        'INPUT 7', 'INPUT 8', 'INPUT 9', 'INPUT 10', 'IPOD', 'LINE 1', 'LINE 2', 'LINE 3', 'LINE 4', 'LINE 5', 'LINE 6',
        'LINE 7', 'MEDIA PLAYER', 'OPTICAL 1', 'OPTICAL 2', 'PHONO', 'PLAYSTATION', 'PLAYSTATION 3', 'PLAYSTATION 4',
        'SATELLITE', 'SMARTCAST', 'TUNER', 'TV', 'USB DAC', 'VIDEO 1', 'VIDEO 2', 'VIDEO 3', 'XBOX'];

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return $this->computePropertiesForValue($this->getStringValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        $values = [];
        foreach (self::VALID_INPUTS as $input) {
            $values[] = [
                'name' => $input
            ];
        }
        return [
            [
                'label' => 'Input Variable',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label'    => 'Supported Inputs',
                'name'     => self::capabilityPrefix . 'Supported',
                'width'    => 0,
                'add'      => [],
                'visible'  => false,
                'edit'     => [
                    'type'     => 'List',
                    'rowCount' => count(self::VALID_INPUTS),
                    'columns'  => [
                        [
                            'caption' => 'Input',
                            'name'    => 'name',
                            'width'   => 'auto'
                        ],
                        [
                            'caption' => '',
                            'name'    => 'selected',
                            'width'   => '24px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
                    ],
                    'values'   => $values
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        $stringStatus = $this->getStringCompatibility($configuration[self::capabilityPrefix . 'ID']);
        if ($stringStatus != 'OK') {
            return $stringStatus;
        }

        foreach ($configuration[self::capabilityPrefix . 'Supported'] as $input) {
            if ($input['selected']) {
                return 'OK';
            }
        }

        return 'No possible inputs';
    }

    public function getStatusPrefix()
    {
        return 'Input: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $switchInput = function ($configuration, $value, $emulateStatus)
        {
            if ($this->setStringValue($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != $this->getStringValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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
                        'type' 		=> 'NO_SUCH_ENDPOINT'
                    ],
                    'eventName'      => 'ErrorResponse',
                    'eventNamespace' => 'Alexa'
                ];
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

            case 'SelectInput':
                return $switchInput($configuration, $payload['input'], $emulateStatus);

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
            'SelectInput'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.InputController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        return [
            'input'
        ];
    }

    public function getCapabilityInformation($configuration)
    {
        $info = parent::getCapabilityInformation($configuration);
        $inputs = [];
        foreach ($configuration[self::capabilityPrefix . 'Supported'] as $i => $input) {
            if ($input['selected']) {
                $inputs[] = [
                    'name' => self::VALID_INPUTS[$i]
                ];
            }
        }
        $info[0]['inputs'] = $inputs;
        return $info;
    }

    private function computePropertiesForValue($value)
    {
        if (!in_array($value, self::VALID_INPUTS)) {
            $value = self::VALID_INPUTS[0];
        }
        return [
            [
                'namespace'                 => 'Alexa.InputController',
                'name'                      => 'input',
                'value'                     => $value,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
