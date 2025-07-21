<?php

declare(strict_types=1);

class DeviceTypeLightColor extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'ColorController'
        ];
        $this->displayedCategories = [
            'LIGHT'
        ];
    }

    public function getPosition()
    {
        return 2;
    }

    public function getCaption()
    {
        return 'Light (Color)';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Light (Color)'  => 'Licht (Farbe)',
                'Color Variable' => 'Farbvariable',
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightColor');
