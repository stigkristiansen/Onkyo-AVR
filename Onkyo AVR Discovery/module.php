<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ISCP.php';

class OnkyoAVRDiscovery extends IPSModule {
		
	public function Create() {
		//Never delete this line!
		parent::Create();
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
	}

	private function InitiateDiscovery()	{

		$api = new ISCPCommand('ECN', 'QSTN', '!x');
		$this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $api->ToString()]));
	}

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		IPS_LogMessage('Received discovery data', $JSONString);

	}

}