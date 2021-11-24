<?php

declare(strict_types=1);

class DeviceTypeRegistry extends CommonConnectRegistry
{
    public function __construct(int $instanceID, callable $registerProperty)
    {
        parent::__construct($instanceID, $registerProperty, 'DeviceType');
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
}
