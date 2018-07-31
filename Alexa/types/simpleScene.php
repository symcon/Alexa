<?php

declare(strict_types=1);

class DeviceTypeSimpleScene
{
    private static $implementedCapabilities = [
        'SceneController'
    ];

    private static $displayedCategories = [
        'SCENE_TRIGGER'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 99;
    }

    public static function getCaption()
    {
        return 'Scenes';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Scenes' => 'Szenen',
                'Script' => 'Skript'
            ]
        ];
    }
}

DeviceTypeRegistry::register('SimpleScene');
