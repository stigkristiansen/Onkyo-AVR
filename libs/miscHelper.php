<?PHP

declare(strict_types=1);

require_once __DIR__ . '/../libs/semaphoreHelper.php';
require_once __DIR__ . '/../libs/capabilities.php';

trait ExecuteCommand {
    private function ExecuteCommand($Command, $Data) {
		$this->SendDebug( __FUNCTION__ , sprintf('The command to execute is: %s', $Command), 0);
		$this->SendDebug( __FUNCTION__ , sprintf('The command data is: %s', json_encode($Data)), 0);

		if($this->HasActiveParent()) {
			$command = [
				'Command' => $Command,
				'Data'	  => $Data
			];
			
			if($Command=='CAP' && $Data=='QSTN') {
				$this->SendDebug( __FUNCTION__ , 'Querying for capabilities...', 0);

				$capabilities = json_decode($this->SendDataToParent(json_encode(['DataID' => '{1CEDE467-DFFC-5466-5CDF-BBCA3966E657}', 'Buffer' => $command])), true);	

				if($this->Lock(Capabilities::BUFFER)) {
					$this->SetBuffer(Capabilities::BUFFER, serialize($capabilities));
					$this->Unlock(Capabilities::BUFFER);
				}

				$this->SendDebug( __FUNCTION__ , sprintf('Capabilites: %s', json_encode($capabilities)), 0);
				return;
			}
				
			$this->SendDataToParent(json_encode(['DataID' => '{1CEDE467-DFFC-5466-5CDF-BBCA3966E657}', 'Buffer' => $command]));
			$this->SendDebug( __FUNCTION__ , 'The command was sent', 0);
		} else {
			$this->SendDebug(__FUNCTION__ ,'The command was not sent. Parent instance(s) are not active', 0);	
		}
		
	}
}