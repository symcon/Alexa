<?php

declare(strict_types=1);

class CapabilityBrightnessOnlyController extends Capability
{
    use HelperDimDevice;
    const capabilityPrefix = 'BrightnessOnlyController';

    public function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.BrightnessController',
                'name'                      => 'brightness',
                'value'                     => $value,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return $this->computePropertiesForValue($this->getDimValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Brightness Variable',
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
            return $this->getDimCompatibility($configuration[self::capabilityPrefix . 'ID']);
        }
    }

    public function getStatusPrefix()
    {
        return 'Brightness: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setDimValue = function ($configuration, $value, $emulateStatus)
        {
            if ($this->dimDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue($value);
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

            case 'AdjustBrightness':
                return $setDimValue($configuration, $this->getDimValue($configuration[self::capabilityPrefix . 'ID']) + $payload['brightnessDelta'], $emulateStatus);

            case 'SetBrightness':
                return $setDimValue($configuration, $payload['brightness'], $emulateStatus);

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
            'AdjustBrightness',
            'SetBrightness'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.BrightnessController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        if ($configuration[self::capabilityPrefix . 'ID'] == 0) {
            return null;
        } else {
            return [
                'brightness'
            ];
        }
    }
}
