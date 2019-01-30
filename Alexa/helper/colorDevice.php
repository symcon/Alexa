<?php

declare(strict_types=1);

trait HelperColorDevice
{
    private static function rgbToHex($r, $g, $b)
    {
        return ($r << 16) + ($g << 8) + $b;
    }

    private static function getColorBrightness($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 0;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return 0;
        }

        return self::getColorBrightnessByValue(GetValueInteger($variableID));
    }

    private static function getColorBrightnessByValue($rgbValue)
    {
        if (($rgbValue < 0) || ($rgbValue > 0xFFFFFF)) {
            return 0;
        }

        $red = intval($rgbValue >> 16);
        $green = intval(($rgbValue % 0x10000) >> 8);
        $blue = intval($rgbValue % 0x100);

        $maxColor = max($red, $green, $blue);
        return (floatval($maxColor) / 255.0) * 100;
    }

    private static function computeColorBrightness($variableID, $brightness)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return false;
        }

        $rgbValue = GetValueInteger($variableID);

        if (($rgbValue < 0) || ($rgbValue > 0xFFFFFF)) {
            return false;
        }

        $brightness = min(100.0, $brightness);
        $brightness = max(0.0, $brightness);

        $red = intval($rgbValue >> 16);
        $green = intval(($rgbValue % 0x10000) >> 8);
        $blue = intval($rgbValue % 0x100);

        $previousBrightness = self::getColorBrightness($variableID);

        $newRed = 0;
        $newGreen = 0;
        $newBlue = 0;

        if ($previousBrightness != 0) {
            $newRed = intval($red * ($brightness / $previousBrightness));
            $newGreen = intval($green * ($brightness / $previousBrightness));
            $newBlue = intval($blue * ($brightness / $previousBrightness));
        }
        // If the color was black before (which is the only possibility for its brightness = 0), just dim white
        else {
            $newRed = intval(0xff * ($brightness / 100));
            $newGreen = $newRed;
            $newBlue = $newRed;
        }

        return self::rgbToHex($newRed, $newGreen, $newBlue);
    }

    private static function setColorBrightness($variableID, $brightness)
    {
        return self::colorDevice($variableID, self::computeColorBrightness($variableID, $brightness));
    }

    private static function getColorCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return 'Integer required';
        }

        if ($targetVariable['VariableCustomAction'] != '') {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if (!($profileAction > 10000)) {
            return 'Action required';
        }

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if ($profileName != '~HexColor') {
            return '~HexColor profile required';
        }

        return 'OK';
    }

    private static function getColorValue($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 0;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return 0;
        }

        $value = GetValueInteger($variableID);

        if (($value < 0) || ($value > 0xFFFFFF)) {
            return 0;
        }

        return $value;
    }

    private static function colorDevice($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomAction'] != 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if ($profileAction < 10000) {
            return false;
        }

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return false;
        }

        if (($value < 0) || ($value > 0xFFFFFF)) {
            return false;
        }

        if (IPS_InstanceExists($profileAction)) {
            IPS_RunScriptText('IPS_RequestAction(' . var_export($profileAction, true) . ', ' . var_export(IPS_GetObject($variableID)['ObjectIdent'], true) . ', ' . var_export($value, true) . ');');
        } elseif (IPS_ScriptExists($profileAction)) {
            IPS_RunScriptEx($profileAction, ['VARIABLE' => $variableID, 'VALUE' => $value, 'SENDER' => 'VoiceControl']);
        } else {
            return false;
        }

        return true;
    }

    private static function rgbToHSB($rgbValue)
    {
        $red = intval($rgbValue >> 16);
        $green = intval(($rgbValue % 0x10000) >> 8);
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

            $hue /= 6;

            if ($hue < 0) {
                $hue += 1;
            }
        }
        return [
            'hue'        => $hue * 360,
            'saturation' => $saturation,
            'brightness' => $brightness
        ];
    }

    private static function hsbToRGB($hsbValue)
    {
        $prepareValue = function ($value) {
            return intval($value * 255 + 0.5);
        };

        // Conversion algorithm from http://www.docjar.com/html/api/java/awt/Color.java.html
        if ($hsbValue['saturation'] == 0.0) {
            $colorValue = $prepareValue($hsbValue['brightness']);
            return self::rgbToHex($colorValue, $colorValue, $colorValue);
        } else {
            $huePercentage = $hsbValue['hue'] / 360;
            $h = ($huePercentage - floor($huePercentage)) * 6;
            $f = $h - floor($h);
            $p = $hsbValue['brightness'] * (1 - $hsbValue['saturation']);
            $q = $hsbValue['brightness'] * (1 - ($hsbValue['saturation'] * $f));
            $t = $hsbValue['brightness'] * (1 - ($hsbValue['saturation'] * (1 - $f)));
            switch (intval($h)) {
                case 0:
                    return self::rgbToHex(
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($t),
                        $prepareValue($p)
                    );

                case 1:
                    return self::rgbToHex(
                        $prepareValue($q),
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($p)
                    );

                case 2:
                    return self::rgbToHex(
                        $prepareValue($p),
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($t)
                    );

                case 3:
                    return self::rgbToHex(
                        $prepareValue($p),
                        $prepareValue($q),
                        $prepareValue($hsbValue['brightness'])
                    );

                case 4:
                    return self::rgbToHex(
                        $prepareValue($t),
                        $prepareValue($p),
                        $prepareValue($hsbValue['brightness'])
                    );

                case 5:
                    return self::rgbToHex(
                        $prepareValue($hsbValue['brightness']),
                        $prepareValue($p),
                        $prepareValue($q)
                    );
            }
        }
        return 0;
    }
}
