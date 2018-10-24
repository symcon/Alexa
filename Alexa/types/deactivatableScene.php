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

    private static $displayStatusPrefix = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 101;
    }

    public static function getCaption()
    {
        return 'Scenes (Deactivatable)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Scenes (Deactivatable)' => 'Szenen (deaktivierbar)',
                'ActivateScript'         => 'AktivierenSkript',
                'DeactivateScript'       => 'DeaktivierenSkript'
            ]
        ];
    }
}

DeviceTypeRegistry::register('DeactivatableScene');
