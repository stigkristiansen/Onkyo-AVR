<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/profileHelper.php';
require_once __DIR__ . '/../libs/zones.php';
require_once __DIR__ . '/../libs/capabilities.php';

class OnkyoAVRDevice extends IPSModule {
	use Profile;

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

		$profileName = 'OAVRD.Mute';
		$this->RegisterProfileBooleanEx($profileName, 'Speaker', '', '', [
			[true, 'Muted', '', -1],
			[false, 'Unmuted', '', -1]
		]);

		$this->RegisterVariableBoolean('AMT', 'Mute', $profileName, 3);
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

	private function ExecuteCommand($Ident, $Value) {
		$command = [
			'Command' => $Ident,
			'Data'	  => $Value
		];

		$this->SendDataToParent(json_encode(['DataID' => '{1CEDE467-DFFC-5466-5CDF-BBCA3966E657}', 'Buffer' => $command]));
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
				
				$this->SendDebug( __FUNCTION__ , sprintf('XML: %s', $capabilities->XML()), 0);

				$capabilities->Decode();

				$this->SendDebug( __FUNCTION__ , sprintf('Firmware: %s', $capabilities->FirmwareVersion), 0);

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