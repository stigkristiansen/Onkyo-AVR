<?php

trait InstanceStatus {
    protected function HandleInstanceMessages($TimeStamp, $SenderID, $Message, $Data) {
        this->SendDebug( __FUNCTION__ , sprintf('Received message "%s" from instance "%s" with data "%s"', (string)$Message, (string)$SenderID, serialize($Data)), 0);
        switch ($Message) {
            case IM_CHANGESETTINGS: 
            case FM_CONNECT:
                $this->RegisterParent();
                if ($this->HasActiveParent()) {
                    $state = IS_ACTIVE;
                } else {
                    $state = IS_INACTIVE;
                }
                break;
            case FM_DISCONNECT:
                $this->RegisterParent();
                $state = IS_INACTIVE;
                break;
            case IM_CHANGESTATUS:
                $state = $Data[0];
                break;
            default:
                return;
        }
        
		$this->SetStatus($state);
    }

	protected function RegisterParent() {
        try {
			$parentID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		} catch(Exception $e) {
			$parentID = 0;
		}

        $this->SendDebug(__FUNCTION__, sprintf('Parent ID is %s',(string)$parentID), 0);
				
        if ($parentID != $this->parentID) {
            if ($this->parentID > 0) {
                $this->SendDebug(__FUNCTION__, 'Unregistering IM_CHANGESETTINGS and IM_CHANGESTATUS', 0);

                $this->UnregisterMessage($this->parentID, IM_CHANGESETTINGS);
                $this->UnregisterMessage($this->parentID, IM_CHANGESTATUS);
            }
            if ($parentID > 0) {
                $this->SendDebug(__FUNCTION__, 'Registering IM_CHANGESETTINGS and IM_CHANGESTATUS', 0);

                $this->RegisterMessage($this->parentID, IM_CHANGESETTINGS);
                $this->RegisterMessage($this->parentID, IM_CHANGESTATUS);
            } 

            $this->parentID = $parentID;
        }
    }

}
    
