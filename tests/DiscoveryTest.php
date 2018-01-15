<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class DiscoveryTest extends TestCase
{
    private $alexaModuleID = '{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}';
    private $agentUserId = '';

    public function setUp()
    {
        //Licensee is used as agentUserId
        $this->agentUserId = md5(IPS_GetLicensee());

        //Reset
        IPS\Kernel::reset();

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        parent::setUp();
    }

    public function testEmptyDiscovery()
    {
        $iid = IPS_CreateInstance($this->alexaModuleID);
        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Alexa);

        $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.Discovery",
            "name": "Discover",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820"
        },
        "payload": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            }
        }
    }
}      
EOT;

        $testResponse = <<<EOT
{
    "event": {
        "header": {
            "namespace": "Alexa.Discovery",
            "name": "Discover.Response",
            "payloadVersion": "3",
            "messageId": ""
        },
        "payload": {
            "endpoints": []
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = "";
        }

        $this->assertEquals(json_decode($testResponse, true), $response);
    }

    public function testLightSwitchDiscovery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightSwitch' => json_encode([
                [
                    'ID'      => '1',
                    'Name'    => 'Flur Licht',
                    'PowerControllerID' => $vid
                ]
            ])
        ]));
        IPS_ApplyChanges($iid);

        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Alexa);

        $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.Discovery",
            "name": "Discover",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820"
        },
        "payload": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            }
        }
    }
}
EOT;

        $testResponse = <<<EOT
{
    "event": {
        "header": {
            "namespace": "Alexa.Discovery",
            "name": "Discover.Response",
            "payloadVersion": "3",
            "messageId": ""
        },
        "payload": {
            "endpoints": [
                {
                    "endpointId": "1",
                    "friendlyName": "Flur Licht",
                    "description": "Light (Switch) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "LIGHT"
                    ],
                    "cookie": {},
                    "capabilities": [{
                        "type": "AlexaInterface",
                        "interface": "Alexa.PowerController",
                        "version": "3",
                        "properties": {
                            "supported": [{
                                "name": "powerState"
                            }],
                            "proactivelyReported": false,
                            "retrievable": true
                        }
                    }]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = "";
        }

        $this->assertEquals(json_decode($testResponse, true), $response);
    }
}
