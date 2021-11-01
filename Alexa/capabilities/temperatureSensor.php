<?php

declare(strict_types=1);

class CapabilityTemperatureSensor extends Capability
{
    use HelperGetFloatDevice;
    const capabilityPrefix = 'TemperatureSensor';

    public function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.TemperatureSensor',
                    'name'                      => 'temperature',
                    'value'                     => [
                        'value' => floatval($this->getFloatValue($configuration[self::capabilityPrefix . 'ID'])),
                        'scale' => 'CELSIUS'
                    ],
                    'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                    'uncertaintyInMilliseconds' => 0
                ]
            ];
        } else {
            return [];
        }
    }

    public function getColumns()
    {
        return [
            [
                'label' => 'SensorVariable',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        return $this->getGetFloatCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Temperature Sensor: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        switch ($directive) {
            case 'ReportState':
                return [
                    'properties'     => $this->computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

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
            'ReportState'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.TemperatureSensor'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        return [
            'temperature'
        ];
    }
}
