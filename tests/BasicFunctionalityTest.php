<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class BasicFunctionalityTest extends TestCase
{
    private $alexaModuleID = '{CC759EB6-7821-4AA5-9267-EF08C6A6A5B3}';

    public function setUp()
    {
        //Reset
        IPS\Kernel::reset();

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

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

        $vid = IPS_CreateVariable(0 /* Boolean */);
        IPS_SetVariableCustomAction($vid, $sid);

        $activateScriptID = IPS_CreateScript(0);
        $deactivateScriptID = IPS_CreateScript(0);

        $iid = IPS_CreateInstance($this->alexaModuleID);

        IPS_SetConfiguration($iid, json_encode([
            'DeviceGenericSwitch' => json_encode([
                [
                    'ID'                => '1',
                    'Name'              => 'Flur Gerät',
                    'PowerControllerID' => $vid
                ]
            ]),
            'DeviceDeactivatableScene' => json_encode([
                [
                    'ID'                                       => '2',
                    'Name'                                     => 'Superszene',
                    'SceneControllerDeactivatableActivateID'   => $activateScriptID,
                    'SceneControllerDeactivatableDeactivateID' => $deactivateScriptID
                ]
            ])
        ]));
        IPS_ApplyChanges($iid);

        $intf = IPS\InstanceManager::getInstanceInterface($iid);
        $this->assertTrue($intf instanceof Alexa);

        $references = IPS_GetReferenceList($iid);

        $this->assertEquals(3, sizeof($references));
        $this->assertTrue(in_array($vid, $references));
        $this->assertTrue(in_array($activateScriptID, $references));
        $this->assertTrue(in_array($deactivateScriptID, $references));
    }
}
