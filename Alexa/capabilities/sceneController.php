<?php

declare(strict_types=1);

class CapabilitySceneController extends Capability
{
    use HelperStartScript;
    const capabilityPrefix = 'SceneControllerSimple';

    public function computeProperties($configuration)
    {
        return [];
    }

    public function getColumns()
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

    public function getStatus($configuration)
    {
        return $this->getScriptCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Scene: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        switch ($directive) {
            case 'Activate':
                if ($this->startScript($configuration[self::capabilityPrefix . 'ID'])) {
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
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
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
