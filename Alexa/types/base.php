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
        if ($configuration['Name'] == '') {
            return 'No name';
        }

        foreach (self::$implementedCapabilities as $capability) {
            $status = call_user_func('Capability' . $capability . '::getStatus', $configuration);
            if ($status != 'OK') {
                if (self::$displayStatusPrefix) {
                    return call_user_func('Capability' . $capability . '::getStatusPrefix') . $status;
                } else {
                    return $status;
                }
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
            'endpointId'        => strval($configuration['ID']),
            'friendlyName'      => $configuration['Name'],
            'description'       => self::getCaption() . ' by IP-Symcon',
            'manufacturerName'  => 'Symcon GmbH',
            'displayCategories' => self::$displayedCategories,
            'cookie'            => new stdClass(),
            'capabilities'      => [
            ]
        ];

        foreach (self::$implementedCapabilities as $capability) {
            $capabilitiesInformation = call_user_func('Capability' . $capability . '::getCapabilityInformation', $configuration);
            foreach ($capabilitiesInformation as $capabilityInformation) {
                $discovery['capabilities'][] = $capabilityInformation;
            }
        }

        return $discovery;
    }
}

trait HelperDeviceTypeDirective
{
    public static function doDirective($configuration, $directiveName, $payload, $emulateStatus)
    {
        // Report State needs to check properties of all capabilities
        if ($directiveName == 'ReportState') {
            $properties = [];

            foreach (self::$implementedCapabilities as $capability) {
                $properties = array_merge($properties, call_user_func('Capability' . $capability . '::computeProperties', $configuration));
            }

            return [
                'properties'     => $properties,
                'payload'        => new stdClass(),
                'eventName'      => 'StateReport',
                'eventNamespace' => 'Alexa'
            ];
        }

        foreach (self::$implementedCapabilities as $capability) {
            if (in_array($directiveName, call_user_func('Capability' . $capability . '::supportedDirectives'))) {
                return call_user_func('Capability' . $capability . '::doDirective', $configuration, $directiveName, $payload, $emulateStatus);
            }
        }

        return [
            'payload'        => [
                'type' => 'NO_SUCH_ENDPOINT'
            ],
            'eventName'      => 'ErrorResponse',
            'eventNamespace' => 'Alexa'
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
