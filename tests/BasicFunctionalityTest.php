<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/autoload.php';

use PHPUnit\Framework\TestCase;

class BasicFunctionalityTest extends TestCase
{
    private $alexaModuleID = '{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}';
    private $connectControlID = '{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}';

    public function setUp(): void
    {
        //Reset
        IPS\Kernel::reset();

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/stubs/CoreStubs/library.json');

        // Create a Connect Control
        IPS_CreateInstance($this->connectControlID);

        parent::setUp();
    }

    public function testCreate()
    {
        $previousCount = count(IPS_GetInstanceListByModuleID($this->alexaModuleID));
        IPS_CreateInstance($this->alexaModuleID);
        $this->assertEquals($previousCount + 1, count(IPS_GetInstanceListByModuleID($this->alexaModuleID)));
    }

    public function testSearchForReferences()
    {
        $sid = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($sid, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');

        $vid1 = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid1, $sid);

        $vid2 = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid2, $sid);

        $vid3 = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid3, $sid);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceGenericSwitch' => json_encode([
                [
                    'ID'                => '1',
                    'Name'              => 'Flur Gerät',
                    'PowerControllerID' => $vid1
                ]
            ]),
            'DeviceLightExpert' => json_encode([
                [
                    'ID'                         => '2',
                    'Name'                       => 'Expertenlicht',
                    'PowerControllerID'          => $vid2,
                    'ColorOnlyControllerID'      => $vid3, // Wrong configuration as color does not work with bool, but should post it as reference nonetheless
                    'BrightnessOnlyControllerID' => 0

                ]
            ])
        ]));
        IPS_ApplyChanges($iid);

        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Alexa);

        $references = IPS_GetReferenceList($iid);

        $this->assertEquals(3, count($references));
        $this->assertTrue(in_array($vid1, $references));
        $this->assertTrue(in_array($vid2, $references));
        $this->assertTrue(in_array($vid3, $references));
    }

    public function testCorrectOutputOnError()
    {
        $vid = IPS_CreateVariable(0 /* Boolean */);

        $activateScriptID = IPS_CreateScript(0);
        $deactivateScriptID = IPS_CreateScript(0);

        $iid = IPS_CreateInstance($this->alexaModuleID);

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
            "type": "NO_SUCH_ENDPOINT"
        }
    }
}
EOT;

        // Convert result back and forth to turn empty stdClasses into empty arrays
        $this->assertEquals(json_decode($testResponse, true), json_decode(json_encode($this->clearResponse($intf->SimulateData(json_decode($testRequest, true)))), true));
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
