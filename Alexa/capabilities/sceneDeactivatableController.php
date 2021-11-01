<?php

declare(strict_types=1);

class CapabilitySceneControllerDeactivatable extends Capability
{
    use HelperStartAction;
    const capabilityPrefix = 'SceneControllerDeactivatable';

    public function computeProperties($configuration)
    {
        return [];
    }

    public function getColumns()
    {
        return [
            [
                'label' => 'Activate Action',
                'name'  => self::capabilityPrefix . 'ActivateAction',
                'width' => '400px',
                'add'   => '{}',
                'edit'  => [
                    'type' => 'SelectAction',
                    'saveEnvironment' => false,
                    'saveParent' => false,
                    'environment' => 'VoiceControl'
                ]
            ],
            [
                'label' => 'Deactivate Action',
                'name'  => self::capabilityPrefix . 'DeactivateAction',
                'width' => '400px',
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
        $activateStatus = $this->getActionCompatibility($configuration[self::capabilityPrefix . 'ActivateAction']);
        if ($activateStatus != 'OK') {
            return $activateStatus;
        } else {
            return $this->getScriptCompatibility($configuration[self::capabilityPrefix . 'DeactivateAction']);
        }
    }

    public function getStatusPrefix()
    {
        return 'Scene: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        switch ($directive) {
            case 'Activate':
            case 'Deactivate':
                $action = $configuration[self::capabilityPrefix . (($directive == 'Activate') ? 'ActivateAction' : 'DeactivateAction')];
                if ($this->startAction($action, $this->instanceID)) {
                    return [
                        'properties' => $this->computeProperties($configuration),
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

    public function getObjectIDs($configuration)
    {
        $result = [];
        foreach(['ActivateAction', 'DeactivateAction'] as $field) {
            if ($this->getActionCompatibility($configuration[self::capabilityPrefix . $field]) === 'OK') {
                $result[] = json_decode($configuration[self::capabilityPrefix . $field], true)['parameters']['TARGET'];
            }
        }
        return $result;
    }

    public function getCapabilityInformation($configuration)
    {
        return [[
            'type'                 => 'AlexaInterface',
            'interface'            => 'Alexa.SceneController',
            'version'              => '3',
            'supportsDeactivation' => true,
            'proactivelyReported'  => false
        ]];
    }

    public function supportedDirectives()
    {
        return [
            'Activate',
            'Deactivate'
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
