<?php

declare(strict_types=1);

class CapabilityPlaybackController
{
    const capabilityPrefix = 'PlaybackController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperCapabilityDiscovery {
        getCapabilityInformation as getCapabilityInformationBase;
    }
    use HelperPlaybackDevice;

    public static function computeProperties($configuration)
    {
        return [];
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'Playback Variable',
                'name'  => self::capabilityPrefix . 'ID',
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
        return self::getPlaybackCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function getStatusPrefix()
    {
        return 'Playback: ';
    }

    public static function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $variableID = $configuration[self::capabilityPrefix . 'ID'];
        $success = false;
        switch ($directive) {
            case 'Play':
                $success = self::activatePlay($variableID);
                break;

            case 'Pause':
                $success = self::activatePause($variableID);
                break;

            case 'Stop':
                $success = self::activateStop($variableID);
                break;

            case 'Previous':
                $success = self::activatePrevious($variableID);
                break;

            case 'Next':
                $success = self::activateNext($variableID);
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }

        if ($success) {
            return [
                'properties'     => self::computeProperties($configuration),
                'payload'        => new stdClass(),
                'eventName'      => 'Response',
                'eventNamespace' => 'Alexa'
            ];
        }
        else {
            return [
                'payload'        => [
                    'type' => 'NO_SUCH_ENDPOINT'
                ],
                'eventName'      => 'ErrorResponse',
                'eventNamespace' => 'Alexa'
            ];
        }
    }

    public static function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
    }

    public static function supportedDirectives()
    {
        return [
            'Next',
            'Pause',
            'Play',
            'Previous',
            'Stop'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.PlaybackController'
        ];
    }

    public static function supportedProperties($realCapability, $configuration)
    {
        return [];
    }

    public static function getCapabilityInformation($configuration)
    {
        $info = self::getCapabilityInformationBase($configuration);
        $supportedOperations = ['Play', 'Pause', 'Stop'];
        if (self::supportsPreviousNext($configuration[self::capabilityPrefix . 'ID'])) {
            $supportedOperations[] = 'Previous';
            $supportedOperations[] = 'Next';
        }
        $info[0]['supportedOperations'] = $supportedOperations;

        unset($info[0]['properties']);
        return $info;
    }
}
