<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/autoload.php';

use PHPUnit\Framework\TestCase;

class DiscoveryTest extends TestCase
{
    private $alexaModuleID = '{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}';
    private $connectControlID = '{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}';
    private $agentUserId = '';

    public function setUp(): void
    {

        if (defined('IPS_VERSION') && IPS_VERSION < 8.0) {
            $this->markTestSkipped('This test is for versions >= 8.0');
        }
        //Licensee is used as agentUserId
        $this->agentUserId = md5(IPS_GetLicensee());

        //Reset
        IPS\Kernel::reset();

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/stubs/CoreStubs/library.json');

        // Create a Connect Control
        IPS_CreateInstance($this->connectControlID);

        //Load required actions
        IPS\ActionPool::loadActions(__DIR__ . '/actions');

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

    public function testLightSwitchBrokenDiscovery()
    {
        // The variable has no action and should thus not be found
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

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($response), true));
    }

    public function testLightSwitchDiscovery()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

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
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100]);

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
        $vid = IPS_CreateVariable(VARIABLETYPE_STRING);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_COLOR, 'ENCODING' => 0 /* RGB */]);

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
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

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
        $vid = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        $bvid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);
        IPS_SetVariableCustomAction($bvid, $sid);

        IPS_SetVariableCustomPresentation($bvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100]);

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
        $vid = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        $cvid = IPS_CreateVariable(VARIABLETYPE_STRING);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);
        IPS_SetVariableCustomAction($cvid, $sid);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetVariableCustomPresentation($cvid, ['PRESENTATION' => VARIABLE_PRESENTATION_COLOR, 'ENCODING' => 0 /* RGB */]);

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

    public function testLightExpertPowerColorTemperatureDiscovery()
    {
        $vid = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        $cvid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);
        IPS_SetVariableCustomAction($cvid, $sid);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetVariableCustomPresentation($cvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'USAGE_TYPE' => 1 /* Tunable White */]);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightExpert' => json_encode([
                [
                    'ID'                               => '1',
                    'Name'                             => 'Flur Licht',
                    'PowerControllerID'                => $vid,
                    'BrightnessOnlyControllerID'       => 0,
                    'ColorOnlyControllerID'            => 0,
                    'ColorTemperatureOnlyControllerID' => $cvid
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
                            "interface": "Alexa.ColorTemperatureController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "colorTemperatureInKelvin"
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

    public function testLightExpertPowerBrightnessColorColorTemperatureDiscovery()
    {
        $vid = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        $bvid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $cvid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $ctvid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);
        IPS_SetVariableCustomAction($bvid, $sid);
        IPS_SetVariableCustomAction($cvid, $sid);
        IPS_SetVariableCustomAction($ctvid, $sid);

        IPS_SetVariableCustomPresentation($bvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100]);
        IPS_SetVariableCustomPresentation($cvid, ['PRESENTATION' => VARIABLE_PRESENTATION_COLOR, 'ENCODING' => 0 /* RGB */]);
        IPS_SetVariableCustomPresentation($ctvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'USAGE_TYPE' => 1 /* Tunable White */]);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightExpert' => json_encode([
                [
                    'ID'                               => '1',
                    'Name'                             => 'Flur Licht',
                    'PowerControllerID'                => $vid,
                    'BrightnessOnlyControllerID'       => $bvid,
                    'ColorOnlyControllerID'            => $cvid,
                    'ColorTemperatureOnlyControllerID' => $ctvid
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
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.ColorTemperatureController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "colorTemperatureInKelvin"
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
        $vid = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        $bvid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $cvid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);
        IPS_SetVariableCustomAction($bvid, $sid);
        IPS_SetVariableCustomAction($cvid, $sid);

        IPS_SetVariableCustomPresentation($bvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100]);
        IPS_SetVariableCustomPresentation($cvid, ['PRESENTATION' => VARIABLE_PRESENTATION_COLOR, 'ENCODING' => 0 /* RGB */]);

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
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

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
        $vid = IPS_CreateVariable(VARIABLETYPE_FLOAT);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100]);

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
        $vid = IPS_CreateVariable(VARIABLETYPE_FLOAT);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100, 'USAGE_TYPE' => 2 /* Volume */]);

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

    public function testMutableSpeakerDiscovery()
    {
        $vid = IPS_CreateVariable(2 /* Float */);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

        $muteID = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($muteID, $sid);

        IPS_CreateVariableProfile('Dimmer', 2);
        IPS_SetVariableProfileValues('Dimmer', 0, 100, 1);
        IPS_SetVariableCustomProfile($vid, 'Dimmer');

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceSpeakerMuteable' => json_encode([
                [
                    'ID'                      => '1',
                    'Name'                    => 'Flur Lautsprecher',
                    'SpeakerMuteableVolumeID' => $vid,
                    'SpeakerMuteableMuteID'   => $muteID
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
                    "description": "Speaker (Muteable) by IP-Symcon",
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
                                },
                                {
                                    "name": "mute"
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

    public function testTelevisionDiscovery()
    {
        $sid = IPS_CreateScript(0 /* PHP */);

        $powerID = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        IPS_SetVariableCustomAction($powerID, $sid);

        $channelID = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        IPS_SetVariableCustomAction($channelID, $sid);

        IPS_SetVariableCustomPresentation($channelID, ['PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION, 'OPTIONS' => json_encode([['Value' => 0, 'Caption' => 'ARD'], ['Value' => 1, 'Caption' => 'ZDF'], ['Value' => 2, 'Caption' => 'NDR3']])]);

        $vid = IPS_CreateVariable(VARIABLETYPE_FLOAT);
        IPS_SetVariableCustomAction($vid, $sid);

        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300]);

        $muteID = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
        IPS_SetVariableCustomAction($muteID, $sid);

        $inputID = IPS_CreateVariable(VARIABLETYPE_STRING);
        IPS_SetVariableCustomAction($inputID, $sid);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceTelevision' => json_encode([
                [
                    'ID'                       => '1',
                    'Name'                     => 'Flur Fernseher',
                    'PowerControllerID'        => $powerID,
                    'ChannelControllerID'      => $channelID,
                    'SpeakerMuteableVolumeID'  => $vid,
                    'SpeakerMuteableMuteID'    => $muteID,
                    'InputControllerID'        => $inputID,
                    'InputControllerSupported' => [
                        [
                            'selected' => true
                        ],
                        [
                            'selected' => false
                        ],
                        [
                            'selected' => true
                        ]
                    ]
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
                    "friendlyName": "Flur Fernseher",
                    "description": "Television by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "TV"
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
                            "interface": "Alexa.ChannelController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "channel"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.Speaker",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "volume"
                                },
                                {
                                    "name": "mute"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            }
                        },
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.InputController",
                            "version": "3",
                            "properties": {
                                "supported": [{
                                    "name": "input"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true
                            },
                            "inputs": [
                                {
                                    "name": "AUX 1"
                                },
                                {
                                    "name": "AUX 3"
                                }
                            ]
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

    public function testShutterDiscoveryPercentage()
    {
        $vid = IPS_CreateVariable(VARIABLETYPE_FLOAT);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100]);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceShutter' => json_encode([
                [
                    'ID'                       => '1',
                    'Name'                     => 'Flur Rollladen',
                    'RangeControllerShutterID' => $vid
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
                    "friendlyName": "Flur Rollladen",
                    "description": "Shutter by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "EXTERIOR_BLIND"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.RangeController",
                            "version": "3",
                            "instance": "Shutter.Position",
                            "properties": {
                                "supported": [{
                                    "name": "rangeValue"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true,
                                "noControllable": false
                            },
                            "capabilityResources": {
                              "friendlyNames": [
                                {
                                  "@type": "asset",
                                  "value": {
                                    "assetId": "Alexa.Setting.Opening"
                                  }
                                }
                              ]
                            },
                            "configuration": {
                              "supportedRange": {
                                "minimumValue": 0,
                                "maximumValue": 100,
                                "precision": 1
                              },
                              "unitOfMeasure": "Alexa.Unit.Percent"
                            },
                            "semantics": {
                              "actionMappings": [
                                {
                                  "@type": "ActionsToDirective",
                                  "actions": ["Alexa.Actions.Close"],
                                  "directive": {
                                    "name": "SetRangeValue",
                                    "payload": {
                                      "rangeValue": 0
                                    }
                                  }
                                },
                                {
                                  "@type": "ActionsToDirective",
                                  "actions": ["Alexa.Actions.Open"],
                                  "directive": {
                                    "name": "SetRangeValue",
                                    "payload": {
                                      "rangeValue": 100
                                    }
                                  }
                                },
                                {
                                  "@type": "ActionsToDirective",
                                  "actions": ["Alexa.Actions.Raise"],
                                  "directive": {
                                    "name": "AdjustRangeValue",
                                    "payload": {
                                      "rangeValueDelta" : 25,
                                      "rangeValueDeltaDefault" : false
                                    }
                                  }
                                },
                                {
                                  "@type": "ActionsToDirective",
                                  "actions": ["Alexa.Actions.Lower"],
                                  "directive": {
                                    "name": "AdjustRangeValue",
                                    "payload": {
                                      "rangeValueDelta" : -25,
                                      "rangeValueDeltaDefault" : false
                                    }
                                  }
                                }
                              ],
                              "stateMappings": [
                                {
                                  "@type": "StatesToValue",
                                  "states": ["Alexa.States.Closed"],
                                  "value": 0
                                },
                                {
                                  "@type": "StatesToRange",
                                  "states": ["Alexa.States.Open"],
                                  "range": {
                                    "minimumValue": 1,
                                    "maximumValue": 100
                                  }
                                }  
                              ]
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

    public function testShutterDiscovery()
    {
        $vid = IPS_CreateVariable(VARIABLETYPE_INTEGER);
        $sid = IPS_CreateScript(0);
        IPS_SetVariableCustomAction($vid, $sid);

        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_LEGACY, 'PROFILE' => '~ShutterMoveStep']);
        // IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100]);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceShutter' => json_encode([
                [
                    'ID'                       => '1',
                    'Name'                     => 'Flur Rollladen',
                    'RangeControllerShutterID' => $vid
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
                    "friendlyName": "Flur Rollladen",
                    "description": "Shutter by IP-Symcon",
                    "manufacturerName": "Symcon GmbH",
                    "displayCategories": [
                        "EXTERIOR_BLIND"
                    ],
                    "cookie": {},
                    "capabilities": [
                        {
                            "type": "AlexaInterface",
                            "interface": "Alexa.RangeController",
                            "version": "3",
                            "instance": "Shutter.Position",
                            "properties": {
                                "supported": [{
                                    "name": "rangeValue"
                                }],
                                "proactivelyReported": false,
                                "retrievable": true,
                                "noControllable": false
                            },
                            "capabilityResources": {
                              "friendlyNames": [
                                {
                                  "@type": "asset",
                                  "value": {
                                    "assetId": "Alexa.Setting.Opening"
                                  }
                                }
                              ]
                            },
                            "configuration": {
                              "supportedRange": {
                                "minimumValue": 0,
                                "maximumValue": 100,
                                "precision": 100
                              },
                              "unitOfMeasure": "Alexa.Unit.Percent"
                            },
                            "semantics": {
                              "actionMappings": [
                                {
                                  "@type": "ActionsToDirective",
                                  "actions": ["Alexa.Actions.Close", "Alexa.Actions.Lower"],
                                  "directive": {
                                    "name": "SetRangeValue",
                                    "payload": {
                                      "rangeValue": 0
                                    }
                                  }
                                },
                                {
                                  "@type": "ActionsToDirective",
                                  "actions": ["Alexa.Actions.Open", "Alexa.Actions.Raise"],
                                  "directive": {
                                    "name": "SetRangeValue",
                                    "payload": {
                                      "rangeValue": 100
                                    }
                                  }
                                }
                              ],
                              "stateMappings": [
                                {
                                  "@type": "StatesToValue",
                                  "states": ["Alexa.States.Closed"],
                                  "value": 0
                                },
                                {
                                  "@type": "StatesToRange",
                                  "states": ["Alexa.States.Open"],
                                  "range": {
                                    "minimumValue": 1,
                                    "maximumValue": 100
                                  }
                                }  
                              ]
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
