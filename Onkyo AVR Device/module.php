<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/profileHelper.php';
require_once __DIR__ . '/../libs/semaphoreHelper.php';
require_once __DIR__ . '/../libs/zones.php';
require_once __DIR__ . '/../libs/capabilities.php';
require_once __DIR__ . '/../libs/parentStatus.php';
require_once __DIR__ . '/../libs/miscHelper.php';


class OnkyoAVRDevice extends IPSModule {
	use Profile;
	use Semaphore;
	use ParentStatus;
	use ExecuteCommand;

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		$this->ConnectParent('{CD39A489-D759-1786-1904-879A571231AF}');

		$this->RegisterPropertyString('MacAddress', '');
		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyInteger('Zone', Zones::MAIN);

		$this->SetBuffer(Capabilities::BUFFER, serialize([]));

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

			$this->Initialize(true);
        }

	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->SendDebug(__FUNCTION__, 'Kernel is ready. Initializing module', 0);
			
			$this->Initialize(true);
			
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
				case 'INITIALIZE':
					$this->Initialize();
					return;
			}
			
			$this->ExecuteCommand($Ident, $Value);

		} catch(Exception $e) {
			$msg = sprintf('An error occured. The error was: %s', $e->getMessage());
			$this->SendDebug( __FUNCTION__ , $msg, 0);	
			$this->LogMessage($msg, KL_WARNING);
		} 
	}

	private function Initialize(bool $Timer=false) {
		if($Timer) {
			$script = 'IPS_RequestAction(' . (string)$this->InstanceID . ', "Initialize",\'0\');';
			$this->RegisterOnceTimer('Initialize', $script);
			return;
		}

		IPS_Sleep(2000);

		$this->RegisterParent();
		$this->GetCapabilities();
		$this->CreateVariables();
		$this->QueryVariables();
	}

	private function QueryVariables(){
		$zone = $this->ReadPropertyInteger('Zone');	

		foreach(Zones::VARIABLES[$zone] as $ident => $variable) {
			$this->ExecuteCommand($ident, 'QSTN');
		}
	}

	private function GetCapabilities() {
		$this->ExecuteCommand('CAP', 'QSTN');
	}

	private function CreateVariables() {
		$zone = $this->ReadPropertyInteger('Zone');
		$position = 0;

		$this->SendDebug( __FUNCTION__ , sprintf('Creating the variables for zone "%s"', Zones::ZoneNames[$zone]), 0);	
		foreach(Zones::VARIABLES[$zone] as $ident => $variable) {
			$assoc = [];

			$profileName = $variable['Profile'];
			$icon = $variable['Icon'];
			$caption = $variable['Caption'];
			$enabled = $variable['Enabled'];
			$prefix = '';
			$suffix = '';
			$position++;

			$this->SendDebug( __FUNCTION__ , sprintf('Creating variable "%s"', $caption), 0);
			
			if(strpos($profileName, '~')===false) {
				
				$this->SendDebug( __FUNCTION__ , sprintf('The variable has a custom profile: %s', $profileName), 0);
				
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
					$this->SendDebug( __FUNCTION__ , sprintf('The profile has a associations: %s', json_encode($assoc)), 0);

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
							$this->RegisterProfileInteger($profileName, $icon, $prefix, $suffix, 0, 0, 0);
							break;
						case Zones::STRING:
							$this->RegisterProfileString($profileName, $icon, $prefix, $suffix);
							break;
					}
				}

				$this->SendDebug( __FUNCTION__ , 'The custom profile is registered', 0);
			}


			$this->SendDebug( __FUNCTION__ , 'Registering the variable...', 0);

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

			$this->SendDebug( __FUNCTION__ , 'The variable is registered', 0);
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
				$this->SendDebug( __FUNCTION__ , sprintf('Updating variable with ident "%s" to value "%s"', $command->Command, json_encode($command->Data)), 0);
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
	                
	

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		
		$this->SendDebug( __FUNCTION__ , sprintf('Received data: %s', $JSONString), 0);

		$commands = base64_encode(json_encode($data->Buffer));
		
		$this->SendDebug( __FUNCTION__ , 'Creating a timer to process incoming data in a new thread.', 0);

		$script = 'IPS_RequestAction(' . (string)$this->InstanceID . ', "ReceivedCommands",\''.$commands.'\');';
		$this->RegisterOnceTimer('ReceivedCommands', $script);
	}
}