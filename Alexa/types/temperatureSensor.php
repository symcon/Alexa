<?php

declare(strict_types=1);

class DeviceTypeTemperatureSensor extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'TemperatureSensor'
        ];
        $this->displayedCategories = [
            'TEMPERATURE_SENSOR'
        ];
    }

    public function getPosition()
    {
        return 20;
    }

    public function getCaption()
    {
        return 'Temperature Sensor';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Temperature Sensor' => 'Temperatursensor',
                'Sensor Variable'    => 'Sensorvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('TemperatureSensor');
