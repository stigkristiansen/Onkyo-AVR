<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/capabilities.php';
require_once __DIR__ . '/../libs/semaphoreHelper.php';
require_once __DIR__ . '/../libs/miscHelper.php';
	
class OnkyoAVRConfigurator extends IPSModule {
	use ExecuteCommand;
	use Semaphore;

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->RequireParent('{CD39A489-D759-1786-1904-879A571231AF}');

		$this->RegisterPropertyString('MacAddress', '');
		$this->RegisterPropertyString('Model', '');

		$this->SetReceiveDataFilter("NeverReceiveData");

		$this->SetBuffer(Capabilities::BUFFER, serialize([]));
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
	}

	public function GetConfigurationForm() {
		$this->SendDebug(__FUNCTION__, 'Generating the form...', 0);

		$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

		if (!$this->HasActiveParent()) {
			$form['actions'][] = [
				'name' => 'NoActiveParent',
				'type'  => 'PopupAlert',
				'popup' => [
					'items' => [
						[
							'type'    => 'Label',
							'caption' => 'This instance has no active parent(s).',
						]
					]
				]
			];
			
			return json_encode($form);
		}

		$instances = $this->GetInstances();

		$zones = $this->GetZones();

		foreach($zones as $id => $zone) {
			$this->SendDebug(__FUNCTION__, sprintf('Adding zone: %s', $zone['Name']), 0);
			
			$value = [
				'Type' => 'Zone',
				'Name' => $zone['Name'],
				'instanceID' => 0
			];
		}

		

	}

	private function GetZones() : array {
		IPS_Sleep(2000);
		
		$this->ExecuteCommand('CAP', 'QSTN');
		$capabilities = unserialize($this->GetBuffer(Capabilities::BUFFER));
		
		if(isset($capabilities['ZoneList'])) {
			$this->SendDebug(__FUNCTION__, sprintf('Available zones on Onkyo device are: %s', json_encode($capabilities['ZoneList'])), 0);

			return $capabilities['ZoneList'];
		}
		
		return [];
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