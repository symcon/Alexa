<?php

declare(strict_types=1);

class CapabilityPlaybackController extends Capability
{
    use HelperPlaybackDevice;
    public const capabilityPrefix = 'PlaybackController';

    public function computeProperties($configuration)
    {
        return [];
    }

    public function getColumns()
    {
        return [
            [
                'caption' => 'Playback Variable',
                'name'    => self::capabilityPrefix . 'ID',
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
        return $this->getPlaybackCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public function getStatusPrefix()
    {
        return 'Playback: ';
    }

    public function doDirective($configuration, $directive, $payload, $emulateStatus)
    {
        $variableID = $configuration[self::capabilityPrefix . 'ID'];
        $success = false;
        switch ($directive) {
            case 'Play':
                $success = $this->activatePlay($variableID);
                break;

            case 'Pause':
                $success = $this->activatePause($variableID);
                break;

            case 'Stop':
                $success = $this->activateStop($variableID);
                break;

            case 'Previous':
                $success = $this->activatePrevious($variableID);
                break;

            case 'Next':
                $success = $this->activateNext($variableID);
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }

        if ($success) {
            return [
                'properties'     => $this->computeProperties($configuration),
                'payload'        => new stdClass(),
                'eventName'      => 'Response',
                'eventNamespace' => 'Alexa'
            ];
        } else {
            return [
                'payload'        => [
                    'type'    => 'HARDWARE_MALFUNCTION',
                    'message' => ob_get_contents()
                ],
                'eventName'      => 'ErrorResponse',
                'eventNamespace' => 'Alexa'
            ];
        }
    }

    public function getObjectIDs($configuration)
    {
        return [
            $configuration[self::capabilityPrefix . 'ID']
        ];
    }

    public function supportedDirectives()
    {
        return [
            'Next',
            'Pause',
            'Play',
            'Previous',
            'Stop'
        ];
    }

    public function supportedCapabilities()
    {
        return [
            'Alexa.PlaybackController'
        ];
    }

    public function supportedProperties($realCapability, $configuration)
    {
        return [];
    }

    public function getCapabilityInformation($configuration)
    {
        $info = parent::getCapabilityInformation($configuration);
        $supportedOperations = ['Play', 'Pause', 'Stop'];
        if ($this->supportsPreviousNext($configuration[self::capabilityPrefix . 'ID'])) {
            $supportedOperations[] = 'Previous';
            $supportedOperations[] = 'Next';
        }
        $info[0]['supportedOperations'] = $supportedOperations;

        unset($info[0]['properties']);
        return $info;
    }

    protected function getSupportedProfiles()
    {
        return [
            self::capabilityPrefix . 'ID' => ['~Playback', '~PlaybackPreviousNext']
        ];
    }
}
