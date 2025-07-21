<?php

declare(strict_types=1);

class DeviceTypeGenericSlider extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'PercentageController'
        ];
        $this->displayedCategories = [
            'SWITCH'
        ];
    }

    public function getPosition()
    {
        return 51;
    }

    public function getCaption()
    {
        return 'Generic Slider';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Generic Slider'  => 'Generischer Schieberegler',
                'Slider Variable' => 'Reglervariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('GenericSlider');
