<?php

declare(strict_types=1);

class CapabilitySceneController
{
    const capabilityPrefix = 'SceneControllerSimple';
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
                'label' => 'Script',
                'name'  => self::capabilityPrefix . 'ID',
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
        return self::getScriptCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function getStatusPrefix()
    {
        return 'Scene: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        switch ($directive) {
            case 'Activate':
                if (self::startScript($configuration[self::capabilityPrefix . 'ID'])) {
                    return [
                        'properties' => self::computeProperties($configuration),
                        'payload'    => [
                            'cause' => [
                                'type' => 'VOICE_INTERACTION'
                            ],
                            'timestamp' => gmdate(self::DATE_TIME_FORMAT)
                        ],
                        'eventName'      => 'ActivationStarted',
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
            'supportsDeactivation' => false,
            'proactivelyReported'  => false
        ]];
    }

    public static function supportedDirectives()
    {
        return [
            'Activate'
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
