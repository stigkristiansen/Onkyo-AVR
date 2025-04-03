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
		
		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('MacAddress', '');
		
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
				$this->SendDebug(__FUNCTION__, sprintf('The module with MAC address %s and $zone already has an instance (%s). Setting InstanceId', $macAddress, $zoneId, $instanceId), 0);
				unset($instances[$instanceId]); // Remove from list to avoid duplicates
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

		return json_encode($values);
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

		$ipAddress = $this->GetIpAddressById($this->InstanceID);
		
		if($ipAddress!==false) {
			foreach ($instanceIds as $instanceId) {
				$instanceIpAddress = $this->GetIpAddressById($instanceId);
				if($instanceIpAddress!==false && $ipAddress==$instanceIpAddress) {
					$instances[$instanceId] = IPS_GetProperty($instanceId, 'Zone');
				}
			}
	
			$this->SendDebug(__FUNCTION__, sprintf('Found %d existing instance(s) of Onkyo devices', count($instances)), 0);
			$this->SendDebug(__FUNCTION__, 'Finished searching for existing Onkyo devices', 0);	
		}
		
		return $instances;
	}

	protected function GetIpAddressById(int $InstanceID) : mixed {
		$splitterId = @IPS_GetInstance($InstanceID)['ConnectionID'];
		if ($splitterId != 0) {
			$ioId = @IPS_GetInstance($splitterId)['ConnectionID'];
			if($ioId!=0) { //Client socket
				$ioModuleId = @IPS_GetInstance($InstanceID)['ModuleInfo']['ModuleID'];
				if($ioModuleId=='{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}') {
					return IPS_GetProperty($ioId, 'Host');
				} else {
					return false;	
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}