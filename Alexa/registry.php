<?php

declare(strict_types=1);

class DeviceTypeRegistry
{
    const classPrefix = 'DeviceType';
    const propertyPrefix = 'Device';
    const deviceSearchPrefix = 'FoundDevice';

    private static $supportedDeviceTypes = [];

    private $registerProperty = null;
    private $instanceID = 0;

    public function __construct(int $instanceID, callable $registerProperty)
    {
        $this->registerProperty = $registerProperty;
        $this->instanceID = $instanceID;
    }

    public static function register(string $deviceType): void
    {

        //Check if the same service was already registered
        if (in_array($deviceType, self::$supportedDeviceTypes)) {
            throw new Exception('Cannot register deviceType! ' . $deviceType . ' is already registered.');
        }
        //Add to our static array
        self::$supportedDeviceTypes[] = $deviceType;
    }

    public function repairIDs(array $listValues, callable $updateFormField): void
    {
        $nextID = intval($this->getNextID($listValues));

        $ids = [];
        foreach ($listValues as $i => $datas) {
            $dataArray = [];
            foreach ($datas as $data) {
                $dataArray[] = $data;
            }
            $updateField = false;
            foreach ($dataArray as $j => $data) {
                if (!is_numeric($data['ID']) || in_array($data['ID'], $ids)) {
                    $dataArray[$j]['ID'] = strval($nextID);
                    $nextID++;
                    $updateField = true;
                }

                // Access via index as it could have been updated
                $ids[] = $dataArray[$j]['ID'];
            }
            if ($updateField) {
                $updateFormField(self::propertyPrefix . self::$supportedDeviceTypes[$i], 'values', json_encode($dataArray));
                $listValues[$i] = $dataArray;
            }
        }

        $this->updateNextID($listValues, $updateFormField);
    }

    public function searchDevices(array $listValues, callable $updateFormField): void
    {
        $updateFormField('DeviceSearchButton', 'popup.buttons', json_encode([]));
        $updateFormField('DeviceSearchProgress', 'visible', true);
        $updateFormField('DeviceSearchNoneFoundLabel', 'visible', false);
        $updateFormField('DeviceSearchColumn', 'items', json_encode([]));
        $deviceTrees = [];
        $variableNamesExisting = [];
        $variableNamesNew = [];

        $translations = $this->getTranslations();
        $language = 'de'; // TODO: Use System Language once available via function
        $translate = function($text) use ($translations, $language) {
            if (isset($translations[$language][$text])) {
                return $translations[$language][$text];
            } else {
                return $text;
            }
        };

        $usedVariables = [];
        foreach($listValues as $listData) {
            foreach ($listData as $listEntry) {
                foreach ($listEntry as $parameterValue) {
                    // IDs are not trigered here as they are numeric strings
                    if (is_int($parameterValue)) {
                        $usedVariables[] = $parameterValue;
                    }
                }
            }
        }

        foreach ($this->getSortedDeviceTypes() as $deviceType) {
            $deviceTypeObject = $this->generateDeviceTypeObject($deviceType);
            $detectedDevices = $deviceTypeObject->getDetectedDevices();
            // Treat expert devices as empty list
            if ($deviceTypeObject->isExpertDevice()) {
                $detectedDevices = [];
            }

            $variableNamesExisting[] = '$' . self::propertyPrefix . $deviceType;
            $variableNamesNew[] = '$' . self::deviceSearchPrefix . $deviceType;
            $columns = $deviceTypeObject->getColumns();
            $columnObject = [];
            foreach ($columns as $column) {
                $columnObject[$column['name']] = $column['caption'];
            }

            $treeValues = [];
            foreach ($detectedDevices as $instanceID => $detectedVariables) {
                $newValues = [[
                    'objectID' => $instanceID,
                    'name'     => IPS_GetName($instanceID),
                    'function' => 'This should never be readable: Function not relevant for top level',
                    'register' => false,
                    'expanded' => true,
                    'id'       => $instanceID
                ]];
                foreach ($detectedVariables as $name => $variableID) {
                    // Remove devices from list if any detected variable is already used in some registered device
                    $variableAlreadyUsed = in_array($variableID, $usedVariables);
                    if ($variableAlreadyUsed) {
                        break;
                    }
                    $newValues[] = [
                        'objectID' => $variableID,
                        'function' => $name,
                        'name'     => $translate($columnObject[$name]),
                        'register' => false,
                        'id'       => $variableID,
                        'parent'   => $instanceID,
                        'editable' => false
                    ];
                }
                if (!$variableAlreadyUsed) {
                    $treeValues = array_merge($treeValues, $newValues);
                }
            }

            $deviceTrees[] = [
                'type'    => 'Tree',
                'name'    => self::deviceSearchPrefix . $deviceType,
                'caption' => $deviceTypeObject->getCaption(),
                'columns' => [
                    [
                        'caption' => 'Register',
                        'name'    => 'register',
                        'width'   => '100px',
                        'edit'    => [
                            'type' => 'CheckBox'
                        ]
                    ],
                    [
                        'caption' => 'Object',
                        'name'    => 'objectID',
                        'width'   => 'auto',
                        'edit'    => [
                            'type'    => 'SelectObject',
                            'enabled' => false
                        ]
                    ],
                    [
                        'caption' => 'Name',
                        'name'    => 'name',
                        'width'   => '200px',
                        'edit'    => [
                            'type' => 'ValidationTextBox'
                        ]
                    ],
                    [
                        'caption' => 'Function',
                        'name'    => 'function',
                        'width'   => '200px',
                        'visible' => false
                    ]
                ],
                'values'   => $treeValues,
                'rowCount' => max(min(count($treeValues), 10), 1),
                'visible'  => count($detectedDevices) > 0
            ];
        }

        $updateFormField('DeviceSearchProgress', 'visible', false);
        if (count($deviceTrees) === 0) {
            $updateFormField('DeviceSearchNoneFoundLabel', 'visible', true);
        } else {
            $updateFormField('DeviceSearchColumn', 'items', json_encode($deviceTrees));
            $updateFormField('DeviceSearchButton', 'popup.buttons', json_encode([[
                'onClick' => 'AA_UIAddSearchedDevices($id, [ ' . implode(', ', $variableNamesExisting) . ' ], [ ' . implode(', ', $variableNamesNew) . ' ]);',
                'caption' => 'Add Devices'
            ]]));
        }
    }

    public function addDevices(array $currentDevices, array $newDevices, callable $updateFormField): void
    {
        $deviceTypes = $this->getSortedDeviceTypes();
        $nextID = intval($this->getNextID($currentDevices));
        for ($i = 0; $i < count($currentDevices); $i++) {
            // Generate entries for new devices
            $updateField = false;
            $dataArray = [];
            foreach ($currentDevices[$i] as $data) {
                $dataArray[] = $data;
            }
            foreach ($newDevices[$i] as $newDevice) {
                if (($newDevice['parent'] === 0) && ($newDevice['register'])) {
                    $newEntry = [
                        'ID'     => $nextID,
                        'Name'   => $newDevice['name'],
                        'Status' => '-'
                    ];
                    $nextID++;
                    
                    foreach ($newDevices[$i] as $newDeviceChild) {
                        if ($newDeviceChild['parent'] === $newDevice['id']) {
                            $newEntry[$newDeviceChild['function']] = $newDeviceChild['objectID'];                            
                        }
                    }
                    $dataArray[] = $newEntry;
                    $updateField = true;
                }
            }

            if ($updateField) {
                $updateFormField(self::classPrefix . $deviceTypes[$i] . 'Panel', 'expanded', true);
                $updateFormField(self::propertyPrefix . $deviceTypes[$i], 'values', json_encode($dataArray));
                $currentDevices[$i] = $dataArray;
            }
        }

        $this->updateNextID($currentDevices, $updateFormField);
    }

    public function registerProperties(): void
    {

        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $actionType) {
            ($this->registerProperty)(self::propertyPrefix . $actionType, '[]');
        }
    }

    public function updateNextID(array $listValues, callable $updateFormField)
    {
        $nextID = $this->getNextID($listValues);
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $updateFormField(self::propertyPrefix . $deviceType, 'columns', json_encode($this->getColumns($deviceType, $nextID)));
        }
    }

    public function doDiscovery(): array
    {
        $endpoints = [];

        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                if ($this->isOK($deviceType, $configuration)) {
                    $endpoints[] = $this->generateDeviceTypeObject($deviceType)->doDiscovery($configuration);
                }
            }
        }

        return $endpoints;
    }

    public function doDirective($deviceID, $directiveName, $payload)
    {
        $emulateStatus = IPS_GetProperty($this->instanceID, 'EmulateStatus');
        //Add all deviceType specific properties
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                if ($configuration['ID'] == $deviceID) {
                    return $this->generateDeviceTypeObject($deviceType)->doDirective($configuration, $directiveName, $payload, $emulateStatus);
                }
            }
        }

        //Return a device not found error
        return [
            'payload' => [
                'type' => 'NO_SUCH_ENDPOINT'
            ],
            'eventName'      => 'ErrorResponse',
            'eventNamespace' => 'Alexa'
        ];
    }

    public function getObjectIDs()
    {
        $result = [];
        // Add all variable IDs of all devices
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                $result = array_unique(array_merge($result, $this->generateDeviceTypeObject($deviceType)->getObjectIDs($configuration)));
            }
        }

        return $result;
    }

    public function getConfigurationForm(): array
    {
        $form = [];

        $sortedDeviceTypes = $this->getSortedDeviceTypes();

        $showExpertDevices = IPS_GetProperty($this->instanceID, 'ShowExpertDevices');

        $variableNames = [];
        $listValues = [];
        foreach ($sortedDeviceTypes as $deviceType) {
            $listValues[] = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
        }
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $variableNames[] = '$' . self::propertyPrefix . $deviceType;
        }
        $addScript = 'AA_UIUpdateNextID($id, [ ' . implode(', ', $variableNames) . ' ]);';
        $nextID = $this->getNextID($listValues);

        if ($this->GetStatus() === 200) {
            $form[] = [
                'type'    => 'Button',
                'caption' => 'Repair IDs',
                'onClick' => 'AA_UIRepairIDs($id, [ ' . implode(', ', $variableNames) . ' ]);'
            ];
        }

        $form[] = [
            'type'    => 'PopupButton',
            'name'    => 'DeviceSearchButton',
            'caption' => 'Search for Devices',
            'onClick' => 'AA_UIStartDeviceSearch($id, [ ' . implode(', ', $variableNames) . ' ]);',
            'popup'   => [
                'caption' => 'Device Search',
                'items'   => [
                    [
                        'type'          => 'ProgressBar',
                        'name'          => 'DeviceSearchProgress',
                        'indeterminate' => true,
                        'caption'       => 'Searching for devices...'
                    ],
                    [
                        'type'    => 'Label',
                        'name'    => 'DeviceSearchNoneFoundLabel',
                        'caption' => 'No devices found, devices that are already registered with Alexa are not found again',
                        'visible' => false
                    ],
                    [
                        'type'  => 'ColumnLayout',
                        'name'  => 'DeviceSearchColumn',
                        'items' => []
                    ]
                ]
            ]
        ];

        foreach ($sortedDeviceTypes as $deviceType) {
            $deviceTypeObject = $this->generateDeviceTypeObject($deviceType);

            $values = [];

            $configurations = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($configurations as $configuration) {
                // Legacy Versions of the LightExpert could not have the ColorTemperature. In that case, add it here manually, so getStatus won't fail
                if (($deviceType == 'LightExpert') && !isset($configuration['ColorTemperatureOnlyControllerID'])) {
                    $configuration['ColorTemperatureOnlyControllerID'] = 0;
                }
                $newValues = [
                    'Status' => $deviceTypeObject->getStatus($configuration)
                ];
                $values[] = $newValues;
            }

            $expertDevice = $deviceTypeObject->isExpertDevice();

            $form[] = [
                'type'    => 'ExpansionPanel',
                'name'    => self::classPrefix . $deviceType . 'Panel',
                'caption' => $deviceTypeObject->getCaption(),
                'visible' => $showExpertDevices || !$expertDevice,
                'items'   => [[
                    'type'     => 'List',
                    'name'     => self::propertyPrefix . $deviceType,
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => $this->getColumns($deviceType, $nextID),
                    'values'  => $values,
                    'onAdd'   => $addScript
                ]]
            ];
        }

        return $form;
    }

    public function getTranslations(): array
    {
        $translations = [
            'de' => [
                'No name'                                                                                                                              => 'Kein Name',
                'Name'                                                                                                                                 => 'Name',
                'ID'                                                                                                                                   => 'ID',
                'Status'                                                                                                                               => 'Status',
                'Symcon Connect is not active!'                                                                                                        => 'Symcon Connect ist nicht aktiv!',
                'Symcon Connect is OK!'                                                                                                                => 'Symcon Connect ist OK!',
                'Expert Options'                                                                                                                       => 'Expertenoptionen',
                'Please check the documentation before handling these settings. These settings do not need to be changed under regular circumstances.' => 'Bitte prüfen Sie die Dokumentation bevor Sie diese Einstellungen anpassen. Diese Einstellungen müssen unter normalen Umständen nicht verändert werden.',
                'Emulate Status'                                                                                                                       => 'Status emulieren',
                'Show Expert Devices'                                                                                                                  => 'Expertengeräte anzeigen',
                'The IDs of the devices seem to be broken. Either some devices have the same ID or IDs are not numeric.'                               => 'Die IDs der Geräte scheinen fehlerhaft zu sein. Entweder haben einige Geräte die gleiche ID oder IDs sind nicht numerisch',
                'IDs updated. Apply changes to save the fixed IDs.'                                                                                    => 'IDs aktualisiert. Bitte übernehmen Sie die Änderngen um die korrigierten IDs zu speichern.',
                'Repair IDs'                                                                                                                           => 'IDs reparieren',
                'Search for Devices'                                                                                                                   => 'Suche nach Geräten',
                'Device Search'                                                                                                                        => 'Gerätesuche',
                'Add Devices'                                                                                                                          => 'Geräte hinzufügen',
                'Searching for devices...'                                                                                                             => 'Suche nach Geräten läuft...',
                'Register'                                                                                                                             => 'Registrieren',
                'Object'                                                                                                                               => 'Objekt'
            ]
        ];

        foreach (self::$supportedDeviceTypes as $deviceType) {
            foreach ($this->generateDeviceTypeObject($deviceType)->getTranslations() as $language => $languageTranslations) {
                if (array_key_exists($language, $translations)) {
                    foreach ($languageTranslations as $original => $translated) {
                        if (array_key_exists($original, $translations[$language])) {
                            if ($translations[$language][$original] != $translated) {
                                throw new Exception('Different translations ' . $translated . ' + ' . $translations[$language][$original] . ' for original ' . $original . ' was found!');
                            }
                        } else {
                            $translations[$language][$original] = $translated;
                        }
                    }
                } else {
                    $translations[$language] = $languageTranslations;
                }
            }
        }

        return $translations;
    }

    public function getStatus()
    {
        $ids = [];
        foreach (self::$supportedDeviceTypes as $deviceType) {
            $listValues = json_decode(IPS_GetProperty($this->instanceID, self::propertyPrefix . $deviceType), true);
            foreach ($listValues as $listValue) {
                if (!is_numeric($listValue['ID']) || in_array($listValue['ID'], $ids)) {
                    return 200;
                }

                $ids[] = $listValue['ID'];
            }
        }

        //Check Connect availability
        $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');
        if (IPS_GetInstance($ids[0])['InstanceStatus'] != 102) {
            return 104;
        } else {
            return 102;
        }
    }

    public function isOK($deviceType, $configuration)
    {
        return ($this->generateDeviceTypeObject($deviceType)->getStatus($configuration) == 'OK') && ($configuration['ID'] != '');
    }

    public function getExpertPanelNames()
    {
        $result = [];
        foreach (self::$supportedDeviceTypes as $deviceType) {
            if ($this->generateDeviceTypeObject($deviceType)->isExpertDevice()) {
                $result[] = self::classPrefix . $deviceType . 'Panel';
            }
        }
        return $result;
    }

    private function generateDeviceTypeObject(string $deviceTypeName)
    {
        $deviceTypeClass = 'DeviceType' . $deviceTypeName;
        $deviceTypeObject = new $deviceTypeClass($this->instanceID);
        return $deviceTypeObject;
    }

    private function getNextID(array $listValues): string
    {
        $highestID = 0;

        foreach ($listValues as $datas) {
            foreach ($datas as $data) {
                $highestID = max($highestID, intval($data['ID']));
            }
        }

        return strval($highestID + 1);
    }

    private function getColumns(string $deviceType, string $nextID)
    {
        $columns = [
            [
                'caption' => 'ID',
                'name'    => 'ID',
                'width'   => '35px',
                'add'     => $nextID,
                'save'    => true
            ],
            [
                'caption' => 'Name',
                'name'    => 'Name',
                'width'   => 'auto',
                'add'     => '',
                'edit'    => [
                    'type' => 'ValidationTextBox'
                ]
            ], //We will insert the custom columns here
            [
                'caption' => 'Status',
                'name'    => 'Status',
                'width'   => '100px',
                'add'     => '-'
            ]
        ];

        array_splice($columns, 2, 0, $this->generateDeviceTypeObject($deviceType)->getColumns());

        return $columns;
    }

    private function getSortedDeviceTypes()
    {
        $sortedDeviceTypes = self::$supportedDeviceTypes;
        usort($sortedDeviceTypes, function ($a, $b)
        {
            $posA = $this->generateDeviceTypeObject($a)->getPosition();
            $posB = $this->generateDeviceTypeObject($b)->getPosition();

            return ($posA < $posB) ? -1 : 1;
        });
        return $sortedDeviceTypes;
    }
}
