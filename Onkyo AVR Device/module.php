<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ISCP.php';


class OnkyoAVRDevice extends IPSModule {
	public function Create()
	{
		//Never delete this line!
		parent::Create();

		$this->RequireParent('{CD39A489-D759-1786-1904-879A571231AF}');

		$this->RegisterPropertyString('MacAddress', '');
		$this->RegisterPropertyString('Model', '');
	}

	public function Destroy()
	{
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
				'RECEIVEDCOMMANDS':
					break;
				default:
					throw new Exeption(sprintf('Unknown Ident: %s', $Ident));
			}
		} catch(Exception $e) {
			$msg = sprintf('An unexpected error occured. The error was: %s', $e->getMessage());
			$this->SendDebug( __FUNCTION__ , $msg, 0);	
			$this->LogMessage($msg, KL_WARNING);
		} 
	}


	public function Send() {
		$command = [
			'Command' => 'PWR',
			'Data'	  => true
		];

		$this->SendDataToParent(json_encode(['DataID' => '{1CEDE467-DFFC-5466-5CDF-BBCA3966E657}', 'Buffer' => $command]));
	}

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		
		$this->SendDebug( __FUNCTION__ , sprintf('Received data: %s', $JSONString), 0);

		$commands = json_encode($data->Buffer);
		
		$this->SendDebug( __FUNCTION__ , 'Creating a timer to process incoming data in a new thread.', 0);

		$script = 'IPS_RequestAction(' . (string)$this->InstanceID . ', "ReceivedCommands",\''.$commands.'\');';
		$this->RegisterOnceTimer('ReceivedCommands', $script);
	}
}