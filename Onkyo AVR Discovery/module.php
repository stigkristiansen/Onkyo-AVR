<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ISCP.php';

class OnkyoAVRDiscovery extends IPSModule {
		
	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
	}
	
	public function GetConfigurationForParent() {
		return '{"BindPort":1234,"EnableBroadcast":true,"EnableReuseAddress":true,"Host":"","Open":true,"Port":60128}';
	}

	private function InitiateDiscovery()	{

		$api = new ISCPCommand('ECN', 'QSTN', '!x');
		$this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $api->ToString()]));
	}

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);

		IPS_LogMessage('Onkyo Discovery', utf8_decode($data->Buffer));
		
		$discoveredData = utf8_decode($data->Buffer);

		if(substr($discoveredData, 0, 4) != 'ISCP') {
			IPS_LogMessage('Onkyo Discovery', 'Invalid data. Data do not start with "ISCP"');
			return;
		}

		$startPos = strpos($discoveredData, '!1ECN');
		if($startPos===false) {
			IPS_LogMessage('Onkyo Discovery', 'Invalid data or not an Onkyo AVR');
			return;
		}

		$discoveredData = explode('/', substr($discoveredData, $startPos+5, -1));
		
		$model = $discoveredData[0];
		$devicePort = $discoveredData[1];
		$deviceIp = $data->ClientIP;

		IPS_LogMessage('Onkyo Discovery', $model.'|'.$deviceIp.'|'.$devicePort);
	}

}