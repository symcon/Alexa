<?php

declare(strict_types=1);

class CapabilitySceneController extends Capability
{
    use HelperStartAction;
    const capabilityPrefix = 'SceneControllerSimple';

    public function computeProperties($configuration)
    {
        return [];
    }

    public function getColumns()
    {
        return [
            [
                'label' => 'Action',
                'name'  => self::capabilityPrefix . 'Action',
                'width' => '500px',
                'add'   => '{}',
                'edit'  => [
                    'type' => 'SelectAction',
                    'saveEnvironment' => false,
                    'saveParent' => false,
                    'environment' => 'VoiceControl'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        return $this->getActionCompatibility($configuration[self::capabilityPrefix . 'Action']);
    }

    public function getStatusPrefix()
    {
        return 'Scene: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        switch ($directive) {
            case 'Activate':
                if ($this->startAction($configuration[self::capabilityPrefix . 'Action'], $this->instanceID)) {
                    return [
                        'properties' => $this->computeProperties($configuration),
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

    public function getObjectIDs($configuration)
    {
        if ($this->getStatus($configuration) === 'OK') {
            return [ json_decode($configuration[self::capabilityPrefix . 'Action'], true)['parameters']['TARGET'] ];
        }
        else {
            return [];
        }
    }

    public function getCapabilityInformation($configuration)
    {
        return [[
            'type'                 => 'AlexaInterface',
            'interface'            => 'Alexa.SceneController',
            'version'              => '3',
            'supportsDeactivation' => false,
            'proactivelyReported'  => false
        ]];
    }

    public function supportedDirectives()
    {
        return [
            'Activate'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.SceneController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        return [];
    }
}
