<?php

// Declare
declare(strict_types=1);

trait KUE_triggerAlarm
{
    /**
     * Checks the actual state.
     */
    public function CheckActualState()
    {
        $newState = false;
        // Actual state
        $actualState = $this->GetValue('Status');
        $this->SendDebug(__FUNCTION__, 'Aktueller Status: ' . $actualState, 0);
        // Temperature sensor
        $temperatureSensor = $this->ReadPropertyInteger('TemperatureSensor');
        if ($temperatureSensor == 0 || !IPS_ObjectExists($temperatureSensor)) {
            return;
        }
        $actualTemperature = GetValueFloat($temperatureSensor);
        $this->SendDebug(__FUNCTION__, 'Aktuelle Temperatur: ' . (string) $actualTemperature . ' °C', 0);
        // Window status
        $windowStatus = $this->GetValue('WindowStatus');
        $this->SendDebug(__FUNCTION__, 'Aktueller Fensterstatus: ' . GetValueFormatted($this->GetIDForIdent('WindowStatus')), 0);
        // Windows are closed
        if (!$windowStatus) {
            // Check Threshold
            $threshold = $this->ReadPropertyFloat('Threshold');
            // Threshold is reached
            if ($actualTemperature >= $threshold) {
                $newState = true;
            }
        }
        $this->SendDebug(__FUNCTION__, 'Neuer Status: ' . $newState, 0);
        // State changed
        if ($newState != $actualState) {
            $this->SendDebug(__FUNCTION__, 'Status hat sich geändert.', 0);
            // Set new status
            $this->SetValue('Status', $newState);
            // Target variable
            $targetVariable = $this->ReadPropertyInteger('TargetVariable');
            if ($targetVariable == 0 || !IPS_ObjectExists($targetVariable)) {
                return;
            }
            $targetValue = boolval(GetValue($targetVariable));
            $toggleMode = false;
            // Alarm
            if ($newState) {
                $this->SendDebug(__FUNCTION__, 'Status: ALARM', 0);
                // Set Attribute
                $this->WriteAttributeBoolean('OriginState', $targetValue);
                $toggleMode = $this->ReadPropertyBoolean('ToggleMode');
            }
            // OK
            if (!$newState) {
                $this->SendDebug(__FUNCTION__, 'Status: OK', 0);
                $revertOriginState = $this->GetValue('RevertOriginState');
                if ($revertOriginState) {
                    $toggleMode = $this->ReadAttributeBoolean('OriginState');
                }
            }
            if ($this->GetValue('Monitoring')) {
                // Toggle target variable
                if ($targetValue != $toggleMode) {
                    $this->SendDebug(__FUNCTION__, 'Steckdose wird geschaltet: ' . $toggleMode, 0);
                    $toggle = @RequestAction($targetVariable, $toggleMode);
                    if (!$toggle) {
                        // 2nd try
                        $toggle = @RequestAction($targetVariable, $toggleMode);
                    }
                    if (!$toggle) {
                        $this->LogMessage(__CLASS__ . ' Steckdose konnte nicht geschaltet werden', 10205);
                    }
                    if ($toggle && !$newState) {
                        $this->WriteAttributeBoolean('OriginState', $toggleMode);
                    }
                }
                // Script
                $targetScript = $this->ReadPropertyInteger('TargetScript');
                if ($targetScript != 0 && IPS_ObjectExists($targetScript)) {
                    $this->SendDebug(__FUNCTION__, 'Skript wird ausgeführt mit dem Status: ' . $newState, 0);
                    $execute = @IPS_RunScriptEx($targetScript, ['Status' => intval($newState)]);
                    if (!$execute) {
                        // 2nd try
                        $execute = @IPS_RunScriptEx($targetScript, ['Status' => intval($newState)]);
                    }
                    if (!$execute) {
                        $this->LogMessage(__CLASS__ . ' Skript konnte nicht ausgeführt werden', 10205);
                    }
                }
            }
        }
    }

    /**
     * Sets the origin state attribute of the target variable.
     *
     * @param bool $SystemStart
     */
    protected function SetOriginState(bool $SystemStart = false)
    {
        $targetVariable = $this->ReadPropertyInteger('TargetVariable');
        if ($targetVariable == 0 || !IPS_ObjectExists($targetVariable)) {
            return;
        }
        $actualState = $this->GetValue('Status');
        if (!$actualState || $SystemStart) {
            $originState = boolval(GetValue($targetVariable));
            $this->WriteAttributeBoolean('OriginState', $originState);
        }
    }

    /**
     * Shows the origin state of the target variable.
     */
    public function ShowOriginState()
    {
        var_dump($this->ReadAttributeBoolean('OriginState'));
    }
}