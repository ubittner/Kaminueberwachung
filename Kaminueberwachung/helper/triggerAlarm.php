<?php

// Declare
declare(strict_types=1);

trait KUE_triggerAlarm
{

    public function CheckActualState()
    {
        // Check monitoring
        if (!$this->GetValue('Monitoring')) {
            return;
        }
        //$actualState = $this->GetValue('Status');
        $status = false;
        // Temperature sensor
        $temperatureSensor = $this->ReadPropertyInteger('TemperatureSensor');
        $actualTemperature = GetValueFloat($temperatureSensor);
        $this->SendDebug(__FUNCTION__, 'Aktuelle Temperatur: ' . (string)$actualTemperature . ' °C', 0);
        // Window status
        $windowStatus = $this->GetValue('WindowStatus');
        $this->SendDebug(__FUNCTION__, 'Aktueller Fensterstatus: ' . GetValueFormatted($this->GetIDForIdent('WindowStatus')), 0);
        // Windows are closed
        if (!$windowStatus) {
            // Threshold
            $threshold = $this->ReadPropertyFloat('Threshold');
            // Threshold is reached
            if ($actualTemperature >= $threshold) {
                $status = true;
            }
        }
        // Set new status
        $this->SetValue('Status', $status);
        $monitoringState = $this->GetValue('Status');
        // Only on alarm
        if ($monitoringState) {
            // Target variable
            $targetVariable = $this->ReadPropertyInteger('TargetVariable');
            if ($targetVariable != 0 && IPS_ObjectExists($targetVariable)) {
                $targetValue = boolval(GetValue($targetVariable));
                $toggleMode = $this->ReadPropertyBoolean('ToggleMode');
                if ($targetValue != $toggleMode) {
                    $toggle = @RequestAction($targetVariable, $toggleMode);
                    if (!$toggle) {
                        IPS_Sleep(150);
                        $toggle = @RequestAction($targetVariable, $toggleMode);
                    }
                    if (!$toggle) {
                        $this->LogMessage(__CLASS__ . ' Zielvariable konnte nicht ausgeführt werden', 10205);
                    }
                }
            }
            // Script
            $targetScript = $this->ReadPropertyInteger('TargetScript');
            if ($targetScript != 0 && IPS_ObjectExists($targetScript)) {
                $execute = @IPS_RunScriptEx($targetScript, ['Status' => intval($monitoringState)]);
                if (!$execute) {
                    // 2nd try
                    $execute = @IPS_RunScriptEx($targetScript, ['Status' => intval($monitoringState)]);
                }
                if (!$execute) {
                    $this->LogMessage(__CLASS__ . ' Zielskript konnte nicht ausgeführt werden', 10205);
                }
            }
        }
    }
}