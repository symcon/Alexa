<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/autoload.php';

use PHPUnit\Framework\TestCase;

class DirectiveTest extends TestCase
{
    public const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';
    private $alexaModuleID = '{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}';
    private $connectControlID = '{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}';

    public function setUp(): void
    {

        if (defined('IPS_VERSION') && IPS_VERSION < 8.0) {
            $this->markTestSkipped('This test is for versions >= 8.0');
        }
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

    public function testVariableResponseData()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $vid = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid, $sid);
        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SWITCH]);

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

        $this->assertMatchesRegularExpression('/(\w{8}(-\w{4}){3}-\w{12}?)/', $response['event']['header']['messageId']);
    }

    public function testVariableResponseDataWithPresentation()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $vid = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid, $sid);
        IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SWITCH]);

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

        $this->assertMatchesRegularExpression('/(\w{8}(-\w{4}){3}-\w{12}?)/', $response['event']['header']['messageId']);
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
        $testFunction = function ($emulateStatus)
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
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);
            $presentations = json_decode(IPS_GetPresentations(), true);
            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300, 'STEP_SIZE' => 5]);

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
            $this->assertEquals(GetValue($vid), 300); // MAX
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
            $this->assertEquals(GetValue($vid), -100); // MIN
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
            $this->assertEquals(GetValue($vid), -100); // Brightness 0% -> MIN
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
            $this->assertEquals(GetValue($vid), 68); // Brightness 42% -> 68
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
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            // TODO: Add test case for int with color presentation
            $vid = IPS_CreateVariable(VARIABLETYPE_STRING);
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
                "hue":0,
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

            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($vid));
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

            $this->assertEquals(json_encode(['r' => 0, 'g' => 255, 'b' => 0]), GetValue($vid));

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

            $this->assertEquals(json_encode(['r' => 0, 'g' => 0, 'b' => 255]), GetValue($vid));

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

            $this->assertEquals(json_encode(['r' => 96, 'g' => 64, 'b' => 128]), GetValue($vid));

            if (isset($result['context']['properties'][2]['value']['brightness'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][2]['value']['brightness'] = round($result['context']['properties'][2]['value']['brightness'], 2);
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

            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($vid));

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

            $this->assertEquals(json_encode(['r' => 128, 'g' => 0, 'b' => 0]), GetValue($vid));

            if (isset($result['context']['properties'][2]['value']['brightness'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][2]['value']['brightness'] = round($result['context']['properties'][2]['value']['brightness'], 2);
            } else {
                $this->assertTrue(false);
            }

            if (isset($result['context']['properties'][1]['value'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][1]['value'] = round($result['context']['properties'][1]['value']);
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
                "brightness": 0.7
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

            $this->assertEquals(json_encode(['r' => 178, 'g' => 0, 'b' => 0]), GetValue($vid));

            if (isset($result['context']['properties'][2]['value']['brightness'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][2]['value']['brightness'] = round($result['context']['properties'][2]['value']['brightness'], 2);
            } else {
                $this->assertTrue(false);
            }

            if (isset($result['context']['properties'][1]['value'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][1]['value'] = round($result['context']['properties'][1]['value']);
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

            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 13]), GetValue($vid));

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
        $testFunction = function ($emulateStatus)
        {
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
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
            $bvid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($bvid, $sid);

            IPS_SetVariableCustomPresentation($bvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300, 'STEP' => 5]);

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
            $this->assertTrue(GetValue($vid));
            $this->assertEquals(GetValue($bvid), 0);

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

            $this->assertTrue(GetValue($vid));
            $this->assertEquals(GetValue($bvid), 68); // 42% -> 68

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

            $this->assertFalse(GetValue($vid));
            $this->assertEquals(GetValue($bvid), 68);

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

            $this->assertFalse(GetValue($vid));
            $this->assertEquals(GetValue($bvid), 68);

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

            $this->assertFalse(GetValue($vid));
            $this->assertEquals(GetValue($bvid), 236); // 84%
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertPowerBrightnessColorDirectives()
    {
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(VARIABLETYPE_BOOLEAN);
            $bvid = IPS_CreateVariable(VARIABLETYPE_FLOAT);
            $cvid = IPS_CreateVariable(VARIABLETYPE_STRING);
            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($bvid, $sid);
            IPS_SetVariableCustomAction($cvid, $sid);

            IPS_SetVariableCustomPresentation($bvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300]);

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

            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($cvid));
            $this->assertEquals(0, GetValue($bvid));
            $this->assertFalse(GetValue($vid));

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

            $this->assertEquals(json_encode(['r' => 0, 'g' => 255, 'b' => 0]), GetValue($cvid));
            $this->assertEquals(0, GetValue($bvid));
            $this->assertFalse(GetValue($vid));
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

            $this->assertEquals(json_encode(['r' => 0, 'g' => 0, 'b' => 255]), GetValue($cvid));
            $this->assertEquals(0, GetValue($bvid));
            $this->assertFalse(GetValue($vid));

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

            $this->assertEquals(json_encode(['r' => 96, 'g' => 64, 'b' => 128]), GetValue($cvid));
            $this->assertEquals(0, GetValue($bvid));
            $this->assertFalse(GetValue($vid));
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

            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($cvid));
            $this->assertEquals(0, GetValue($bvid));
            $this->assertFalse(GetValue($vid));

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
            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($cvid));
            $this->assertEquals(100, GetValue($bvid));
            $this->assertFalse(GetValue($vid));

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
            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($cvid));
            $this->assertEquals(180, GetValue($bvid));
            $this->assertFalse(GetValue($vid));

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

            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($cvid));
            $this->assertEquals(180, GetValue($bvid));
            $this->assertTrue(GetValue($vid));

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

            $this->assertEquals(json_encode(['r' => 255, 'g' => 0, 'b' => 0]), GetValue($cvid));
            $this->assertEquals(180, GetValue($bvid));
            $this->assertTrue(GetValue($vid));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testLightExpertPowerBrightnessColorColorTemperatureDirectives()
    {
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(0 /* Boolean */);
            $bvid = IPS_CreateVariable(2 /* Float */);
            $cvid = IPS_CreateVariable(1 /* Integer */);
            $ctvid = IPS_CreateVariable(1 /* Integer */);
            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomAction($bvid, $sid);
            IPS_SetVariableCustomAction($cvid, $sid);
            IPS_SetVariableCustomAction($ctvid, $sid);

            IPS_SetVariableCustomPresentation($bvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'DIGITS' => 2, 'MIN' => -100, 'MAX' => 300, 'USAGE_TYPE' => 2/* Intensity */]);
            IPS_SetVariableCustomPresentation($cvid, ['PRESENTATION' => VARIABLE_PRESENTATION_COLOR, 'ENCODING' => 0 /* RGB */]);
            IPS_SetVariableCustomPresentation($ctvid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'DIGITS' => 0, 'USAGE_TYPE' => 1 /* Tunable White */]);

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
                $result['context']['properties'][0]['value']['brightness'] = round($result['context']['properties'][0]['value']['brightness'], 2);
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
            "namespace": "Alexa.ColorTemperatureController",
            "name": "SetColorTemperature",
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
            "colorTemperatureInKelvin": 5000
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ColorTemperatureController",
            "name": "colorTemperatureInKelvin",
            "value": 5000,
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
            "namespace": "Alexa.ColorTemperatureController",
            "name": "IncreaseColorTemperature",
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
            "namespace": "Alexa.ColorTemperatureController",
            "name": "colorTemperatureInKelvin",
            "value": 8000,
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
            "namespace": "Alexa.ColorTemperatureController",
            "name": "DecreaseColorTemperature",
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
            "namespace": "Alexa.ColorTemperatureController",
            "name": "colorTemperatureInKelvin",
            "value": 5000,
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
        }, 
        {
            "namespace": "Alexa.ColorTemperatureController",
            "name": "colorTemperatureInKelvin",
            "value": 5000,
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
        $testFunction = function ($emulateStatus, $scale, $scaleToCelsiusFunction)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            // The type of presentation is not important. It is only checked if the variable has a presentation
            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'DIGITS' => 3]);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceThermostat' => json_encode([
                    [
                        'ID'                     => '1',
                        'Name'                   => 'Flur Thermostat',
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
        },
        {
            "namespace": "Alexa.ThermostatController",
            "name": "thermostatMode",
            "value": "HEAT",
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

            $result = json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true);
            if (isset($result['context']['properties'][0]['value']['value'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][0]['value']['value'] = round($result['context']['properties'][0]['value']['value'], 2);
            } else {
                $this->assertTrue(false);
            }

            $testDecoded = json_decode($testResponse, true);
            $testDecoded['context']['properties'][0]['value']['value'] = round($testDecoded['context']['properties'][0]['value']['value'], 2);

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals($testDecoded, $result);

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
        },
        {
            "namespace": "Alexa.ThermostatController",
            "name": "thermostatMode",
            "value": "HEAT",
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
            $result = json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true);
            $testDecoded = json_decode($testResponse, true);

            if (isset($result['context']['properties'][0]['value']['value'])) {
                //Turn brightness value to one point after comma to avoid different values due to rounding
                $result['context']['properties'][0]['value']['value'] = round($result['context']['properties'][0]['value']['value'], 2);
            } else {
                $this->assertTrue(false);
            }

            $testDecoded['context']['properties'][0]['value']['value'] = round($testDecoded['context']['properties'][0]['value']['value'], 2);
            $this->assertEquals($testDecoded, $result);
        };

        $celsiusToCelsius = function ($value)
        {
            return $value;
        };

        $fahrenheitToCelsius = function ($value)
        {
            return ($value - 32) * 5 / 9;
        };

        $kelvinToCelsius = function ($value)
        {
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
        $testFunction = function ($emulateStatus)
        {
            $vid = IPS_CreateVariable(1 /* Integer */);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceSimpleScene' => json_encode([
                    [
                        'ID'                          => '1',
                        'Name'                        => 'Meine Szene',
                        'SceneControllerSimpleAction' => json_encode([
                            'actionID'   => '{3644F802-C152-464A-868A-242C2A3DEC5C}',
                            'parameters' => [
                                'TARGET' => $vid,
                                'VALUE'  => 42
                            ]
                        ])
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
        $vid = IPS_CreateVariable(1 /* Integer */);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceDeactivatableScene' => json_encode([
                [
                    'ID'                                           => '1',
                    'Name'                                         => 'Meine Szene',
                    'SceneControllerDeactivatableActivateAction'   => json_encode([
                        'actionID'   => '{3644F802-C152-464A-868A-242C2A3DEC5C}',
                        'parameters' => [
                            'TARGET' => $vid,
                            'VALUE'  => 42
                        ]
                    ]),
                    'SceneControllerDeactivatableDeactivateAction' => json_encode([
                        'actionID'   => '{3644F802-C152-464A-868A-242C2A3DEC5C}',
                        'parameters' => [
                            'TARGET' => $vid,
                            'VALUE'  => 0
                        ]
                    ])
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

        $this->assertEquals(42, GetValue($vid));

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

        $this->assertEquals(0, GetValue($vid));
    }

    // Verify that scenes defined with ScriptID are converted and handled correctly
    public function testSimpleScenesDirectivesLegacyConversion()
    {
        $testFunction = function ($emulateStatus)
        {
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

    // Verify that scenes defined with ScriptID are converted and handled correctly
    public function testDeactivatableScenesDirectivesLegacyConversion()
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
        $testFunction = function ($emulateStatus)
        {
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
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);
            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300, 'STEP_SIZE' => 5]);

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

            $this->assertEquals(300, GetValue($vid));

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

            $this->assertEquals(-100, GetValue($vid));

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

            $this->assertEquals(-100, GetValue($vid));

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
            $this->assertEquals(68, GetValue($vid));

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
            $this->assertEquals(236, GetValue($vid));
        };

        $testFunction(false);
        $testFunction(true);
    }

    public function testSpeakerDirectives()
    {
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300, 'STEP_SIZE' => 5]);

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

    public function testMutableSpeakerDirectives()
    {
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300, 'STEP_SIZE' => 5]);

            $muteID = IPS_CreateVariable(0 /* Boolean */);
            IPS_SetVariableCustomAction($muteID, $sid);

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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
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
            "name": "SetMute",
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
            "mute": true
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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": true,
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
            "name": "SetMute",
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
            "mute": false
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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
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

    public function testTelevisionDirectives()
    {
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $powerID = IPS_CreateVariable(0 /* Boolean */);
            IPS_SetVariableCustomAction($powerID, $sid);

            $channelID = IPS_CreateVariable(1 /* Integer */);
            IPS_SetVariableCustomAction($channelID, $sid);

            IPS_SetVariableCustomPresentation($channelID, ['PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION, 'OPTIONS' => json_encode([['Value' => 0, 'Caption' => 'ARD'], ['Value' => 1, 'Caption' => 'ZDF'], ['Value' => 2, 'Caption' => 'NDR3']])]);

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300]);

            $muteID = IPS_CreateVariable(0 /* Boolean */);
            IPS_SetVariableCustomAction($muteID, $sid);

            $inputID = IPS_CreateVariable(3 /* String */);
            IPS_SetVariableCustomAction($inputID, $sid);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceTelevision' => json_encode([
                    [
                        'ID'                       => '1',
                        'Name'                     => 'Fernseher',
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
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "0",
                "callSign": "ARD"
            },
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.Speaker",
            "name": "volume",
            "value": 25,
            "timeOfSample": "",
            "uncertaintyInMilliseconds": 0
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
          "timeOfSample": "",
          "uncertaintyInMilliseconds": 0
        },
        {
            "namespace": "Alexa.InputController",
            "name": "input",
            "value": "AUX 1",
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
            // $this->assertEquals(2, GetValue($channelID));

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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
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
            "name": "SetMute",
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
            "mute": true
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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": true,
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
            "name": "SetMute",
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
            "mute": false
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
        },
        {
          "namespace": "Alexa.Speaker",
          "name": "muted",
          "value": false,
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
            "namespace": "Alexa.ChannelController",
            "name": "ChangeChannel",
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
            "channel": {
                "number": "1"
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "1",
                "callSign": "ZDF"
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

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "ChangeChannel",
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
            "channel": {
                "callSign": "NDR3"
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "2",
                "callSign": "NDR3"
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

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "ChangeChannel",
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
            "channel": {
                "affiliateCallSign": "ARD"
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "0",
                "callSign": "ARD"
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

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "ChangeChannel",
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
            "channel": {
                "uri": "ZDF"
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "1",
                "callSign": "ZDF"
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

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "ChangeChannel",
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
            "channelMetadata": {
                "name": "NDR3"
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "2",
                "callSign": "NDR3"
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

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "ChangeChannel",
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
            "channel": {
                "name": "Pro 7"
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "ErrorResponse",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {
            "type": "INVALID_VALUE",
            "message": "Channel not found in profile associations"
        }
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "ChangeChannel",
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
            "channel": {
                "number": "7"
            }
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "event": {
        "header": {
            "namespace": "Alexa",
            "name": "ErrorResponse",
            "payloadVersion": "3",
            "messageId": "",
            "correlationToken": "dFMb0z+PgpgdDmluhJ1LddFvSqZ/jCc8ptlAKulUj90jSqg=="
        },
        "endpoint": {
            "endpointId": "1"
        },
        "payload": {
            "type": "INVALID_VALUE",
            "message": "Channel not found in profile associations"
        }
    }
}
EOT;

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "SkipChannels",
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
            "channelCount": 2
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "1",
                "callSign": "ZDF"
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

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.ChannelController",
            "name": "SkipChannels",
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
            "channelCount": -1
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.ChannelController",
            "name": "channel",
            "value": {
                "number": "0",
                "callSign": "ARD"
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

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.InputController",
            "name": "SelectInput",
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
            "input": "AUX 3"
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.InputController",
            "name": "input",
            "value": "AUX 3",
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

    public function testRangeControllerShutterDirectivesPercentage()
    {
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(2 /* Float */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => -100, 'MAX' => 300]);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceShutter' => json_encode([
                    [
                        'ID'                       => '1',
                        'Name'                     => 'Flur Rollladen',
                        'RangeControllerShutterID' => $vid
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
            "namespace": "Alexa.RangeController",
            "name": "rangeValue",
            "instance": "Shutter.Position",
            "value": "75",
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
            "namespace": "Alexa.RangeController",
            "name": "SetRangeValue",
            "instance": "Shutter.Position",
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
            "rangeValue": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.RangeController",
            "name": "rangeValue",
            "instance": "Shutter.Position",
            "value": "42",
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
            "namespace": "Alexa.RangeController",
            "name": "AdjustRangeValue",
            "instance": "Shutter.Position",
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
            "rangeValueDelta": 42,
            "rangeValueDeltaDefault": false
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.RangeController",
            "name": "rangeValue",
            "instance": "Shutter.Position",
            "value": "84",
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

    public function testRangeControllerShutterDirectivesShutterProfile()
    {
        $testFunction = function ($emulateStatus)
        {
            $sid = IPS_CreateScript(0 /* PHP */);
            IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

            $vid = IPS_CreateVariable(1 /* Integer */);
            IPS_SetVariableCustomAction($vid, $sid);

            IPS_SetVariableCustomPresentation($vid, ['PRESENTATION' => VARIABLE_PRESENTATION_LEGACY, 'PROFILE' => '~ShutterMoveStop']);

            $iid = IPS_CreateInstance($this->alexaModuleID);

            IPS_SetConfiguration($iid, json_encode([
                'DeviceShutter' => json_encode([
                    [
                        'ID'                       => '1',
                        'Name'                     => 'Flur Rollladen',
                        'RangeControllerShutterID' => $vid
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
            "namespace": "Alexa.RangeController",
            "name": "rangeValue",
            "instance": "Shutter.Position",
            "value": "100",
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
            "namespace": "Alexa.RangeController",
            "name": "SetRangeValue",
            "instance": "Shutter.Position",
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
            "rangeValue": 42
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.RangeController",
            "name": "rangeValue",
            "instance": "Shutter.Position",
            "value": "0",
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

            $testResponse = json_decode($testResponse, true);
            if ($emulateStatus) {
                $testResponse['context']['properties'][0]['value'] = '42';
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals($testResponse, json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));

            $testRequest = <<<'EOT'
{
    "directive": {
        "header": {
            "namespace": "Alexa.RangeController",
            "name": "AdjustRangeValue",
            "instance": "Shutter.Position",
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
            "rangeValueDelta": 62,
            "rangeValueDeltaDefault": false
        }
    }
}           
EOT;

            $testResponse = <<<'EOT'
{
    "context": {
        "properties": [ {
            "namespace": "Alexa.RangeController",
            "name": "rangeValue",
            "instance": "Shutter.Position",
            "value": "100",
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

            $testResponse = json_decode($testResponse, true);
            if ($emulateStatus) {
                $testResponse['context']['properties'][0]['value'] = '62';
            }

            // Convert result back and forth to turn empty stdClasses into empty arrays
            $this->assertEquals($testResponse, json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
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
