<?php

declare(strict_types=1);

class CapabilityColorController extends Capability
{
    use HelperColorDevice;
    const capabilityPrefix = 'ColorController';

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return $this->computePropertiesForValue($this->getColorValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Variable',
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
        return $this->getColorCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Color: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setColor = function ($configuration, $value, $emulateStatus)
        {
            if ($this->colorDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != $this->getColorValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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
                    'properties'     => $this->computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'SetColor':
                return $setColor($configuration, $this->hsbToRGB($payload['color']), $emulateStatus);

            case 'AdjustBrightness':
                {
                    $currentBrightness = $this->getColorBrightness($configuration[self::capabilityPrefix . 'ID']);
                    return $setColor($configuration, $this->computeColorBrightness($configuration[self::capabilityPrefix . 'ID'], $currentBrightness + $payload['brightnessDelta']), $emulateStatus);
                }
                break;

            case 'SetBrightness':
                return $setColor($configuration, $this->computeColorBrightness($configuration[self::capabilityPrefix . 'ID'], $payload['brightness']), $emulateStatus);

            case 'TurnOn':
            case 'TurnOff':
                $value = ($directive == 'TurnOn' ? 0xFFFFFF : 0);
                return $setColor($configuration, $value, $emulateStatus);

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
            'SetColor',
            'AdjustBrightness',
            'SetBrightness',
            'TurnOn',
            'TurnOff'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.ColorController',
            'Alexa.BrightnessController',
            'Alexa.PowerController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
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

    protected function getSupportedProfiles()
    {
        return [
            self::capabilityPrefix . 'ID' => ['~HexColor']
        ];
    }

    private function computePropertiesForValue($value)
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
                'value'                     => $this->getColorBrightnessByValue($value),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ],
            [
                'namespace'                 => 'Alexa.ColorController',
                'name'                      => 'color',
                'value'                     => $this->rgbToHSB($value),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
