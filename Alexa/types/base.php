<?php

declare(strict_types=1);

trait HelperDeviceTypeColumns
{
    public static function getColumns()
    {
        $columns = [];
        foreach (self::$implementedCapabilities as $capability) {
            $columns = array_merge($columns, call_user_func('Capability' . $capability . '::getColumns'));
        }
        return $columns;
    }
}

trait HelperDeviceTypeStatus
{
    public static function getStatus($configuration)
    {
        foreach (self::$implementedCapabilities as $capability) {
            $status = call_user_func('Capability' . $capability . '::getStatus', $configuration);
            if ($status != 'OK') {
                return $status;
            }
        }
        return 'OK';
    }
}

trait HelperDeviceTypeDiscovery
{
    public static function doDiscovery($configuration)
    {
        $discovery = [
            'endpointId'   => strval($configuration['ID']),
            'friendlyName' => $configuration['Name'],
            'description'  => self::getCaption() . ' by IP-Symcon',
            'manufacturerName' => 'Symcon GmbH',
            'displayCategories' => self::$displayedCategories,
            'cookie' => new stdClass,
            'capabilities' => [
            ]
        ];

        $attributes = [];
        foreach (self::$implementedCapabilities as $capability) {
            $capabilities = call_user_func('Capability' . $capability . '::supportedCapabilities');
            foreach ($capabilities as $realCapability) {
                $supportedProperties = [];
                foreach (call_user_func('Capability' . $capability . '::supportedProperties', $realCapability) as $property) {
                    $supportedProperties[] = [
                        'name' => $property
                    ];
                }
                $discovery['capabilities'][] = [
                    'type' => 'AlexaInterface',
                    'interface' => $realCapability,
                    'version' => '3',
                    'properties' => [
                        'supported' => $supportedProperties,
                        'proactivelyReported' => false,
                        'retrievable' => true
                    ]

                ];
            }
        }

        return $discovery;
    }
}


trait HelperDeviceTypeDirective
{
    public static function doDirective($configuration, $directiveName, $payload)
    {
        foreach (self::$implementedCapabilities as $capability) {
            if (in_array($directiveName, call_user_func('Capability' . $capability . '::supportedDirectives'))) {
                return call_user_func('Capability' . $capability . '::doDirective', $configuration, $directiveName, $payload);
            }
        }

        return [
            'payload' => [
                'type' => 'NO_SUCH_ENDPOINT'
            ],
            'eventName' => 'ErrorResponse'
        ];
    }
}

trait HelperDeviceType
{
    use HelperDeviceTypeColumns;
    use HelperDeviceTypeStatus;
    use HelperDeviceTypeDiscovery;
    use HelperDeviceTypeDirective;
}