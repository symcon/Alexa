<?php

declare(strict_types=1);

class DeviceTypeShutter extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'RangeControllerShutter'
        ];
        $this->displayedCategories = [
            'EXTERIOR_BLIND'
        ];
    }

    public function getPosition()
    {
        return 40;
    }

    public function getCaption()
    {
        return 'Shutter';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Shutter'          => 'Rollladen',
                'Shutter Variable' => 'Rollladen Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Shutter');
