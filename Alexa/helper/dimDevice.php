<?php

declare(strict_types=1);

trait HelperDimDevice
{
    private static function getDimCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */ && $targetVariable['VariableType'] != 2 /* Float */) {
            return 'Int/Float required';
        }

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if (!IPS_VariableProfileExists($profileName)) {
            return 'Profile required';
        }

        $profile = IPS_GetVariableProfile($profileName);

        if (($profile['MaxValue'] - $profile['MinValue']) <= 0) {
            return 'Profile not dimmable';
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

    private static function getDimValue($variableID)
    {
        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        $profile = IPS_GetVariableProfile($profileName);

        if (($profile['MaxValue'] - $profile['MinValue']) <= 0) {
            return 0;
        }

        $valueToPercent = function ($value) use ($profile) {
            return (($value - $profile['MinValue']) / ($profile['MaxValue'] - $profile['MinValue'])) * 100;
        };

        $value = $valueToPercent(GetValue($variableID));

        // Revert value for reversed profile
        if (preg_match('/\.Reversed$/', $profileName)) {
            $value = 100 - $value;
        }

        return $value;
    }

    private static function dimDevice($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableCustomProfile'] != '') {
            $profileName = $targetVariable['VariableCustomProfile'];
        } else {
            $profileName = $targetVariable['VariableProfile'];
        }

        if (!IPS_VariableProfileExists($profileName)) {
            return false;
        }

        // Revert value for reversed profile
        if (preg_match('/\.Reversed$/', $profileName)) {
            $value = 100 - $value;
        }

        $profile = IPS_GetVariableProfile($profileName);

        if (($profile['MaxValue'] - $profile['MinValue']) <= 0) {
            return false;
        }

        if ($targetVariable['VariableCustomAction'] != 0) {
            $profileAction = $targetVariable['VariableCustomAction'];
        } else {
            $profileAction = $targetVariable['VariableAction'];
        }

        if ($profileAction < 10000) {
            return false;
        }

        $percentToValue = function ($value) use ($profile) {
            return (max(0, min($value, 100)) / 100) * ($profile['MaxValue'] - $profile['MinValue']) + $profile['MinValue'];
        };

        if ($targetVariable['VariableType'] == 1 /* Integer */) {
            $value = intval($percentToValue($value));
        } elseif ($targetVariable['VariableType'] == 2 /* Float */) {
            $value = floatval($percentToValue($value));
        } else {
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
