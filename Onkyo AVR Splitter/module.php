<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ISCP.php';
require_once __DIR__ . '/../libs/semaphoreHelper.php';

class OnkyoAVRSplitter extends IPSModule {
	use Semaphore;

	const BUFFER = 'Incoming';

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ConnectParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->SetBuffer(self::BUFFER, serialize(''));
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
		$command = json_encode($data->Buffer);

		$this->SendDebug( __FUNCTION__ , sprintf('Received data for forwaring to IO-instance: %s', $command), 0);

		$api = new ISCPCommand($command);
		
		$result = $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $api->ToString()]));

		return $result;
	}

	public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		$stream = utf8_decode($data->Buffer);
		
		$this->SendDebug( __FUNCTION__ , sprintf('Received data for from the IO-instance: %s', $stream), 0);

		if(self::Lock(self::BUFFER)) {
			$buffer = unserialize($this->GetBuffer(self::BUFFER));
			$bufferLength = strlen($buffer);

			$this->SendDebug( __FUNCTION__ , sprintf('Saved buffer: %s', strlen($buffer)>0?$buffer:'<EMPTY>'), 0);
						
			$startPos = strpos($stream, 'ISCP');
			if($startPos!==false) {
				$this->SendDebug( __FUNCTION__ , sprintf('Found prefix "ISCP" in received stream at position %d', $startPos), 0);
			}
			
			if($bufferLength==0 || $startPos==0) {
				$reason = '';
				if($startPos==0) {
					$reason = 'ISCP is at position 0';
				}
				
				if($bufferLength==0) {
					$separator = '';
					if(strlen($reason)>0) {
						$separator = '|';
					}
						$reason = $reason . $separator . 'Size of buffer is 0';
				}
				
				$this->SendDebug( __FUNCTION__ , sprintf('Setting saved buffer to received stream. Reason: %s', $reason), 0);
				
				$buffer = $stream;
			}
			
			if(($startPos>0 || $startPos===false) && $bufferLength>0) {
				$this->SendDebug( __FUNCTION__ , 'Concatinating saved buffer and received stream', 0);
				$buffer.=$stream;
				$startPos = strpos($buffer, 'ISCP');
			} 

			if($startPos>0) {
				$this->SendDebug( __FUNCTION__ , 'Removing data before prefix "ISCP"', 0);
				$buffer = substr($buffer, $startPos);
			}

			$this->SendDebug( __FUNCTION__ , sprintf('New buffer after received stream and before processing complete command(s): %s', $buffer), 0);

			$commandsToChild = [];
			if(strpos($buffer, "\x0d\x0a")>0) {
				$this->SendDebug( __FUNCTION__ , 'At least one complete command has been received', 0);
				$commands = explode("\x0d\x0a", $buffer);
				$buffer = '';

				$commandsToChild = [];
				foreach($commands as $command) {
					$startPos = strpos($command, 'ISCP');
					$endPos = strpos($command, "\x1A");
					if($startPos==0 && $endPos == strlen($command)-1) {
						$this->SendDebug( __FUNCTION__ , sprintf('Found command: %s', $command), 0);
						
						$api = new ISCPCommand($command); 

						$this->SendDebug( __FUNCTION__ , sprintf('Decoded command: %s', $api->ToJSON()), 0);

						break;
					} else {
						$this->SendDebug( __FUNCTION__ , sprintf('Invalid command or last in stream: %s', $command), 0);
						$buffer = $command;
						break;
					}
				}
			} 
			
			$this->SendDebug( __FUNCTION__ , sprintf('New buffer after processing commands: %s', strlen($buffer)>0?$buffer:'<EMPTY>'), 0);
			
			$this->SetBuffer(self::BUFFER, serialize($buffer));

			self::Unlock(self::BUFFER);
		}



		//$this->SendDataToChildren(json_encode(['DataID' => '{EF1FFC09-B63E-971C-8DC9-A2F6B37046F1}', 'Buffer' => $data->Buffer]));
	}
}