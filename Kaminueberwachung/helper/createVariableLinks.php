<?php

// Declare
declare(strict_types=1);

trait KUE_createVariableLinks
{
    /**
     * Creates a link of the temperature sensor.
     */
    protected function CreateTemperatureLink()
    {
        // Create link
        $temperatureSensor = $this->ReadPropertyInteger('TemperatureSensor');
        if ($temperatureSensor != 0 && IPS_ObjectExists($temperatureSensor)) {
            $linkID = @IPS_GetLinkIDByName('Aktuelle Temperatur', $this->InstanceID);
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
                IPS_SetName($linkID, 'Aktuelle Temperatur');
                IPS_SetParent($linkID, $this->InstanceID);
                IPS_SetPosition($linkID, 1);
                IPS_SetIcon($linkID, 'Temperature');
                IPS_SetLinkTargetID($linkID, $temperatureSensor);
            }
        }
        // Delete link
        if ($temperatureSensor == 0 || !IPS_ObjectExists($temperatureSensor)) {
            $linkID = @IPS_GetLinkIDByName('Aktuelle Temperatur', $this->InstanceID);
            if (is_int($linkID)) {
                IPS_DeleteLink($linkID);
            }
        }
    }

    /**
     * Creates a link of the target variable.
     */
    protected function CreateTargetVariableLink()
    {
        // Create
        $targetVariable = $this->ReadPropertyInteger('TargetVariable');
        if ($targetVariable != 0 && IPS_ObjectExists($targetVariable)) {
            $linkID = @IPS_GetLinkIDByName('Steckdose', $this->InstanceID);
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
                IPS_SetName($linkID, 'Steckdose');
                IPS_SetParent($linkID, $this->InstanceID);
                IPS_SetPosition($linkID, 5);
                IPS_SetIcon($linkID, 'Power');
                IPS_SetLinkTargetID($linkID, $targetVariable);
            }
        }
        // Delete link
        if ($targetVariable == 0 || !IPS_ObjectExists($targetVariable)) {
            $linkID = @IPS_GetLinkIDByName('Steckdose', $this->InstanceID);
            if (is_int($linkID)) {
                IPS_DeleteLink($linkID);
            }
        }
    }
}