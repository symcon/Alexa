<?php

declare(strict_types=1);

class CapabilitySceneControllerDeactivatable
{
    const capabilityPrefix = 'SceneControllerDeactivatable';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperStartScript;

    public static function computeProperties($configuration)
    {
        return [];
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'ActivateScript',
                'name'  => self::capabilityPrefix . 'ActivateID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectScript'
                ]
            ],
            [
                'label' => 'DeactivateScript',
                'name'  => self::capabilityPrefix . 'DeactivateID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectScript'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        $activateStatus = self::getScriptCompatibility($configuration[self::capabilityPrefix . 'ActivateID']);
        if ($activateStatus != 'OK') {
            return $activateStatus;
        } else {
            return self::getScriptCompatibility($configuration[self::capabilityPrefix . 'DeactivateID']);
        }
    }

    public static function getStatusPrefix()
    {
        return 'Scene: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        switch ($directive) {
            case 'Activate':
            case 'Deactivate':
                $scriptID = $configuration[self::capabilityPrefix . (($directive == 'Activate') ? 'ActivateID' : 'DeactivateID')];
                if (self::startScript($scriptID, ($directive == 'Activate'))) {
                    return [
                        'properties' => self::computeProperties($configuration),
                        'payload'    => [
                            'cause' => [
                                'type' => 'VOICE_INTERACTION'
                            ],
                            'timestamp' => gmdate(self::DATE_TIME_FORMAT)
                        ],
                        'eventName'      => ($directive == 'Activate') ? 'ActivationStarted' : 'DeactivationStarted',
                        'eventNamespace' => 'Alexa.SceneController'
                    ];
                } else {
                    return [
                        'payload' => [
                            'type' => 'NO_SUCH_ENDPOINT'
                        ],
                        'eventName'      => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                }
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function getCapabilityInformation()
    {
        return [[
            'type'                 => 'AlexaInterface',
            'interface'            => 'Alexa.SceneController',
            'version'              => '3',
            'supportsDeactivation' => true,
            'proactivelyReported'  => false
        ]];
    }

    public static function supportedDirectives()
    {
        return [
            'Activate',
            'Deactivate'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.SceneController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        return [];
    }
}
