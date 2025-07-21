<?php

declare(strict_types=1);

class DeviceTypeLock extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'LockController'
        ];
        $this->displayedCategories = [
            'SMARTLOCK'
        ];
    }

    public function getPosition()
    {
        return 10;
    }

    public function getCaption()
    {
        return 'Lock';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Lock'          => 'Schloss',
                'Lock Variable' => 'Schlossvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Lock');
