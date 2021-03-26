<?php

declare(strict_types=1);

include_once __DIR__ . '/../libs/WebOAuthModule.php';
include_once __DIR__ . '/simulate.php';
include_once __DIR__ . '/registry.php';
include_once __DIR__ . '/helper/autoload.php';
include_once __DIR__ . '/capabilities/autoload.php';
include_once __DIR__ . '/types/autoload.php';

class Alexa extends WebOAuthModule
{
    use Simulate;

    private $registry = null;
    private $apiKey = 'AIzaSyAtQwhb65ITHYJZXd-x7ziBfKkNj5rTo1k';

    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID, 'amazon_smarthome');

        $this->registry = new DeviceTypeRegistry(
            $this->InstanceID,
            function ($Name, $Value) {
                $this->RegisterPropertyString($Name, $Value);
            }
        );
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        if (!IPS_VariableProfileExists('ThermostatMode.GA')) {
            IPS_CreateVariableProfile('ThermostatMode.GA', 1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 0, 'Off', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 1, 'Heat', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 2, 'Cool', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 3, 'On', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 4, 'HeatCool', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 5, 'Off', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 6, 'Off', '', -1);
            IPS_SetVariableProfileAssociation('ThermostatMode.GA', 7, 'Off', '', -1);
        }

        //Each accessory is allowed to register properties for persistent data
        $this->registry->registerProperties();

        $this->RegisterPropertyBoolean('EmulateStatus', false);
        $this->RegisterPropertyBoolean('ShowExpertDevices', false);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        // We need to check for IDs that are empty and assign a proper ID
        $this->registry->updateProperties();

        $objectIDs = $this->registry->getObjectIDs();

        if (method_exists($this, 'GetReferenceList')) {
            $refs = $this->GetReferenceList();
            foreach ($refs as $ref) {
                $this->UnregisterReference($ref);
            }

            foreach ($objectIDs as $id) {
                $this->RegisterReference($id);
            }
        }
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
        $result = $this->registry->doDirective($directive['endpoint']['endpointId'], $directive['header']['name'], $payload);

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

    public function GetConfigurationForm()
    {
        //Check Connect availability
        $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');
        if (IPS_GetInstance($ids[0])['InstanceStatus'] != 102) {
            $message = 'Error: Symcon Connect is not active!';
        } else {
            $message = 'Status: Symcon Connect is OK!';
        }

        // Translations are just added in the registry
        $connect = [
            [
                'type'    => 'Label',
                'caption' => $message
            ]
        ];

        $deviceTypes = $this->registry->getConfigurationForm();

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

        return json_encode(['elements'     => array_merge($connect, $deviceTypes, $expertMode),
            'translations'                 => $this->registry->getTranslations()]);
    }

    public function UIUpdateExpertVisibility(bool $ShowExpertDevices)
    {
        foreach ($this->registry->getExpertPanelNames() as $panelName) {
            $this->UpdateFormField($panelName, 'visible', $ShowExpertDevices);
        }
    }
}
