<?php

declare(strict_types=1);

class CapabilitySpeakerMuteable
{
    const capabilityPrefix = 'SpeakerMuteable';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery;
    use HelperDimDevice;
    use HelperSwitchDevice;

    private static function computePropertiesForValue($volume, $muted)
    {
        $propertys = [];
        if (!is_null($volume)) {
            $propertys[] = [
                'namespace'                 => 'Alexa.Speaker',
                'name'                      => 'volume',
                'value'                     => $volume,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds' => 0
            ];
        }
        if (!is_null($muted)) {
            $propertys[] = [
                'namespace'                 => 'Alexa.Speaker',
                'name'                      => 'muted',
                'value'                     => $muted,
                'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                'uncertaintyInMilliseconds'	=> 0
            ];
        }
        return $propertys;
    }

    public static function computeProperties($configuration)
    {
        $volume = null;
        $muted = null;
        if ($configuration[self::capabilityPrefix . 'VolumeID'] != 0) {
            $volume = self::getDimValue($configuration[self::capabilityPrefix . 'VolumeID']);
        }
        if ($configuration[self::capabilityPrefix . 'MuteID'] != 0) {
            $muted = self::getSwitchValue($configuration[self::capabilityPrefix . 'MuteID']);
        }
        return self::computePropertiesForValue($volume, $muted);
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'Volume Variable',
                'name'  => self::capabilityPrefix . 'VolumeID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'label' => 'Mute Variable',
                'name'  => self::capabilityPrefix . 'MuteID',
                'width' => '250px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        $volumeExists = ($configuration[self::capabilityPrefix . 'VolumeID'] != 0);
        $muteExists = ($configuration[self::capabilityPrefix . 'MuteID'] != 0);
        if (!$volumeExists && !$muteExists) {
            return 'Missing';
        }

        if ($volumeExists) {
            $volumeStatus = self::getDimCompatibility($configuration[self::capabilityPrefix . 'VolumeID']);
            if ($volumeStatus != 'OK') {
                return 'Volume: ' . $volumeStatus;
            }
        }

        if ($muteExists) {
            $volumeStatus = self::getSwitchCompatibility($configuration[self::capabilityPrefix . 'MuteID']);
            if ($volumeStatus != 'OK') {
                return 'Mute: ' . $volumeStatus;
            }
        }

        return  'OK';
    }

    public static function getStatusPrefix()
    {
        return 'Speaker: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setVolume = function ($configuration, $value, $emulateStatus) {
            if (self::dimDevice($configuration[self::capabilityPrefix . 'VolumeID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = self::computePropertiesForValue($value, null);
                } else {
                    $i = 0;
                    while (($value != self::getDimValue($configuration[self::capabilityPrefix . 'VolumeID'])) && $i < 10) {
                        $i++;
                        usleep(100000);
                    }
                    $properties = self::computeProperties($configuration);
                }
                return [
                    'properties'     => $properties,
                    'payload'        => new stdClass(),
                    'eventName'      => 'Response',
                    'eventNamespace' => 'Alexa'
                ];
            } else {
                return [
                    'payload'        => [
                        'type' => 'NO_SUCH_ENDPOINT'
                    ],
                    'eventName'      => 'ErrorResponse',
                    'eventNamespace' => 'Alexa'
                ];
            }
        };

        $setMuted = function ($configuration, $value, $emulateStatus) {
            if (self::switchDevice($configuration[self::capabilityPrefix . 'MuteID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = self::computePropertiesForValue(null, $value);
                } else {
                    $i = 0;
                    while (($value != self::getSwitchValue($configuration[self::capabilityPrefix . 'MuteID'])) && $i < 10) {
                        $i++;
                        usleep(100000);
                    }
                    $properties = self::computeProperties($configuration);
                }
                return [
                    'properties'     => $properties,
                    'payload'        => new stdClass(),
                    'eventName'      => 'Response',
                    'eventNamespace' => 'Alexa'
                ];
            } else {
                return [
                    'payload'        => [
                        'type' => 'NO_SUCH_ENDPOINT'
                    ],
                    'eventName'      => 'ErrorResponse',
                    'eventNamespace' => 'Alexa'
                ];
            }
        };

        switch ($directive) {
            case 'ReportState':
                return [
                    'properties'     => self::computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];

            case 'AdjustVolume':
                return $setVolume($configuration, self::getDimValue($configuration[self::capabilityPrefix . 'VolumeID']) + $payload['volume'], $emulateStatus);

            case 'SetVolume':
                return $setVolume($configuration, $payload['volume'], $emulateStatus);

            case 'SetMute':
                 return $setMuted($configuration, $payload['mute'], $emulateStatus);

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'VolumeID'],
            $configuration[self::capabilityPrefix . 'MuteID']
        ];
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'SetVolume',
            'AdjustVolume',
            'SetMute'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.Speaker'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        $properties = [];
        if ($configuration[self::capabilityPrefix . 'VolumeID'] != 0) {
            $properties[] = 'volume';
        }

        if ($configuration[self::capabilityPrefix . 'MuteID'] != 0) {
            $properties[] = 'mute';
        }

        return $properties;
    }
}
