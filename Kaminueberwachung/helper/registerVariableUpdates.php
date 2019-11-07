<?php

// Declare
declare(strict_types=1);

trait KUE_registerVariableUpdates
{
    /**
     * Unregisters all variable updates from message sink.
     */
    protected function UnregisterVariableUpdates()
    {
        $registeredVariables = $this->GetMessageList();
        if (!empty($registeredVariables)) {
            foreach ($registeredVariables as $id => $registeredVariable) {
                foreach ($registeredVariable as $messageType) {
                    if ($messageType == VM_UPDATE) {
                        $this->UnregisterMessage($id, VM_UPDATE);
                    }
                }
            }
        }
    }

    /**
     * Registers variable updates for message sink.
     */
    protected function RegisterVariableUpdates()
    {
        // Unregister all variable updates first
        $this->UnregisterVariableUpdates();
        // Register existing temperature sensor
        $temperatureSensor = $this->ReadPropertyInteger('TemperatureSensor');
        if ($temperatureSensor != 0 && IPS_ObjectExists($temperatureSensor)) {
            $this->RegisterMessage($temperatureSensor, VM_UPDATE);
        }
        // Register window sensors
        $windowSensors = json_decode($this->ReadPropertyString('WindowSensors'));
        if (empty($windowSensors)) {
            return;
        }
        foreach ($windowSensors as $windowSensor) {
            $use = $windowSensor->UseSensor;
            $id = $windowSensor->ID;
            if ($use) {
                if ($id != 0 && IPS_ObjectExists($id)) {
                    $this->RegisterMessage($id, VM_UPDATE);
                }
            }
        }
        // Register target variable
        $targetVariable = $this->ReadPropertyInteger('TargetVariable');
        if ($targetVariable != 0 && IPS_ObjectExists($targetVariable)) {
            $this->RegisterMessage($targetVariable, VM_UPDATE);
        }
    }

    /**
     * Shows the registered variable updates.
     */
    public function ShowMessageSink()
    {
        $registeredVariableUpdates = [];
        $registeredVariables = $this->GetMessageList();
        foreach ($registeredVariables as $id => $registeredVariable) {
            foreach ($registeredVariable as $messageType) {
                if ($messageType == VM_UPDATE) {
                    array_push($registeredVariableUpdates, ['id' => $id, 'name' => IPS_GetName($id)]);
                }
            }
        }
        sort($registeredVariableUpdates);
        echo "\n\nRegistrierte Variablen:\n\n";
        print_r($registeredVariableUpdates);
    }
}