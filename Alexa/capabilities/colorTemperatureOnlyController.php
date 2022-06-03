<?php

declare(strict_types=1);

class CapabilityColorTemperatureOnlyController extends Capability
{
    use HelperNumberDevice;
    const capabilityPrefix = 'ColorTemperatureOnlyController';

    const COLOR_TEMPERATURE_STEPSIZE = 3000;
    const COLOR_TEMPERATURE_MAX = 12000;
    const COLOR_TEMPERATURE_MIN = 1000;

    public function computeProperties($configuration)
    {
        // Check if field in configuration is set, as it was added later on for expert light
        if (isset($configuration[self::capabilityPrefix . 'ID']) && IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return $this->computePropertiesForValue($this->getNumberValue($configuration[self::capabilityPrefix . 'ID']));
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Color Temperature Variable',
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
        if (!isset($configuration[self::capabilityPrefix . 'ID']) || ($configuration[self::capabilityPrefix . 'ID'] == 0)) {
            return 'OK';
        } else {
            return $this->getNumberCompatibility($configuration[self::capabilityPrefix . 'ID']);
        }
    }

    public function getStatusPrefix()
    {
        return 'Color Temperature: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setColorTemperature = function ($configuration, $value, $emulateStatus)
        {
            if ($this->setNumberValue($configuration[self::capabilityPrefix . 'ID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue($value);
                } else {
                    $i = 0;
                    while (($value != $this->getNumberValue($configuration[self::capabilityPrefix . 'ID'])) && $i < 10) {
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

            case 'SetColorTemperature':
                return $setColorTemperature($configuration, $payload['colorTemperatureInKelvin'], $emulateStatus);

            case 'IncreaseColorTemperature': {
                $newValue = $this->getNumberValue($configuration[self::capabilityPrefix . 'ID']) + self::COLOR_TEMPERATURE_STEPSIZE;
                $newValue = min($newValue, self::COLOR_TEMPERATURE_MAX);
                return $setColorTemperature($configuration, $newValue, $emulateStatus);
            }

            case 'DecreaseColorTemperature': {
                $newValue = $this->getNumberValue($configuration[self::capabilityPrefix . 'ID']) - self::COLOR_TEMPERATURE_STEPSIZE;
                $newValue = max($newValue, self::COLOR_TEMPERATURE_MIN);
                return $setColorTemperature($configuration, $newValue, $emulateStatus);
            }

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public function getObjectIDs($configuration)
    {
        // Due to legacy versions, it is possible, that the ID is not set
        if (!isset($configuration[self::capabilityPrefix . 'ID'])) {
            return [];
        }
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
    }

    public function supportedDirectives()
    {
        return [
            'ReportState',
            'SetColorTemperature',
            'IncreaseColorTemperature',
            'DecreaseColorTemperature'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.ColorTemperatureController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        if (!isset($configuration[self::capabilityPrefix . 'ID']) || ($configuration[self::capabilityPrefix . 'ID'] == 0)) {
            return null;
        } else {
            return [
                'colorTemperatureInKelvin'
            ];
        }
    }

    protected function getSupportedProfiles()
    {
        return [
            self::capabilityPrefix . 'ID' => ['~TWColor']
        ];
    }

    private function computePropertiesForValue($value)
    {
        return [
            [
                'namespace'                 => 'Alexa.ColorTemperatureController',
                'name'                      => 'colorTemperatureInKelvin',
                'value'                     => $value,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ]
        ];
    }
}
