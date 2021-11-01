<?php

declare(strict_types=1);

class DeviceTypeDeactivatableScene extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'SceneControllerDeactivatable'
        ];
        $this->displayedCategories = [
            'SCENE_TRIGGER'
        ];
    }

    public function getPosition()
    {
        return 101;
    }

    public function getCaption()
    {
        return 'Scenes (Deactivatable)';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Scenes (Deactivatable)' => 'Szenen (deaktivierbar)',
                'ActivateScript'         => 'AktivierenSkript',
                'DeactivateScript'       => 'DeaktivierenSkript'
            ]
        ];
    }

    public function isExpertDevice()
    {
        return true;
    }
}

DeviceTypeRegistry::register('DeactivatableScene');
