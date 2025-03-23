<?php

trait InstanceStatus {

    const PARENTID = 'ParentID';

    protected function HandleInstanceMessages($TimeStamp, $SenderID, $Message, $Data) {
        $this->SendDebug( __FUNCTION__ , sprintf('Received message "%s" from instance "%s" with data "%s"', (string)$Message, (string)$SenderID, serialize($Data)), 0);
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

        $savedParentID = $this->GetSavedPatrentID();

        $this->SendDebug(__FUNCTION__, sprintf('Earlier parent ID is %s',(string)$savedParentID), 0);
        $this->SendDebug(__FUNCTION__, sprintf('New retrieved parent ID is %s',(string)$parentID), 0);
				
        if ($parentID != $savedParentID) {
            if ($this->parentID > 0) {
                $this->SendDebug(__FUNCTION__, 'Unregistering IM_CHANGESETTINGS and IM_CHANGESTATUS', 0);

                $this->UnregisterMessage($savedParentID, IM_CHANGESETTINGS);
                $this->UnregisterMessage($savedParentID, IM_CHANGESTATUS);
            }
            if ($parentID > 0) {
                $this->SendDebug(__FUNCTION__, 'Registering IM_CHANGESETTINGS and IM_CHANGESTATUS', 0);

                $this->RegisterMessage($parentID, IM_CHANGESETTINGS);
                $this->RegisterMessage($parentID, IM_CHANGESTATUS);
            } 

            $this->SaveParentID($parentID);
        }
    }

    protected function GetSavedParentID() : int {
        if(self::Lock(self::PARENTID)) {
            $parentID = (int)unserialize($this->GetBuffer(self::PARENTID));
            self::Unlock(self::PARENTID);

            return $parentID;
        }

        return 0;
    }

    protected function SaveParentID(int $ParentID) {
        if(self::Lock(self::PARENTID)) {
            $this->SetBuffer(self::PARENTID, serialize($ParentID));
        }
    }

}
    
