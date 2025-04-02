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
		$this->SetReceiveDataFilter("NeverReceiveData");
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


		$this->GetCapabilities();


	}

	private function GetCapabilities() {
		IPS_Sleep(2000);
		$this->ExecuteCommand('CAP', 'QSTN');
	}
}