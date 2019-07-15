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

    public function setUp(): void
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

        $testResponse = <<<'EOT'
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
            $response['event']['header']['messageId'] = '';
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
                    'ID'                => '1',
                    'Name'              => 'Flur Licht',
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

        $testResponse = <<<'EOT'
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
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testLightDimmerDiscovery()
    {
        $vid = IPS_CreateVariable(2 /* Float */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightDimmer' => json_encode([
                [
                    'ID'                     => '1',
                    'Name'                   => 'Flur Licht',
                    'BrightnessControllerID' => $vid
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

        $testResponse = <<<'EOT'
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
                    "description": "Light (Dimmer) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "LIGHT"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.BrightnessController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "brightness"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        },
                        {
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
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testSimpleSceneDiscovery()
    {
        $sid = IPS_CreateScript(0);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceSimpleScene' => json_encode([
                [
                    'ID'                      => '1',
                    'Name'                    => 'Meine Szene',
                    'SceneControllerSimpleID' => $sid
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

        $testResponse = <<<'EOT'
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
                    "friendlyName": "Meine Szene",
                    "description": "Scenes by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "SCENE_TRIGGER"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.SceneController",
                            "version": "3",
                            "supportsDeactivation": false,
                            "proactivelyReported": false
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testLightColorDiscovery()
    {
        $vid = IPS_CreateVariable(1 /* Integer */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightColor' => json_encode([
                [
                    'ID'                => '1',
                    'Name'              => 'Flur Licht',
                    'ColorControllerID' => $vid
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

        $testResponse = <<<'EOT'
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
                    "description": "Light (Color) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "LIGHT"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.ColorController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "color"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.BrightnessController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "brightness"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        },
                        {
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
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testLightExpertPowerDiscovery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightExpert' => json_encode([
                [
                    'ID'                         => '1',
                    'Name'                       => 'Flur Licht',
                    'PowerControllerID'          => $vid,
                    'BrightnessOnlyControllerID' => 0,
                    'ColorOnlyControllerID'      => 0
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

        $testResponse = <<<'EOT'
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
                    "description": "Light (Expert) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "LIGHT"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
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
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testLightExpertPowerBrightnessDiscovery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);
        $bvid = IPS_CreateVariable(1 /*Integer */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightExpert' => json_encode([
                [
                    'ID'                         => '1',
                    'Name'                       => 'Flur Licht',
                    'PowerControllerID'          => $vid,
                    'BrightnessOnlyControllerID' => $bvid,
                    'ColorOnlyControllerID'      => 0
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

        $testResponse = <<<'EOT'
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
                    "description": "Light (Expert) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "LIGHT"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
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
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.BrightnessController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "brightness"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testLightExpertPowerColorDiscovery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);
        $cvid = IPS_CreateVariable(1 /* Integer */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetVariableCustomProfile($cvid, '~HexColor');

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightExpert' => json_encode([
                [
                    'ID'                         => '1',
                    'Name'                       => 'Flur Licht',
                    'PowerControllerID'          => $vid,
                    'BrightnessOnlyControllerID' => 0,
                    'ColorOnlyControllerID'      => $cvid
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

        $testResponse = <<<'EOT'
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
                    "description": "Light (Expert) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "LIGHT"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
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
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.ColorController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "color"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testLightExpertPowerBrightnessColorDiscovery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);
        $bvid = IPS_CreateVariable(1 /* Integer */);
        $cvid = IPS_CreateVariable(1 /* Integer */);

        IPS_SetVariableCustomProfile($cvid, '~HexColor');

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightExpert' => json_encode([
                [
                    'ID'                         => '1',
                    'Name'                       => 'Flur Licht',
                    'PowerControllerID'          => $vid,
                    'BrightnessOnlyControllerID' => $bvid,
                    'ColorOnlyControllerID'      => $cvid
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

        $testResponse = <<<'EOT'
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
                    "description": "Light (Expert) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "LIGHT"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
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
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.BrightnessController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "brightness"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.ColorController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "color"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testDeactivatableSceneDiscovery()
    {
        $sid = IPS_CreateScript(0);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceDeactivatableScene' => json_encode([
                [
                    'ID'                                       => '1',
                    'Name'                                     => 'Meine Szene',
                    'SceneControllerDeactivatableActivateID'   => $sid,
                    'SceneControllerDeactivatableDeactivateID' => $sid
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

        $testResponse = <<<'EOT'
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
                    "friendlyName": "Meine Szene",
                    "description": "Scenes (Deactivatable) by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "SCENE_TRIGGER"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.SceneController",
                            "version": "3",
                            "supportsDeactivation": true,
                            "proactivelyReported": false
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testGenericSwitchDiscovery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceGenericSwitch' => json_encode([
                [
                    'ID'                => '1',
                    'Name'              => 'Flur Gerät',
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

        $testResponse = <<<'EOT'
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
                    "friendlyName": "Flur Gerät",
                    "description": "Generic Switch by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "SWITCH"
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
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testGenericSliderDiscovery()
    {
        $vid = IPS_CreateVariable(2 /* Float */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceGenericSlider' => json_encode([
                [
                    'ID'                     => '1',
                    'Name'                   => 'Flur Gerät',
                    'PercentageControllerID' => $vid
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

        $testResponse = <<<'EOT'
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
                    "friendlyName": "Flur Gerät",
                    "description": "Generic Slider by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "SWITCH"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.PercentageController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "percentage"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        },
                        {
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
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testSpeakerDiscovery()
    {
        $vid = IPS_CreateVariable(2 /* Float */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceSpeaker' => json_encode([
                [
                    'ID'        => '1',
                    'Name'      => 'Flur Lautsprecher',
                    'SpeakerID' => $vid
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

        $testResponse = <<<'EOT'
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
                    "friendlyName": "Flur Lautsprecher",
                    "description": "Speaker by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "SPEAKER"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.Speaker",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "volume"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        }
                    ]
                }
            ]
        }
    }
}
EOT;

        // Since a new and random messageID is generated every time, we clear the messageId
        $response = $intf->SimulateData(json_decode($testRequest, true));
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }
}
