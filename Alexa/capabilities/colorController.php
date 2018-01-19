<?php

declare(strict_types=1);

class CapabilityColorController
{
    const capabilityPrefix = 'ColorController';
    const DATE_TIME_FORMAT = 'o-m-d\TH:i:s\Z';

    use HelperColorDevice;

    private static function rgbToHSB($rgbValue)
    {
        $red = intval($rgbValue / 0x10000);
        $green = intval( ($rgbValue % 0x10000) / 0x100);
        $blue = intval($rgbValue % 0x100);

        // Conversion algorithm from http://www.docjar.com/html/api/java/awt/Color.java.html
        $cMax = max($red, $green, $blue);
        $cMin = min($red, $green, $blue);

        $brightness = floatval($cMax) / 255.0;

        $saturation = 0;
        if ($cMax != 0) {
            $saturation = floatval($cMax - $cMin) / floatval($cMax);
        }

        $hue = 0;
        if ($saturation != 0) {
            $redC = floatval($cMax - $red) / floatval($cMax - $cMin);
            $greenC = floatval($cMax - $green) / floatval($cMax - $cMin);
            $blueC = floatval($cMax - $blue) / floatval($cMax - $cMin);

            switch ($cMax) {
                case $red:
                    $hue = $blueC - $greenC;
                    break;

                case $green:
                    $hue = 2 + $redC - $blueC;
                    break;

                case $blue:
                    $hue = 4 + $greenC - $redC;
                    break;
            }

            if ($hue < 0) {
                $hue += 1;
            }
        }
            return [
                'hue' => $hue,
                'saturation' => $saturation,
                'brightness' => $brightness
            ];
    }

    private static function hsbToRGB($hsbValue)
    {
        $rgbToHex = function($r, $g, $b)
        {
            return $r * 0x10000 + $g * 0x100 + $b;
        };


        // Conversion algorithm from http://www.docjar.com/html/api/java/awt/Color.java.html
        if ($hsbValue['saturation'] == 0.0) {
            $colorValue = intval($hsbValue['brightness'] * 255 + 0.5);
            return $rgbToHex($colorValue,$colorValue, $colorValue);
        } else {
            $h = ($hsbValue['hue'] - floor($hsbValue['hue'])) * 6;
            $f = $h - floor($h);
            $p = $hsbValue['brightness'] * (1 - $hsbValue['saturation']);
            $q = $hsbValue['brightness'] * (1 - ($hsbValue['saturation'] * $f));
            $t = $hsbValue['brightness'] * (1 - ($hsbValue['saturation'] * (1 - $f)));

            $prepareValue = function($value) {
                return intval($value * 255 + 0.5);
            };
            switch (intval($h)) {
                case 0:
                    return $rgbToHex(
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($t),
                        $prepareValue($p)
                    );

                case 1:
                    return $rgbToHex(
                        $prepareValue($q),
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($p)
                    );

                case 2:
                    return $rgbToHex(
                        $prepareValue($p),
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($t)
                    );

                case 3:
                    return $rgbToHex(
                        $prepareValue($p),
                        $prepareValue($q),
                        $prepareValue($hsbValue['brightness'])
                    );

                case 4:
                    return $rgbToHex(
                        $prepareValue($t),
                        $prepareValue($p),
                        $prepareValue($hsbValue['brightness'])
                    );

                case 5:
                    return $rgbToHex(
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($p),
                        $prepareValue($q)
                    );
            }
        }
        return 0;
    }

    private static function computeProperties($configuration)
    {
        if (IPS_VariableExists($configuration[self::capabilityPrefix . 'ID'])) {
            return [
                [
                    'namespace'                 => 'Alexa.PowerController',
                    'name'                      => 'powerState',
                    'value'                     => (self::getColorValue($configuration[self::capabilityPrefix . 'ID']) > 0 ? 'ON' : 'OFF'),
                    'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                    'uncertaintyInMilliseconds' => 0
                ],
                [
                    'namespace'                 => 'Alexa.ColorController',
                    'name'                      => 'color',
                    'value'                     => self::rgbToHSB(self::getColorValue($configuration[self::capabilityPrefix . 'ID'])),
                    'timeOfSample'              => gmdate(self::DATE_TIME_FORMAT),
                    'uncertaintyInMilliseconds' => 0
                ]
            ];
        } else {
            return [];
        }
    }

    public static function getColumns()
    {
        return [
            [
                'label' => 'VariableID',
                'name'  => self::capabilityPrefix . 'ID',
                'width' => '100px',
                'add'   => 0,
                'edit'  => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public static function getStatus($configuration)
    {
        return self::getColorCompatibility($configuration[self::capabilityPrefix . 'ID']);
    }

    public static function doDirective($configuration, $directive, $payload)
    {
        switch ($directive) {
            case 'ReportState':
                return [
                    'properties'     => self::computeProperties($configuration),
                    'payload'        => new stdClass(),
                    'eventName'      => 'StateReport',
                    'eventNamespace' => 'Alexa'
                ];
                break;

            case 'SetColor':
                if (self::colorDevice($configuration[self::capabilityPrefix . 'ID'], self::hsbToRGB($payload['color']))) {
                    return [
                        'properties'     => self::computeProperties($configuration),
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
                break;

            case 'TurnOn':
            case 'TurnOff':
                $value = ($directive == 'TurnOn' ? 0xFFFFFF : 0);
                if (self::colorDevice($configuration[self::capabilityPrefix . 'ID'], $value)) {
                    return [
                        'properties'     => self::computeProperties($configuration),
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
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public static function supportedDirectives()
    {
        return [
            'ReportState',
            'SetColor',
            'TurnOn',
            'TurnOff'
        ];
    }

    public static function supportedCapabilities()
    {
        return [
            'Alexa.ColorController',
            'Alexa.PowerController'
        ];
    }

    public static function supportedProperties($realCapability)
    {
        switch ($realCapability) {
            case 'Alexa.ColorController':
                return [
                    'color'
                ];

            case 'Alexa.PowerController':
                return [
                    'powerState'
                ];
        }
    }
}
