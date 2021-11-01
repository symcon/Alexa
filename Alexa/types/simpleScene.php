<?php

declare(strict_types=1);

class DeviceTypeSimpleScene extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'SceneController'
        ];
        $this->displayedCategories = [
            'SCENE_TRIGGER'
        ];
    }

    public function getPosition()
    {
        return 100;
    }

    public function getCaption()
    {
        return 'Scenes';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Scenes' => 'Szenen',
                'Script' => 'Skript'
            ]
        ];
    }

    public function isExpertDevice()
    {
        return true;
    }
}

DeviceTypeRegistry::register('SimpleScene');
