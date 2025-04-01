<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/profileHelper.php';
require_once __DIR__ . '/../libs/semaphoreHelper.php';
require_once __DIR__ . '/../libs/zones.php';
require_once __DIR__ . '/../libs/capabilities.php';
require_once __DIR__ . '/../libs/parentStatus.php';



class OnkyoAVRDevice extends IPSModule {
	use Profile;
	use Semaphore;
	use ParentStatus;

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		$this->RequireParent('{CD39A489-D759-1786-1904-879A571231AF}');

		$this->RegisterPropertyString('MacAddress', '');
		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyInteger('Zone', Zones::MAIN);

	}

	public function Destroy() {
		$module = json_decode(file_get_contents(__DIR__ . '/module.json'));
		if(count(IPS_GetInstanceListByModuleID($module->id))==0) {
			$this->DeleteProfile('OAVRD.Mute');
		}

		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

		$this->SendDebug(__FUNCTION__, 'Applying changes', 0);

		if (IPS_GetKernelRunlevel() == KR_READY) {
			$this->SendDebug(__FUNCTION__, 'Kernel is ready. Initializing module', 0);

			$this->RegisterParent();

			$this->GetCapabilities();

			$this->CreateVariables();
        }

		/*$this->RegisterVariableBoolean('PWR', 'Power', '~Switch', 1);
		$this->EnableAction('PWR');

		$this->RegisterVariableInteger('MVL', 'Volume', '~Intensity.100', 2);
		$this->EnableAction('MVL');

		$this->RegisterVariableInteger('SLI', 'Input', '', 3);
		$this->EnableAction('SLI');

		$profileName = 'OAVRD.Mute';
		$this->RegisterProfileBooleanEx($profileName, 'Speaker', '', '', Zones::VARIABLES[Zones::MAIN]['AMT']['Assoc']);

		$this->RegisterVariableBoolean('AMT', 'Mute', $profileName, 4);
		$this->EnableAction('AMT');
*/
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->SendDebug(__FUNCTION__, 'Kernel is ready. Initializing module', 0);
			
			$this->RegisterParent();

			$this->GetCapabilities();

			

			return;
		}

		$this->HandleParentMessages($TimeStamp, $SenderID, $Message, $Data);
    }

	public function RequestAction($Ident, $Value) {
		$this->SendDebug(__FUNCTION__, sprintf('RequestAction was called: %s:%s', (string)$Ident, (string)$Value), 0);
		
		try {
			switch (strtoupper($Ident)) {
				case 'RECEIVEDCOMMANDS':
					$this->HandleCommands($Value);
					return;
			}
			
			$this->ExecuteCommand($Ident, $Value);

		} catch(Exception $e) {
			$msg = sprintf('An error occured. The error was: %s', $e->getMessage());
			$this->SendDebug( __FUNCTION__ , $msg, 0);	
			$this->LogMessage($msg, KL_WARNING);
		} 
	}

	private function CreateVariables() {
		$zone = $this->ReadPropertyInteger('Zone');
		$position = 0;
		foreach(Zones::VARIABLES[$zone] as $ident => $variable) {
			$assoc = [];

			$profileName = $variable['Profile'];
			$icon = $variable['Icon'];
			$caption = $variable['Caption'];
			$enabled = $variable['Enabled'];
			$prefix = '';
			$suffix = '';
			$position++;
			
			if(strpos($variable['Profile'], '~')===false) {
				if(is_string($variable['Assoc'])) {
					$capabilities = unserialize($this->GetBuffer(Capabilities::BUFFER));
					if(count($capabilities)>0) {
						$zones = new Zones();
						$assoc = $zones->GetAssocArray($variable['Assoc'], $capabilities, $zone);
					} else {
						$assoc = [];
					}
				} else {
					$assoc = $variable['Assoc'];
				}
				
				if(count($assoc)>0) {
					switch ($variable['Type']) {
						case Zones::BOOLEAN:
							$this->RegisterProfileBooleanEx($profileName, $icon, $prefix, $suffix, $assoc);
							break;
						case Zones::INTEGER:
							$this->RegisterProfileIntegerEx($profileName, $icon, $prefix, $suffix, $assoc);
							break;
						case Zones::STRING:
							$this->RegisterProfileStringEx($profileName, $icon, $prefix, $suffix, $assoc);
							break;
					}
				} else {
					switch ($variable['Type']) {
						case Zones::BOOLEAN:
							$this->RegisterProfileBoolean($profileName, $icon, $prefix, $suffix);
							break;
						case Zones::INTEGER:
							$this->RegisterProfileInteger($profileName, $icon, $prefix, $suffix);
							break;
						case Zones::STRING:
							$this->RegisterProfileString($profileName, $icon, $prefix, $suffix);
							break;
					}
				}
			}

			switch ($variable['Type']) {
				case Zones::BOOLEAN:
					$this->RegisterVariableBoolean($ident, $caption, $profileName, $position);
					break;
				case Zones::INTEGER:
					$this->RegisterVariableInteger($ident, $caption, $profileName, $position);
					break;
				case Zones::STRING:
					$this->RegisterVariableString($ident, $caption, $profileName, $position);
					break;
			}

			$enabled?$this->EnableAction($ident):$this-D>isableAction($ident);
		}

		

	}

	private function ExecuteCommand($Command, $Data) {
		
		if($this->HasActiveParent()) {
			$command = [
				'Command' => $Command,
				'Data'	  => $Data
			];
			
			if($Command=='CAP' && $Data=='QSTN') {
				$this->SendDebug( __FUNCTION__ , 'Querying for capabilities...', 0);

				$capabilities = $this->SendDataToParent(json_encode(['DataID' => '{1CEDE467-DFFC-5466-5CDF-BBCA3966E657}', 'Buffer' => $command]));	

				if($this->Lock(Capabilities::BUFFER)) {
					$this->SetBuffer(Capabilities::BUFFER, serialize($capabilities));
					$this->Unlock(Capabilities::BUFFER);
				}

				$this->SendDebug( __FUNCTION__ , sprintf('Capabilites: %s', $capabilities), 0);
				return;
			}
				
			$this->SendDataToParent(json_encode(['DataID' => '{1CEDE467-DFFC-5466-5CDF-BBCA3966E657}', 'Buffer' => $command]));
		} else {
			$this->SendDebug(__FUNCTION__ , 'The command was not sent. Parent instances are not active', 0);	
		}
		
	}

	private function ValidIdent($Ident, $Zone) {
		if(isset(Zones::VARIABLES[$Zone][$Ident])) {
			return true;
		}

		return false;
	}

	private function HandleCommands($Commands) {
		$commands = json_decode(base64_decode($Commands));
		
		foreach($commands as $command) {
			$this->SendDebug( __FUNCTION__ , sprintf('Decoded the data. Command "%s" with data "%s"', $command->Command, json_encode($command->Data)), 0);
			
			if($this->ValidIdent($command->Command,  $this->ReadPropertyInteger('Zone'))) {
				$this->SendDebug( __FUNCTION__ , sprintf('Updating variable with ident "%s" to value "%s"', $command->Command, $command->Data), 0);
				$this->SetValue($command->Command, $command->Data);
				return;
			} 

			if($command->Command=='NRI') {
				$capabilities = new Capabilities($command->Data);
				
				if($capabilities->Decode()){
					$this->SendDebug( __FUNCTION__ , sprintf('Firmware: %s', $capabilities->FirmwareVersion), 0);
					
				} else {
					$this->SendDebug( __FUNCTION__ , 'XML decode failed!', 0);
				}
				return;
			}
		}
	}

	public function Send() {
		$this->ExecuteCommand('NRI', 'QSTN');
	}
	                
	public function GetCapabilities() {
		$this->ExecuteCommand('CAP', 'QSTN');
	}

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		
		$this->SendDebug( __FUNCTION__ , sprintf('Received data: %s', $JSONString), 0);

		$commands = base64_encode(json_encode($data->Buffer));
		
		$this->SendDebug( __FUNCTION__ , 'Creating a timer to process incoming data in a new thread.', 0);

		$script = 'IPS_RequestAction(' . (string)$this->InstanceID . ', "ReceivedCommands",\''.$commands.'\');';
		$this->RegisterOnceTimer('ReceivedCommands', $script);
	}
}