<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class DirectiveTest extends TestCase
{
    private $alexaModuleID = '{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    public function setUp()
    {
        //Reset
        IPS\Kernel::reset();

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        parent::setUp();
    }

    public function testVariableResponseData()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $vid = IPS_CreateVariable(0 /* Boolean */);
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
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;
        $response = $intf->SimulateData(json_decode($testRequest, true));

        $this->assertArrayHasKey('context', $response);
        $this->assertArrayHasKey('properties', $response['context']);
        $this->assertArrayHasKey(0, $response['context']['properties']);
        $this->assertArrayHasKey('timeOfSample', $response['context']['properties'][0]);

        $dateTime = DateTime::createFromFormat(DateTime::ISO8601, $response['context']['properties'][0]['timeOfSample']);
        if ($dateTime) {
            $this->assertEquals($dateTime->format(self::DATE_TIME_FORMAT), $response['context']['properties'][0]['timeOfSample']);
        } else {
            $this->assertTrue(false);
        }

        $this->assertArrayHasKey('event', $response);
        $this->assertArrayHasKey('header', $response['event']);
        $this->assertArrayHasKey('messageId', $response['event']['header']);

        $this->assertRegExp('/(\w{8}(-\w{4}){3}-\w{12}?)/', $response['event']['header']['messageId']);
    }

    public function testEmulateStatus()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, '');

        $vid = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid, $sid);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceLightSwitch' => json_encode([
                [
                    'ID'                => '1',
                    'Name'              => 'Flur Licht',
                    'PowerControllerID' => $vid
                ]
            ]),
            'EmulateStatus' => false
        ]));
        IPS_ApplyChanges($iid);

        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Alexa);

        // No Emulate Status => Old value of the variable is returned
        $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

        $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

        IPS_SetProperty($iid, 'EmulateStatus', true);
        IPS_ApplyChanges($iid);

        // Emulate Status => Device confirms 'ON' despite variable still being off
        $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

        $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
    }

    public function testLightDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
            IPS_SetVariableCustomAction($vid, $sid);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceLightSwitch' => json_encode([
                    [
                        'ID'                => '1',
                        'Name'              => 'Flur Licht',
                        'PowerControllerID' => $vid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOff",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(true);
        $testFunction(false);
    }

    public function testLightDimmerDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_CreateVariableProfile('test', 2);
            IPS_SetVariableProfileValues('test', -100, 300, 5);

            IPS_SetVariableCustomProfile($vid, 'test');

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceLightDimmer' => json_encode([
                    [
                        'ID'                     => '1',
                        'Name'                   => 'Flur Licht',
                        'BrightnessControllerID' => $vid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 100,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        }]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOff",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "SetBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightness": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 42,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "AdjustBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightnessDelta": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 84,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightColorDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(1 /* Integer */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_CreateVariableProfile('test', 1);
            IPS_SetVariableProfileValues('test', 0, 0xFFFFFF, 1);

            IPS_SetVariableCustomProfile($vid, 'test');

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceLightColor' => json_encode([
                    [
                        'ID'                => '1',
                        'Name'              => 'Flur Licht',
                        'ColorControllerID' => $vid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 100,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0xFF0000, GetValue($vid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 120,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 100,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 120,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0x00FF00, GetValue($vid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 240,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 100,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 240,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0x0000FF, GetValue($vid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 270,
                "saturation": 0.5,
                "brightness": 0.5
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 50.0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 270,
                "saturation": 0.5,
                "brightness": 0.5
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0x604080, GetValue($vid));

            if (isset($result['context']['properties'][2]['value']['brightness'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][2]['value']['brightness'] = intval($result['context']['properties'][2]['value']['brightness'] * 100) * 0.01;
            } else {
                $this->assertTrue(false);
            }

            if (isset($result['context']['properties'][1]['value'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][1]['value'] = intval($result['context']['properties'][1]['value']);
            } else {
                $this->assertTrue(false);
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 100,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0xFF0000, GetValue($vid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "SetBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightness": 50.2
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 50.0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 0,
                "saturation": 1,
                "brightness": 0.5
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0x800000, GetValue($vid));

            if (isset($result['context']['properties'][2]['value']['brightness'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][2]['value']['brightness'] = intval($result['context']['properties'][2]['value']['brightness'] * 100) * 0.01;
            } else {
                $this->assertTrue(false);
            }

            if (isset($result['context']['properties'][1]['value'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][1]['value'] = intval($result['context']['properties'][1]['value']);
            } else {
                $this->assertTrue(false);
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "AdjustBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightnessDelta": 20.0
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 69.0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 0,
                "saturation": 1,
                "brightness": 0.69
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0xB20000, GetValue($vid));

            if (isset($result['context']['properties'][2]['value']['brightness'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][2]['value']['brightness'] = intval($result['context']['properties'][2]['value']['brightness'] * 100) * 0.01;
            } else {
                $this->assertTrue(false);
            }

            if (isset($result['context']['properties'][1]['value'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][1]['value'] = intval($result['context']['properties'][1]['value']);
            } else {
                $this->assertTrue(false);
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            // Reproducing error in rgbToHSB conversion
            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 357,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 100,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 356,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0xFF000D, GetValue($vid));

            if (isset($result['context']['properties'][2]['value']['hue'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][2]['value']['hue'] = intval($result['context']['properties'][2]['value']['hue']);
            } else {
                $this->assertTrue(false);
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertPowerDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
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
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOff",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertPowerBrightnessDimmerDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
            $bvid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($bvid, $sid);

            IPS_CreateVariableProfile('test', 2);
            IPS_SetVariableProfileValues('test', -100, 300, 5);

            IPS_SetVariableCustomProfile($bvid, 'test');

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
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        }]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "SetBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightness": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 42,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOff",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 42,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "AdjustBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightnessDelta": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 84,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertPowerBrightnessColorDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
            $bvid = IPS_CreateVariable(2 /* Float */);
            $cvid = IPS_CreateVariable(1 /* Integer */);
            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($bvid, $sid);
            IPS_SetVariableCustomAction($cvid, $sid);

            IPS_CreateVariableProfile('test', 2);
            IPS_SetVariableProfileValues('test', -100, 300, 5);

            IPS_SetVariableCustomProfile($bvid, 'test');

            IPS_CreateVariableProfile('testC', 1);
            IPS_SetVariableProfileValues('testC', 0, 0xFFFFFF, 1);

            IPS_SetVariableCustomProfile($cvid, 'testC');

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
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0xFF0000, GetValue($cvid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 120,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 120,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0x00FF00, GetValue($cvid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 240,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 240,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0x0000FF, GetValue($cvid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 270,
                "saturation": 0.5,
                "brightness": 0.5
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 270,
                "saturation": 0.5,
                "brightness": 0.5
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0x604080, GetValue($cvid));

            if (isset($result['context']['properties'][0]['value']['brightness'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][0]['value']['brightness'] = intval($result['context']['properties'][0]['value']['brightness'] * 100) * 0.01;
            } else {
                $this->assertTrue(false);
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ColorController",
            "name": "SetColor",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "color": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            $this->assertEquals(0xFF0000, GetValue($cvid));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "SetBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightness": 50
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 50.0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.BrightnessController",
            "name": "AdjustBrightness",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "brightnessDelta": 20.0
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 70.0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            $result = $intf->SimulateData(json_decode($testRequest, true));

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($result)), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.BrightnessController",
            "name": "brightness",
            "value": 70.0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.ColorController",
            "name": "color",
            "value": {
                "hue": 0,
                "saturation": 1,
                "brightness": 1
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testThermostatDirectives()
    {
        $testFunction = function ($emulateStatus, $scale, $scaleToCelsiusFunction) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceThermostat' => json_encode([
                    [
                        'ID'                     => '1',
                        'Name'                   => 'Flur Licht',
                        'ThermostatControllerID' => $vid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<EOT
{
    "directive": {
        "header": {
            "namespace": "Alexa.ThermostatController",
            "name": "SetTargetTemperature",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "targetSetpoint": {
                "value": 42,
                "scale": "$scale"
            }
        }
    }
}           
EOT;

            $testResponse = <<<EOT
{
    "context": {
        "properties": [
        {
            "namespace": "Alexa.ThermostatController",
            "name": "targetSetpoint",
            "value": {
                "value": {$scaleToCelsiusFunction(42)},
                "scale": "CELSIUS"            
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<EOT
{
    "directive": {
        "header": {
            "namespace": "Alexa.ThermostatController",
            "name": "AdjustTargetTemperature",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "targetSetpointDelta": {
                "value": -21,
                "scale": "$scale"            
            }
        }
    }
}           
EOT;

            $testResponse = <<<EOT
{
    "context": {
        "properties": [
        {
            "namespace": "Alexa.ThermostatController",
            "name": "targetSetpoint",
            "value": {
                "value": {$scaleToCelsiusFunction(21)},
                "scale": "CELSIUS"            
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $celsiusToCelsius = function ($value) {
            return $value;
        };

        $fahrenheitToCelsius = function ($value) {
            return ($value - 32) * 5 / 9;
        };

        $kelvinToCelsius = function ($value) {
            return $value - 273.15;
        };

        $testFunction(false, 'CELSIUS', $celsiusToCelsius);
        $testFunction(true, 'CELSIUS', $celsiusToCelsius);
        $testFunction(false, 'FAHRENHEIT', $fahrenheitToCelsius);
        $testFunction(true, 'FAHRENHEIT', $fahrenheitToCelsius);
        $testFunction(false, 'KELVIN', $kelvinToCelsius);
        $testFunction(true, 'KELVIN', $kelvinToCelsius);
    }

    public function testSimpleScenesDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $vid = IPS_CreateVariable(1 /* Integer */);

            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, "SetValue($vid, 42); return true;");

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceSimpleScene' => json_encode([
                    [
                        'ID'                      => '1',
                        'Name'                    => 'Meine Szene',
                        'SceneControllerSimpleID' => $sid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.SceneController",
            "name": "Activate",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": []
    },
    "event": {
        "header": {
            "namespace": "Alexa.SceneController",
            "name": "ActivationStarted",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {
            "cause": {
                "type": "VOICE_INTERACTION"
            },
            "timestamp": ""
        }
    }
}
EOT;

            $actualResponse = $this->clearResponse($intf->SimulateData(json_decode($testRequest, true)));
            if (isset($actualResponse['event']['payload']['timestamp'])) {
                $dateTime = DateTime::createFromFormat(DateTime::ISO8601, $actualResponse['event']['payload']['timestamp']);
                if ($dateTime) {
                    $this->assertEquals($dateTime->format(self::DATE_TIME_FORMAT), $actualResponse['event']['payload']['timestamp']);
                } else {
                    $this->assertTrue(false);
                }

                $actualResponse['event']['payload']['timestamp'] = '';
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), $actualResponse);

            $this->assertEquals(42, GetValue($vid));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testDeactivatableScenesDirectives()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);

        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, "SetValue($vid, \$_IPS[\"VALUE\"]); return true;");

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
            "namespace": "Alexa.SceneController",
            "name": "Activate",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

        $testResponse = <<<'EOT'
{
    "context": {
        "properties": []
    },
    "event": {
        "header": {
            "namespace": "Alexa.SceneController",
            "name": "ActivationStarted",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {
            "cause": {
                "type": "VOICE_INTERACTION"
            },
            "timestamp": ""
        }
    }
}
EOT;

        $actualResponse = $this->clearResponse($intf->SimulateData(json_decode($testRequest, true)));
        if (isset($actualResponse['event']['payload']['timestamp'])) {
            $dateTime = DateTime::createFromFormat(DateTime::ISO8601, $actualResponse['event']['payload']['timestamp']);
            if ($dateTime) {
                $this->assertEquals($dateTime->format(self::DATE_TIME_FORMAT), $actualResponse['event']['payload']['timestamp']);
            } else {
                $this->assertTrue(false);
            }

            $actualResponse['event']['payload']['timestamp'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), $actualResponse);

        $this->assertEquals(true, GetValue($vid));

        $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.SceneController",
            "name": "Deactivate",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

        $testResponse = <<<'EOT'
{
    "context": {
        "properties": []
    },
    "event": {
        "header": {
            "namespace": "Alexa.SceneController",
            "name": "DeactivationStarted",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {
            "cause": {
                "type": "VOICE_INTERACTION"
            },
            "timestamp": ""
        }
    }
}
EOT;

        $actualResponse = $this->clearResponse($intf->SimulateData(json_decode($testRequest, true)));
        if (isset($actualResponse['event']['payload']['timestamp'])) {
            $dateTime = DateTime::createFromFormat(DateTime::ISO8601, $actualResponse['event']['payload']['timestamp']);
            if ($dateTime) {
                $this->assertEquals($dateTime->format(self::DATE_TIME_FORMAT), $actualResponse['event']['payload']['timestamp']);
            } else {
                $this->assertTrue(false);
            }

            $actualResponse['event']['payload']['timestamp'] = '';
        }

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), $actualResponse);

        $this->assertEquals(false, GetValue($vid));
    }

    public function testGenericSwitchDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
            IPS_SetVariableCustomAction($vid, $sid);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceGenericSwitch' => json_encode([
                    [
                        'ID'                => '1',
                        'Name'              => 'Flur Gerät',
                        'PowerControllerID' => $vid
                    ]
                ]),
                $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOff",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testGenericSliderDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_CreateVariableProfile('test', 2);
            IPS_SetVariableProfileValues('test', -100, 300, 5);

            IPS_SetVariableCustomProfile($vid, 'test');

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceGenericSlider' => json_encode([
                    [
                        'ID'                     => '1',
                        'Name'                   => 'Flur Gerät',
                        'PercentageControllerID' => $vid
                    ]
                ]),
                'EmulateStatus' => $emulateStatus
            ]));
            IPS_ApplyChanges($iid);

            $intf = IPS\InstanceManager::getInstanceInterface($iid);
            $this->assertTrue($intf instanceof Alexa);

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOn",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.PercentageController",
            "name": "percentage",
            "value": 100,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        }]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PowerController",
            "name": "TurnOff",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.PercentageController",
            "name": "percentage",
            "value": 0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "OFF",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.PercentageController",
            "name": "percentage",
            "value": 0,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PercentageController",
            "name": "SetPercentage",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "percentage": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.PercentageController",
            "name": "percentage",
            "value": 42,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.PercentageController",
            "name": "AdjustPercentage",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "percentageDelta": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.PowerController",
            "name": "powerState",
            "value": "ON",
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.PercentageController",
            "name": "percentage",
            "value": 84,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testSpeakerDirectives()
    {
        $testFunction = function ($emulateStatus) {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_CreateVariableProfile('test', 2);
            IPS_SetVariableProfileValues('test', -100, 300, 5);

            IPS_SetVariableCustomProfile($vid, 'test');

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
            "namespace": "Alexa",
            "name": "ReportState",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {}
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.Speaker",
            "name": "volume",
            "value": 25,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "StateReport",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.Speaker",
            "name": "SetVolume",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "volume": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.Speaker",
            "name": "volume",
            "value": 42,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.Speaker",
            "name": "AdjustVolume",
            "payloadVersion": "3",
            "messageId": "1bd5d003-31b9-476f-ad03-71d471922820",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "scope": {
                "type": "BearerToken",
                "token": "access-token-from-skill"
            },
            "endpointId": "1",
            "cookie": {}
        },
        "payload": {
            "volume": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.Speaker",
            "name": "volume",
            "value": 84,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        } ]
    },
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "Response",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {}
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
        };

        $testFunction(false);
        $testFunction(true);
    }

    private function clearResponse($response)
    {
        if (isset($response['event']['header']['messageId'])) {
            $response['event']['header']['messageId'] = '';
        }

        // Clear timeOfSample as well
        if (isset($response['context']['properties'])) {
            for ($i = 0; $i < count($response['context']['properties']); $i++) {
                if (isset($response['context']['properties'][$i]['timeOfSample'])) {
                    $response['context']['properties'][$i]['timeOfSample'] = '';
                }
            }
        }

        return $response;
    }
}
