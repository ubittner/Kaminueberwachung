<?php

// Declare
declare(strict_types=1);

trait KUE_checkWindowSensors
{
    /**
     * Checks the state of the window sensors.
     */
    public function CheckWindowSensors()
    {
        $windowStatus = false;
        // Check for existing window sensors
        $windowSensors = json_decode($this->ReadPropertyString('WindowSensors'));
        $hide = false;
        if (empty($windowSensors)) {
            $windowStatus = true;
            $hide = true;
        }
        @IPS_SetHidden($this->GetIDForIdent('WindowStatus'), $hide);
        if (!empty($windowSensors)) {
            foreach ($windowSensors as $windowSensor) {
                if ($windowSensor->UseSensor) {
                    if (boolval(GetValue($windowSensor->ID))) {
                        $windowStatus = true;
                    }
                }
            }
        }
        $this->SetValue('WindowStatus', $windowStatus);
        $this->CheckActualState();
    }
}