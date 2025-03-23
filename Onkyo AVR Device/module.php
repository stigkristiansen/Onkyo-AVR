<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/profileHelper.php';
require_once __DIR__ . '/../libs/zones.php';
require_once __DIR__ . '/../libs/capabilities.php';
require_once __DIR__ . '/../libs/instanceStatus.php';

class OnkyoAVRDevice extends IPSModule {
	use Profile;
	use InstanceStatus;

	private $parentID=0;

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		$this->RequireParent('{CD39A489-D759-1786-1904-879A571231AF}');

		$this->RegisterPropertyString('MacAddress', '');
		$this->RegisterPropertyString('Model', '');

		$this->RegisterVariableBoolean('PWR', 'Power', '~Switch', 1);
		$this->EnableAction('PWR');

		$this->RegisterVariableInteger('MVL', 'Volume', '~Intensity.100', 2);
		$this->EnableAction('MVL');

		$this->RegisterVariableInteger('SLI', 'Input', '', 3);
		$this->EnableAction('SLI');

		$profileName = 'OAVRD.Mute';
		$this->RegisterProfileBooleanEx($profileName, 'Speaker', '', '', [
			[true, 'Muted', '', -1],
			[false, 'Unmuted', '', -1]
		]);

		$this->RegisterVariableBoolean('AMT', 'Mute', $profileName, 4);
		$this->EnableAction('AMT');

	}

	public function Destroy() {
		$module = json_decode(file_get_contents(__DIR__ . '/module.json'));
		if(count(IPS_GetInstanceListByModuleID($module->id))==0) {
			$this->DeleteProfile('OAVRD.Mute');
		}

		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		$this->SendDebug(__FUNCTION__, 'Applying changes', 0);

		if (IPS_GetKernelRunlevel() == KR_READY) {
			$this->SendDebug(__FUNCTION__, 'Registering FM_CONNECT and FM_DISCONNECT', 0);

			$this->RegisterMessage($this->InstanceID, FM_CONNECT);
			$this->RegisterMessage($this->InstanceID, FM_DISCONNECT);    

			$this->RegisterParent();
        }
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

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->RegisterMessage($this->InstanceID, FM_CONNECT);
			$this->RegisterMessage($this->InstanceID, FM_DISCONNECT);

			$this->RegisterParent();
		}

		$this->HandleInstanceMessages($TimeStamp, $SenderID, $Message, $Data);
    }

	private function ExecuteCommand($Ident, $Value) {
		$command = [
			'Command' => $Ident,
			'Data'	  => $Value
		];

		if($this->HasActiveParent()) {
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
			$this->SendDebug( __FUNCTION__ , sprintf('Decoded the data. Command "%s" with data "%s"', $command->Command, $command->Data), 0);
			
			if($this->ValidIdent($command->Command, Zones::MAIN)) {
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

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		
		$this->SendDebug( __FUNCTION__ , sprintf('Received data: %s', $JSONString), 0);

		$commands = base64_encode(json_encode($data->Buffer));
		
		$this->SendDebug( __FUNCTION__ , 'Creating a timer to process incoming data in a new thread.', 0);

		$script = 'IPS_RequestAction(' . (string)$this->InstanceID . ', "ReceivedCommands",\''.$commands.'\');';
		$this->RegisterOnceTimer('ReceivedCommands', $script);
	}
}