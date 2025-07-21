<?php

declare(strict_types=1);

class DeviceTypeLightDimmer extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'BrightnessController'
        ];
        $this->displayedCategories = [
            'LIGHT'
        ];
    }

    public function getPosition()
    {
        return 1;
    }

    public function getCaption()
    {
        return 'Light (Dimmer)';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Light (Dimmer)'      => 'Licht (Dimmer)',
                'Brightness Variable' => 'Helligkeitsvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightDimmer');
