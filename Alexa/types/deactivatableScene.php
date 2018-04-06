<?php

declare(strict_types=1);

class DeviceTypeDeactivatableScene
{
    private static $implementedCapabilities = [
        'SceneControllerDeactivatable'
    ];

    private static $displayedCategories = [
        'SCENE_TRIGGER'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 100;
    }

    public static function getCaption()
    {
        return 'Scenes (Deactivatable)';
    }
}

DeviceTypeRegistry::register('DeactivatableScene');
