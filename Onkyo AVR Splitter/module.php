<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ISCP.php';
require_once __DIR__ . '/../libs/semaphore.php';

class OnkyoAVRSplitter extends IPSModule {
		public function Create() {
			//Never delete this line!
			parent::Create();

			$this->ConnectParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');
		}

		public function Destroy() {
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();
		}

		public function ForwardData($JSONString) {
			$data = json_decode($JSONString);
			$this->SendDebug( __FUNCTION__ , sprintf('Received data for forwaring to IO-instance: %s', $data->Buffer), 0);

			$api = new ISCPCommand($data->Buffer);
			
			$result = $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $api->ToString()]));

			return $result;
		}

		public function ReceiveData($JSONString) {
			$data = json_decode($JSONString);
			$this->SendDebug( __FUNCTION__ , sprintf('Received data for from the IO-instance: %s', utf8_decode($data->Buffer)), 0);



			//$this->SendDataToChildren(json_encode(['DataID' => '{EF1FFC09-B63E-971C-8DC9-A2F6B37046F1}', 'Buffer' => $data->Buffer]));
		}
	}