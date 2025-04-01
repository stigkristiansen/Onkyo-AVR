<?php

trait ParentStatus {

    const PARENTID = 'ParentID';

    const STATUSMAPPING = [
        IS_ACTIVE => 'IS_ACTIVE',
        IS_INACTIVE => 'IS_INACTIVE'
    ];

    protected function HandleParentMessages($TimeStamp, $SenderID, $Message, $Data) {
        $this->SendDebug( __FUNCTION__ , sprintf('Received message "%s" from instance "%s" with data "%s"', (string)$Message, (string)$SenderID, json_encode($Data)), 0);
        switch ($Message) {
            case IM_CHANGESETTINGS: 
                $this->SendDebug(__FUNCTION__, 'The message was IM_CHANGESETTINGS', 0);
                
                $this->RegisterParent();
                
                if ($this->HasActiveParent()) {
                    $state = IS_ACTIVE;
                } else {
                    $state = IS_INACTIVE;
                }
                
                break;
            case IM_CHANGESTATUS:
                $this->SendDebug(__FUNCTION__, 'The message was IM_CHANGESTATUS', 0);
                $state = $Data[0];
                
                if($state>=200) {
                    $state = IS_INACTIVE; 
                }
                
                break;
            default:
                return;
        }
        if($state==IS_ACTIVE) {
            $this->GetCapabilities();
        }
        
        $this->SetStatus($state);
        $this->SendDebug(__FUNCTION__, sprintf('Changed the modules status to %s', self::STATUSMAPPING[$state]), 0);
    }

	protected function RegisterParent() {
        try {
			$parentID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		} catch(Exception $e) {
			$parentID = 0;
		}

        $savedParentID = $this->GetSavedParentID();

        $this->SendDebug(__FUNCTION__, sprintf('Earlier parent ID is %s',(string)$savedParentID), 0);
        $this->SendDebug(__FUNCTION__, sprintf('New retrieved parent ID is %s',(string)$parentID), 0);
				
        if ($parentID != $savedParentID) {
            if ($savedParentID > 0) {
                $this->SendDebug(__FUNCTION__, 'Unregistering IM_CHANGESETTINGS and IM_CHANGESTATUS', 0);
                $this->UnregisterMessage($savedParentID, IM_CHANGESETTINGS);
            }
            if ($parentID > 0) {
                $this->SendDebug(__FUNCTION__, 'Registering IM_CHANGESETTINGS and IM_CHANGESTATUS', 0);
                $this->RegisterMessage($parentID, IM_CHANGESETTINGS);
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
            self::Unlock(self::PARENTID);
        }
    }

}
    
