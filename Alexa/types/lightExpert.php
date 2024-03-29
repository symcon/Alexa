<?php

declare(strict_types=1);

class DeviceTypeLightExpert extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'PowerController',
            'BrightnessOnlyController',
            'ColorOnlyController',
            'ColorTemperatureOnlyController'
        ];
        $this->displayedCategories = [
            'LIGHT'
        ];
        $this->displayStatusPrefix = true;
        $this->detectionRequiredCapabilities = [
            'PowerController'
        ];
        $this->detectionMinimumCapabilities = 2;
    }

    public function getPosition()
    {
        return 3;
    }

    public function getCaption()
    {
        return 'Light (Expert)';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Light (Expert)'             => 'Licht (Experte)',
                'Switch Variable'            => 'Schaltervariable',
                'Brightness Variable'        => 'Helligkeitsvariable',
                'Color Variable'             => 'Farbvariable',
                'Color Temperature Variable' => 'Farbtemperaturvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightExpert');
