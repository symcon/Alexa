<?php

declare(strict_types=1);

class CapabilitySceneControllerDeactivatable extends Capability
{
    use HelperStartScript;
    const capabilityPrefix = 'SceneControllerDeactivatable';

    public function computeProperties($configuration)
    {
        return [];
    }

    public function getColumns()
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

    public function getStatus($configuration)
    {
        $activateStatus = $this->getScriptCompatibility($configuration[self::capabilityPrefix . 'ActivateID']);
        if ($activateStatus != 'OK') {
            return $activateStatus;
        } else {
            return $this->getScriptCompatibility($configuration[self::capabilityPrefix . 'DeactivateID']);
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
                $scriptID = $configuration[self::capabilityPrefix . (($directive == 'Activate') ? 'ActivateID' : 'DeactivateID')];
                if ($this->startScript($scriptID, ($directive == 'Activate'))) {
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
        return [
            $configuration[self::capabilityPrefix . 'ActivateID'], $configuration[self::capabilityPrefix . 'DeactivateID']
        ];
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
