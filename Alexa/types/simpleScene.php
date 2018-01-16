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
        return 1;
    }

    public static function getCaption()
    {
        return 'Scenes';
    }
}

DeviceTypeRegistry::register('SimpleScene');
