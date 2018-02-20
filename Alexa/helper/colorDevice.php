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

        $rgbValue = GetValueInteger($variableID);

        if (($rgbValue < 0) || ($rgbValue > 0xFFFFFF)) {
            return 0;
        }

        $red = intval($rgbValue >> 16);
        $green = intval(($rgbValue % 0x10000) >> 8);
        $blue = intval($rgbValue % 0x100);

        $maxColor = max($red, $green, $blue);
        return (floatval($maxColor) / 255.0) * 100;
    }

    private static function setColorBrightness($variableID, $brightness)
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
        $newGreen = 0;

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

        return self::colorDevice($variableID, self::rgbToHex($newRed, $newGreen, $newBlue));
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
}
