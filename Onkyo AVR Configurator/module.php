<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/capabilities.php';
require_once __DIR__ . '/../libs/semaphoreHelper.php';
require_once __DIR__ . '/../libs/miscHelper.php';
require_once __DIR__ . '/../libs/zones.php';
	
class OnkyoAVRConfigurator extends IPSModule {
	use ExecuteCommand;
	use Semaphore;

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->RequireParent('{CD39A489-D759-1786-1904-879A571231AF}');
		
		$this->RegisterPropertyString('Model', '');
		//$this->RegisterPropertyString('MacAddress', '');
		
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
		$this->LogMessage('Discovering Onkyo capabilities...', KL_NOTIFY);

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

		$model = $this->ReadPropertyString('Model');
		
		$values = [];

		foreach($zones as $zoneId => $zone) {
			$value = [
				'Type' 		 => 'Zone',
				'Name'		 => $zone['Name'],
				'instanceID' => 0
			];

			$this->SendDebug(__FUNCTION__, sprintf('Added zone: %s', $zone['Name']), 0);
						
			// Check if discovered entity has an instance that is created earlier. If found, set InstanceID
			$needle = $zoneId;
			$instanceId = array_search($needle, $instances);

			if ($instanceId !== false) {
				$this->SendDebug(__FUNCTION__, sprintf('The module for zone %s already has an instance (%s). Setting InstanceId', Zones::Zones[$zoneId]['Name'], (string)$instanceId), 0);
				//unset($instances[$instanceId]); // Remove from list to avoid duplicates
				$value['instanceID'] = $instanceId;
			} 

			$modules = [];

			$modules[] = [
				'moduleID'       => '{FF80DAC2-0BF3-6A70-F4A8-84A6DE34FDBA}',  
				'configuration'	 => [
					'Model' 		=> $model,	
					'Zone'			=> $zoneId
				]
			];

			$modules[] =  [
				'moduleID' => '{CD39A489-D759-1786-1904-879A571231AF}',
				'info' => 'Splitter'
			];

			$modules[] = [
				'moduleID'       => '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}',  
				'info'			 => 'Client Socket IO',
			
			];

			$value['create'] = $modules;
	
			$values[] = $value;
		}

		$this->SendDebug(__FUNCTION__, 'Completing the form...', 0);
		$form['actions'][0]['values'] = $values;

		return json_encode($form);
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

		$this->SendDebug(__FUNCTION__, sprintf('Found %d instance(s) before filtering by ip-address', count($instanceIds)), 0);

		$ipAddress = $this->GetIpAddressById($this->InstanceID);
		
		if($ipAddress!==false) {
			$this->SendDebug(__FUNCTION__, sprintf('The configurators ip-address is: %s', $ipAddress), 0);

			foreach ($instanceIds as $instanceId) {
				$instanceIpAddress = $this->GetIpAddressById($instanceId);
				$this->SendDebug(__FUNCTION__, sprintf('Found instance ip-address is: %s', $instanceIpAddress), 0);
				if($instanceIpAddress!==false && $ipAddress==$instanceIpAddress) {
					$instances[$instanceId] = IPS_GetProperty($instanceId, 'Zone');
				}
			}
	
			$this->SendDebug(__FUNCTION__, sprintf('Found %d existing instance(s) of Onkyo devices after filtering by ip-address', count($instances)), 0);
			$this->SendDebug(__FUNCTION__, 'Finished searching for existing Onkyo devices', 0);	
		} else {
			$this->SendDebug(__FUNCTION__, sprintf('Unable to retrive configurators ip-address via splitter and io-instance!', $ipAddress), 0);
		}
		
		return $instances;
	}

	protected function GetIpAddressById(int $InstanceId) : mixed {
		$properties = @IPS_GetInstance($InstanceId); 
		$moduleId = $properties['ModuleInfo']['ModuleID'];
	   
		if($moduleId=='{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}') {  // Client socket GUID
			return IPS_GetProperty($InstanceId, 'Host');
		} else {
			$parent = $properties['ConnectionID']; 
			if($parent!=0) {
				return self::GetIpAddressById($parent);
			} else {
				return false;
			}
		}
	}
	
}