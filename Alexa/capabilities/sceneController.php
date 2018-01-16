<?php

declare(strict_types=1);

class CapabilitySceneController
{
    const capabilityPrefix = 'SceneControllerSimple';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperStartScript;

    private static function computeProperties($configuration)
    {
        return [];
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'ScriptID',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '100px',
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

    public static function doDirective($configuration, $directive, $data)
    {
        switch ($directive) {
            case 'Activate':
                $newValue = ($directive == 'TurnOn');
                if (self::startScript($configuration[self::capabilityPrefix . 'ID'])) {
                    return [
                        'properties' => self::computeProperties($configuration),
                        'payload'    => [
                            'cause' => [
                                'type' => 'VOICE_INTERACTION'
                            ],
                            'timestamp' => gmdate(self::DATE_TIME_FORMAT)
                        ],
                        'eventName'  => 'ActivationStarted',
                        'eventNamespace' => 'Alexa.SceneController'
                    ];
                } else {
                    return [
                        'payload' => [
                            'type' => 'NO_SUCH_ENDPOINT'
                        ],
                        'eventName' => 'ErrorResponse',
                        'eventNamespace' => 'Alexa'
                    ];
                }
                break;
            default:
                throw new Exception('Command is not supported by this trait!');
        }
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

    public static function supportedProperties($realCapability)
    {
        return [];
    }
}
