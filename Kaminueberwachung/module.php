<?php

/*
 * @module      Kaminüberwachung
 *
 * @prefix      KUE
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2019
 * @license    	CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @version     1.00-1
 * @date:       2019-11-06, 18:00
 *
 * @see         https://github.com/ubittner/Kaminueberwachung/
 *
 * @guids       Library
 *              {C23D1F38-4D3F-B923-1993-0FA3B35E265D}
 *
 *              Kaminüberwachung
 *             	{4021F6F3-E0C2-7742-BF37-49D55703406D}
 *
 */

// Declare
declare(strict_types=1);

// Include
include_once __DIR__ . '/helper/autoload.php';

// Class
class Kaminueberwachung extends IPSModule
{
    // Traits
    use KUE_checkWindowSensors;
    use KUE_createVariableLinks;
    use KUE_registerVariableUpdates;
    use KUE_triggerAlarm;

    public function Create()
    {
        // Never delete this line!
        parent::Create();

        //#################### Register properties

        // Temperature sensor
        $this->RegisterPropertyInteger('TemperatureSensor', 0);
        $this->RegisterPropertyFloat('Threshold', 30.0);

        // Window sensors
        $this->RegisterPropertyString('WindowSensors', '[]');

        // Control
        $this->RegisterPropertyInteger('TargetVariable', 0);
        $this->RegisterPropertyBoolean('ToggleMode', false);
        $this->RegisterPropertyBoolean('RevertOriginalState', false);
        $this->RegisterPropertyInteger('TargetScript', 0);

        //#################### Create profiles

        // Window status
        $profileName = 'KUE.' . $this->InstanceID . '.WindowStatus';
        if (!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 0);
        }
        IPS_SetVariableProfileAssociation($profileName, 0, 'Geschlossen', 'Window', 0x00FF00);
        IPS_SetVariableProfileAssociation($profileName, 1, 'Geöffnet', 'Window', 0x0000FF);

        // Status
        $profileName = 'KUE.' . $this->InstanceID . '.Status';
        if (!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 0);
        }
        IPS_SetVariableProfileAssociation($profileName, 0, 'OK', 'Information', 0x00FF00);
        IPS_SetVariableProfileAssociation($profileName, 1, 'ALARM', 'Warning', 0xFF0000);

        //################### Register attributes

        // Origin state
        $this->RegisterAttributeBoolean('OriginState', false);
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Never delete this line!
        parent::ApplyChanges();

        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //#################### Register variables

        // Monitoring
        $this->MaintainVariable('Monitoring', 'Kaminüberwachung', 0, '~Switch', 0, true);
        $this->EnableAction('Monitoring');

        // Create temperature link
        $this->CreateTemperatureLink();

        // Window status
        $profileName = 'KUE.' . $this->InstanceID . '.WindowStatus';
        $this->MaintainVariable('WindowStatus', 'Fensterstatus', 0, $profileName, 2, true);

        // State
        $profileName = 'KUE.' . $this->InstanceID . '.Status';
        $this->MaintainVariable('Status', 'Allgemeiner Status', 0, $profileName, 3, true);

        // Origin state
        $this->MaintainVariable('RevertOriginState', 'Schaltzustand wiederherstellen', 0, '~Switch', 4, true);
        $this->EnableAction('RevertOriginState');
        IPS_SetIcon($this->GetIDForIdent('RevertOriginState'), 'Plug');

        // Create target variable link
        $this->CreateTargetVariableLink();

        // Register variable updates
        $this->RegisterVariableUpdates();

        // Set origin state
        $this->SetOriginState(true);

        // Check door window sensors
        $this->CheckWindowSensors();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();

        // Delete profiles
        $this->DeleteProfiles();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;
            case VM_UPDATE:
                // Temperature sensor
                $temperatureSensor = $this->ReadPropertyInteger('TemperatureSensor');
                if ($temperatureSensor != 0 && IPS_ObjectExists($temperatureSensor)) {
                    if ($SenderID === $temperatureSensor) {
                        $this->CheckActualState();
                    }
                }
                // Window sensors
                $windowSensors = json_decode($this->ReadPropertyString('WindowSensors'), true);
                if (!empty($windowSensors)) {
                    if (array_search($SenderID, array_column($windowSensors, 'ID')) !== false) {
                        $this->CheckWindowSensors();
                    }
                }
                // Target variable
                $targetVariable = $this->ReadPropertyInteger('TargetVariable');
                if ($targetVariable != 0 && IPS_ObjectExists($targetVariable)) {
                    if ($SenderID === $targetVariable) {
                        $this->SetOriginState(false);
                    }
                }
                break;
        }
    }

    //#################### Public

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Monitoring':
                $this->SetValue('Monitoring', $Value);
                $this->CheckWindowSensors();
                break;
            case 'RevertOriginState':
                $this->SetValue('RevertOriginState', $Value);
                break;
        }
    }

    //#################### Protected

    /**
     * Applies changes when the kernel is ready.
     */
    protected function KernelReady()
    {
        $this->ApplyChanges();
    }

    /**
     * Deletes the profiles.
     */
    protected function DeleteProfiles()
    {
        $profiles = ['WindowStatus', 'Status'];
        foreach ($profiles as $profile) {
            $profileName = 'KUE.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }
}