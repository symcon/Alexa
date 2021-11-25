<?php

declare(strict_types=1);

include_once __DIR__ . '/../libs/WebOAuthModule.php';
include_once __DIR__ . '/helper/autoload.php';
include_once __DIR__ . '/registry.php';
include_once __DIR__ . '/capabilities/autoload.php';
include_once __DIR__ . '/types/autoload.php';
include_once __DIR__ . '/simulate.php';

class Alexa extends WebOAuthModule
{
    use Simulate, CommonConnectVoiceAssistant {
        Create as BaseCreate;
        ApplyChanges as BaseApplyChanges;
        GetConfigurationForm as BaseGetConfigurationForm;
    }

    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID, 'amazon_smarthome');

        $this->registry = new DeviceTypeRegistry(
            $this->InstanceID,
            function ($Name, $Value)
            {
                $this->RegisterPropertyString($Name, $Value);
            }
        );
    }

    public function Create()
    {
        $this->BaseCreate();

        $this->RegisterPropertyBoolean('EmulateStatus', false);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Transform legacy scenes to new version with action (6.1)
        $wasUpdated = false;
        $simpleScenes = json_decode($this->ReadPropertyString('DeviceSimpleScene'), true);
        if (isset($simpleScenes[0]['SceneControllerSimpleID'])) {
            for ($i = 0; $i < count($simpleScenes); $i++) {
                $simpleScenes[$i]['SceneControllerSimpleAction'] = json_encode([
                    'actionID'   => '{64087366-07B7-A3D6-F6BA-734BDA4C4FAB}',
                    'parameters' => [
                        'BOOLEANPARAMETERS' => json_encode([[
                            'name'  => 'VALUE',
                            'value' => true
                        ]]),
                        'NUMERICPARAMETERS' => json_encode([]),
                        'STRINGPARAMETERS'  => json_encode([[
                            'name'  => 'SENDER',
                            'value' => 'VoiceControl'
                        ]]),
                        'TARGET' => $simpleScenes[$i]['SceneControllerSimpleID']
                    ]
                ]);
                unset($simpleScenes[$i]['SceneControllerSimpleID']);
            }
            IPS_SetProperty($this->InstanceID, 'DeviceSimpleScene', json_encode($simpleScenes));
            $wasUpdated = true;
        }

        $deactivatableScenes = json_decode($this->ReadPropertyString('DeviceDeactivatableScene'), true);
        if (isset($deactivatableScenes[0]['SceneControllerDeactivatableActivateID'])) {
            for ($i = 0; $i < count($deactivatableScenes); $i++) {
                $deactivatableScenes[$i]['SceneControllerDeactivatableActivateAction'] = json_encode([
                    'actionID'   => '{64087366-07B7-A3D6-F6BA-734BDA4C4FAB}',
                    'parameters' => [
                        'BOOLEANPARAMETERS' => json_encode([[
                            'name'  => 'VALUE',
                            'value' => true
                        ]]),
                        'NUMERICPARAMETERS' => json_encode([]),
                        'STRINGPARAMETERS'  => json_encode([[
                            'name'  => 'SENDER',
                            'value' => 'VoiceControl'
                        ]]),
                        'TARGET' => $deactivatableScenes[$i]['SceneControllerDeactivatableActivateID']
                    ]
                ]);
                $deactivatableScenes[$i]['SceneControllerDeactivatableDeactivateAction'] = json_encode([
                    'actionID'   => '{64087366-07B7-A3D6-F6BA-734BDA4C4FAB}',
                    'parameters' => [
                        'BOOLEANPARAMETERS' => json_encode([[
                            'name'  => 'VALUE',
                            'value' => false
                        ]]),
                        'NUMERICPARAMETERS' => json_encode([]),
                        'STRINGPARAMETERS'  => json_encode([[
                            'name'  => 'SENDER',
                            'value' => 'VoiceControl'
                        ]]),
                        'TARGET' => $deactivatableScenes[$i]['SceneControllerDeactivatableDeactivateID']
                    ]
                ]);
                unset($deactivatableScenes[$i]['SceneControllerDeactivatableActivateID']);
                unset($deactivatableScenes[$i]['SceneControllerDeactivatableDeactivateID']);
            }
            IPS_SetProperty($this->InstanceID, 'DeviceDeactivatableScene', json_encode($deactivatableScenes));
            $wasUpdated = true;
        }

        if ($wasUpdated) {
            IPS_ApplyChanges($this->InstanceID);
            return;
        }

        $this->BaseApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $configurationForm = json_decode($this->BaseGetConfigurationForm(), true);

        $expertMode = [
            [
                'type'    => 'PopupButton',
                'caption' => 'Expert Options',
                'popup'   => [
                    'caption' => 'Expert Options',
                    'items'   => [
                        [
                            'type'    => 'Label',
                            'caption' => 'Please check the documentation before handling these settings. These settings do not need to be changed under regular circumstances.'
                        ],
                        [
                            'type'    => 'CheckBox',
                            'caption' => 'Emulate Status',
                            'name'    => 'EmulateStatus'
                        ],
                        [
                            'type'     => 'CheckBox',
                            'caption'  => 'Show Expert Devices',
                            'name'     => 'ShowExpertDevices',
                            'onChange' => 'AA_UIUpdateExpertVisibility($id, $ShowExpertDevices);'
                        ]
                    ]
                ]
            ]
        ];

        $configurationForm['translations']['de']['Expert Options'] = 'Expertenoptionen';
        $configurationForm['translations']['de']['Please check the documentation before handling these settings. These settings do not need to be changed under regular circumstances.'] = 'Bitte prüfen Sie die Dokumentation bevor Sie diese Einstellungen anpassen. Diese Einstellungen müssen unter normalen Umständen nicht verändert werden.';
        $configurationForm['translations']['de']['Emulate Status'] = 'Status emulieren';

        $configurationForm['elements'] = array_merge($configurationForm['elements'], $expertMode);

        return json_encode($configurationForm);
    }

    protected function ProcessData(array $data): array
    {
        $this->SendDebug('Request', json_encode($data), 0);
        //Redirect errors to our variable to push them into Debug
        ob_start();
        $result = $this->ProcessRequest($data);
        $error = ob_get_contents();
        if ($error != '') {
            $this->SendDebug('Error', $error, 0);
        }
        ob_end_clean();

        $this->SendDebug('Response', json_encode($result), 0);

        return $result;
    }

    protected function ProcessOAuthData()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $this->ProcessData($data);
        echo json_encode($result);
    }

    private function GenerateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function ProcessDiscovery(): array
    {
        if ($this->GetStatus() !== 102) {
            return [
                'event' => [
                    'header' => [
                        'namespace'      => 'Alexa',
                        'name'           => 'ErrorResponse',
                        'payloadVersion' => '3',
                        'messageId'      => $this->GenerateUUID()
                    ],
                    'payload' => [
                        'type' => 'INTERNAL_ERROR'
                    ]
                ]
            ];
        }

        return [
            'event' => [
                'header' => [
                    'namespace'      => 'Alexa.Discovery',
                    'name'           => 'Discover.Response',
                    'payloadVersion' => '3',
                    'messageId'      => $this->GenerateUUID()
                ],
                'payload' => [
                    'endpoints' => $this->registry->doDiscovery()
                ]
            ]
        ];
    }

    private function ProcessDirective($directive): array
    {
        if (!isset($directive['header'])) {
            throw new Exception('header is undefined');
        }
        if (!isset($directive['header']['name'])) {
            throw new Exception('header name is undefined');
        }
        if (!isset($directive['header']['correlationToken'])) {
            throw new Exception('correlation token is undefined');
        }
        if (!isset($directive['endpoint'])) {
            throw new Exception('endpoint is undefined');
        }
        if (!isset($directive['endpoint']['endpointId'])) {
            throw new Exception('endpoint id is undefined');
        }
        $payload = isset($directive['payload']) ? $directive['payload'] : new stdClass();

        //Execute each executions command for each device
        $result = [];

        if ($this->GetStatus() !== 102) {
            $result = [
                'payload' => [
                    'type' => 'INTERNAL_ERROR'
                ],
                'eventName'      => 'ErrorResponse',
                'eventNamespace' => 'Alexa'
            ];
        } else {
            $result = $this->registry->doDirective($directive['endpoint']['endpointId'], $directive['header']['name'], $payload);
        }

        $response = [];
        if (isset($result['properties'])) {
            $response['context'] = [
                'properties' => $result['properties']
            ];
        }

        $this->SendDebug('Result', json_encode($result), 0);

        $response['event'] = [
            'header' => [
                'namespace'        => $result['eventNamespace'],
                'name'             => $result['eventName'],
                'payloadVersion'   => '3',
                'messageId'        => $this->GenerateUUID(),
                'correlationToken' => $directive['header']['correlationToken']
            ],
            'endpoint' => [
                'endpointId' => $directive['endpoint']['endpointId']
            ],
            'payload' => $result['payload']
        ];

        return $response;
    }

    private function ProcessRequest($request): array
    {
        if (isset($request['directive']) &&
            isset($request['directive']['header']) &&
            isset($request['directive']['header']['namespace']) &&
            isset($request['directive']['header']['name']) &&
            ($request['directive']['header']['namespace'] == 'Alexa.Discovery') &&
            ($request['directive']['header']['name'] == 'Discover')) {
            return $this->ProcessDiscovery($request['directive']);
        } elseif (isset($request['directive'])) {
            return $this->ProcessDirective($request['directive']);
        }
    }
}
