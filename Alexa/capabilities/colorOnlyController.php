<?php

declare(strict_types=1);

class CapabilityColorOnlyController extends Capability
{
    use HelperColorDevice;
    const capabilityPrefix = 'ColorOnlyController';

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
                'caption' => 'Color Variable',
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
        if ($configuration[self::capabilityPrefix . 'ID'] == 0) {
            return 'OK';
        } else {
            return $this->getColorCompatibility($configuration[self::capabilityPrefix . 'ID']);
        }
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
            'SetColor'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.ColorController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        if ($configuration[self::capabilityPrefix . 'ID'] == 0) {
            return null;
        } else {
            return [
                'color'
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
                'namespace'                 => 'Alexa.ColorController',
                'name'                      => 'color',
                'value'                     => $this->rgbToHSB($value),
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
