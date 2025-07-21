<?php

declare(strict_types=1);

class DeviceTypeThermostat extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'ThermostatController'
        ];
        $this->displayedCategories = [
            'THERMOSTAT'
        ];
    }

    public function getPosition()
    {
        return 21;
    }

    public function getCaption()
    {
        return 'Thermostat';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Thermostat'           => 'Thermostat',
                'Temperature Variable' => 'Temperaturvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Thermostat');
