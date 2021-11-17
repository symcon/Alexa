<?php

declare(strict_types=1);

class CapabilitySpeakerMuteable extends Capability
{
    use HelperDimDevice;
    use HelperSwitchDevice;
    const capabilityPrefix = 'SpeakerMuteable';

    public function computeProperties($configuration)
    {
        $volume = null;
        $muted = null;
        if ($configuration[self::capabilityPrefix . 'VolumeID'] != 0) {
            $volume = $this->getDimValue($configuration[self::capabilityPrefix . 'VolumeID']);
        }
        if ($configuration[self::capabilityPrefix . 'MuteID'] != 0) {
            $muted = $this->getSwitchValue($configuration[self::capabilityPrefix . 'MuteID']);
        }
        return $this->computePropertiesForValue($volume, $muted);
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Volume Variable',
                'name'    => self::capabilityPrefix . 'VolumeID',
                'width'   => '250px',
                'add'     => 0,
                'edit'    => [
                    'type' => 'SelectVariable'
                ]
            ],
            [
                'caption' => 'Mute Variable',
                'name'    => self::capabilityPrefix . 'MuteID',
                'width'   => '250px',
                'add'     => 0,
                'edit'    => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        $volumeExists = ($configuration[self::capabilityPrefix . 'VolumeID'] != 0);
        $muteExists = ($configuration[self::capabilityPrefix . 'MuteID'] != 0);
        if (!$volumeExists && !$muteExists) {
            return 'Missing';
        }

        if ($volumeExists) {
            $volumeStatus = $this->getDimCompatibility($configuration[self::capabilityPrefix . 'VolumeID']);
            if ($volumeStatus != 'OK') {
                return 'Volume: ' . $volumeStatus;
            }
        }

        if ($muteExists) {
            $volumeStatus = $this->getSwitchCompatibility($configuration[self::capabilityPrefix . 'MuteID']);
            if ($volumeStatus != 'OK') {
                return 'Mute: ' . $volumeStatus;
            }
        }

        return  'OK';
    }

    public function getStatusPrefix()
    {
        return 'Speaker: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $setVolume = function ($configuration, $value, $emulateStatus)
        {
            if ($this->dimDevice($configuration[self::capabilityPrefix . 'VolumeID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue($value, null);
                } else {
                    $i = 0;
                    while (($value != $this->getDimValue($configuration[self::capabilityPrefix . 'VolumeID'])) && $i < 10) {
                        $i++;
                        usleep(100000);
                    }
                    $properties = $this->computeProperties($configuration);
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

        $setMuted = function ($configuration, $value, $emulateStatus)
        {
            if ($this->switchDevice($configuration[self::capabilityPrefix . 'MuteID'], $value)) {
                $properties = [];
                if ($emulateStatus) {
                    $properties = $this->computePropertiesForValue(null, $value);
                } else {
                    $i = 0;
                    while (($value != $this->getSwitchValue($configuration[self::capabilityPrefix . 'MuteID'])) && $i < 10) {
                        $i++;
                        usleep(100000);
                    }
                    $properties = $this->computeProperties($configuration);
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
                    'properties'     => $this->computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];

            case 'AdjustVolume':
                return $setVolume($configuration, $this->getDimValue($configuration[self::capabilityPrefix . 'VolumeID']) + $payload['volume'], $emulateStatus);

            case 'SetVolume':
                return $setVolume($configuration, $payload['volume'], $emulateStatus);

            case 'SetMute':
                 return $setMuted($configuration, $payload['mute'], $emulateStatus);

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'VolumeID'],
            $configuration[self::capabilityPrefix . 'MuteID']
        ];
    }

    public function supportedDirectives()
    {
        return [
            'ReportState',
            'SetVolume',
            'AdjustVolume',
            'SetMute'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.Speaker'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
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

    private function computePropertiesForValue($volume, $muted)
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
}
