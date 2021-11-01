<?php

declare(strict_types=1);

class DeviceTypeLightSwitch extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'PowerController'
        ];
        $this->displayedCategories = [
            'LIGHT'
        ];
    }

    public function getPosition()
    {
        return 0;
    }

    public function getCaption()
    {
        return 'Light (Switch)';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Light (Switch)'  => 'Licht (Schalter)',
                'Switch Variable' => 'Schaltervariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightSwitch');
