<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ISCP.php';

class OnkyoAVRDiscovery extends IPSModule {
		
	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');

		$this->SetBuffer('Devices', json_encode([]));
		$this->SetBuffer('DiscoveredDevices', json_encode([]));
		$this->SetBuffer('SearchInProgress', json_encode(false));

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
		return '{"BindIP":"'.gethostbyname(gethostname()).'","BindPort":1234,"EnableBroadcast":true,"EnableReuseAddress":true,"Host":"","Open":true,"Port":60128}';
	}

	public function GetConfigurationForm() {
		$this->SendDebug(__FUNCTION__, 'Generating the form...', 0);
		$this->SendDebug(__FUNCTION__, sprintf('SearchInProgress is "%s"', json_decode($this->GetBuffer('SearchInProgress'))?'TRUE':'FALSE'), 0);
					
		$devices = json_decode($this->GetBuffer('Devices'));
	   
		if (!json_decode($this->GetBuffer('SearchInProgress'))) {
			$this->SendDebug(__FUNCTION__, 'Setting SearchInProgress to TRUE', 0);
			$this->SetBuffer('SearchInProgress', json_encode(true));
			
			$this->SendDebug(__FUNCTION__, 'Starting a timer to process the search in a new thread...', 0);
			$this->RegisterOnceTimer('LoadDevicesTimer', 'IPS_RequestAction(' . (string)$this->InstanceID . ', "Discover", 0);');
		}

		$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		$form['actions'][0]['visible'] = count($devices)==0;
		
		$this->SendDebug(__FUNCTION__, 'Adding cached devices to the form', 0);
		$form['actions'][1]['values'] = $devices;

		$this->SendDebug(__FUNCTION__, 'Finished generating the form', 0);

		return json_encode($form);
	}

	public function RequestAction($Ident, $Value) {
		$this->SendDebug( __FUNCTION__ , sprintf('ReqestAction called for Ident "%s" with Value %s', $Ident, (string)$Value), 0);

		switch (strtolower($Ident)) {
			case 'discover':
				$this->SendDebug(__FUNCTION__, 'Calling LoadDevices()...', 0);
				$this->LoadDevices();
				break;
		}
	}

	private function InitiateDiscovery()	{
		$this->SendDebug( __FUNCTION__ , 'Sending the discovery message...', 0);

		$api = new ISCPCommand('ECN', 'QSTN', '!x');
		$this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $api->ToString()]));
	}

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);

		$this->SendDebug( __FUNCTION__ , sprintf('Received discovery data: %s', utf8_decode($data->Buffer)), 0);
		
		$discoveredData = utf8_decode($data->Buffer);

		if(substr($discoveredData, 0, 4) != 'ISCP') {
			$this->SendDebug( __FUNCTION__ , 'Invalid data. Data do not start with "ISCP"', 0);
			return;
		}

		$startPos = strpos($discoveredData, '!1ECN');
		if($startPos===false) {
			$this->SendDebug( __FUNCTION__ , 'Invalid discovery data or not an AVR. Data is missing "!1ECN"', 0);
			return;
		}

		$discoveredData = explode('/', substr($discoveredData, $startPos+5, -1));
		
		$model = $discoveredData[0];
		$devicePort = (int)$discoveredData[1];
		$macAddress = substr($discoveredData[3], 0, 12);
		$deviceIp = $data->ClientIP;

		$device = [
			'Model' => $model,
			'IPAddress' => $deviceIp,
			'Port' => $devicePort,	
		];
		
		$devices = json_decode($this->GetBuffer('DiscoveredDevices'), true);

		$devices[$macAddress] = $device;
		
		$this->SetBuffer('DiscoveredDevices', json_encode($devices));
		
		$this->SendDebug( __FUNCTION__ , sprintf('Discovered device: %s:%s:%s:%s', $deviceIp, $devicePort, $model, $macAddress), 0);
		
	}

	private function LoadDevices() {
		$this->SendDebug(__FUNCTION__, 'Updating Discovery form...', 0);

		$this->UpdateFormField('SearchingInfo', 'visible', true);

		$this->InitiateDiscovery();
				
		$instances = $this->GetInstances();

		//Wait for discovery to finish...
		$this->SendDebug(__FUNCTION__, 'Waiting for discovery to complete...', 0);
		IPS_Sleep(5000);

		$devices = $this->GetDiscoveredDevices();
		
		$this->SendDebug(__FUNCTION__, 'Setting SearchInProgress to FALSE', 0);
		$this->SetBuffer('SearchInProgress', json_encode(false));
		
		$values = [];
		
		// Add devices that are discovered
		if(count($devices)>0) {
			$this->SendDebug(__FUNCTION__, 'Adding discovered devices...', 0);
		} else {
			$this->SendDebug(__FUNCTION__, 'No devices discovered!', 0);
		}

		foreach($devices as $macAddress => $device) {
			
			$value = [
				'MacAddress' => $macAddress,
				'Model' => $device['Model'],
				'IPAddress' => $device['IPAddress'],
				'Port' => $device['Port'],
				'instanceID' => 0
			];

			$this->SendDebug(__FUNCTION__, sprintf('Added device with IP-address "%s"', $device['IPAddress']), 0);
			
			// Check if discovered device has an instance that is created earlier. If found, set InstanceID
			$instanceId = array_search($macAddress, $instances);
			if ($instanceId !== false) {
				$this->SendDebug(__FUNCTION__, sprintf('The device with MAC address %s already has an instance (%s). Setting InstanceId', $macAddress, $instanceId), 0);
				unset($instances[$instanceId]); // Remove from list to avoid duplicates
				$value['instanceID'] = $instanceId;
			} 

			$modules = [];

			$modules[] = [
				'moduleID'       => '{FF80DAC2-0BF3-6A70-F4A8-84A6DE34FDBA}',  
				'configuration'	 => [
					'MacAddress' 	=> $macAddress,
					'Model' 		=> $device['Model']				
				]
			];

			$modules[] =  [
				'moduleID' => '{CD39A489-D759-1786-1904-879A571231AF}',
				'info' => 'Splitter'
			];

			$modules[] = [
				'moduleID'       => '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}',  
				'info'			 => 'Client Socket IO',
				'configuration'	 => [
					'Host' 			=> $device['IPAddress'],
					'Open' 			=> true,
					'Port'			=> $device['Port'],
					'UseSSL'		=> false
				]
			];

			$value['create'] = $modules;
	
			$values[] = $value;
		
		}

		// Add devices that are not discovered, but created earlier
		if(count($instances)>0) {
			$this->SendDebug(__FUNCTION__, 'Adding instances that are not discovered, but created earlier...', 0);
		}
		foreach ($instances as $instanceId => $macAddress) {
			$ipAddress = '';
			$port = 0;
			$instanceInfo = IPS_GetInstance($instanceId);
			if(isset($instanceInfo['ConnectionID'])) {
				$instanceInfo = IPS_GetInstance($instanceInfo['ConnectionID']);
				if(isset($instanceInfo['ConnectionID'])) {
					$config = json_decode(IPS_GetConfiguration($instanceInfo['ConnectionID']), true);
					$ipAddress = $config['Host'];
					$port = $config['Port'];
				}
			}
			$parentID = IPS_GetParent
			$values[] = [
				'Model'		 	=> json_decode(IPS_GetConfiguration($instanceId),true)['Model'],
				'MacAddress'	=> $macAddress,
				'IPAddress'		=> $ipAddress,
				'Port'			=> $port;				
				'instanceID' 	=> $instanceId,
				'create'		=> ['moduleID' => '{FF80DAC2-0BF3-6A70-F4A8-84A6DE34FDBA}',
										'configuration' => [
											'Model' 	   => json_decode(IPS_GetConfiguration($instanceId),true)['Model'],
											'MacAddress'   => $macAddress
										]
								   ]
			];

			$this->SendDebug(__FUNCTION__, sprintf('Added instance "%s" with InstanceID "%s"', IPS_GetName($instanceId), $instanceId), 0);
		}

		$newDevices = json_encode($values);
		$this->SetBuffer('Devices', $newDevices);
					
		$this->UpdateFormField('Discovery', 'values', $newDevices);
		$this->UpdateFormField('SearchingInfo', 'visible', false);

		$this->SendDebug(__FUNCTION__, 'Updating Discovery form completed', 0);
	}

	private function GetDiscoveredDevices() : array {
		$discoveredDevices = $this->GetBuffer('DiscoveredDevices');
		$this->SendDebug(__FUNCTION__, sprintf('Discovered devices: %s', $discoveredDevices), 0);
		
		return json_decode($discoveredDevices, true);
	}

	private function GetInstances () : array {
		$instances = [];

		$this->SendDebug(__FUNCTION__, 'Searching for existing instances of Onkyo devices...', 0);

		$instanceIds = IPS_GetInstanceListByModuleID('{FF80DAC2-0BF3-6A70-F4A8-84A6DE34FDBA}');
		
		foreach ($instanceIds as $instanceId) {
			$instances[$instanceId] = IPS_GetProperty($instanceId, 'MacAddress');
		}

		$this->SendDebug(__FUNCTION__, sprintf('Found %d existing instance(s) of Onkyo devices', count($instances)), 0);
		$this->SendDebug(__FUNCTION__, 'Finished searching for existing Onkyo devices', 0);	

		return $instances;
	}
}