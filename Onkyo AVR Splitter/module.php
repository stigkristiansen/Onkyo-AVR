<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ISCP.php';
require_once __DIR__ . '/../libs/semaphoreHelper.php';
require_once __DIR__ . '/../libs/capabilities.php';
require_once __DIR__ . '/../libs/parentStatus.php';
require_once __DIR__ . '/../libs/zones.php';

class OnkyoAVRSplitter extends IPSModule {
	use Semaphore;
	use ParentStatus;

	const BUFFER = 'Incoming';

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ConnectParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->SetBuffer(self::BUFFER, serialize(''));
		$this->SetBuffer(Capabilities::BUFFER, serialize([]));

		$this->RegisterMessage(0, IPS_KERNELSTARTED);
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() == KR_READY) {
			$this->SendDebug(__FUNCTION__, 'Kernel is ready. Initializing module', 0);
			
			$this->RegisterParent();
			$this->GetCapabilities();
        }
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);
		
        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->SendDebug(__FUNCTION__, 'Kernel is ready. Initializing module', 0);

			$this->RegisterParent();
			$this->GetCapabilities();

			return;
		}

		$this->HandleParentMessages($TimeStamp, $SenderID, $Message, $Data);
    }


	private function GetCapabilities() {
		$api = new ISCPCommand('NRI', 'QSTN');
		
		$this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $api->ToEISCP()]));
	}
	
	public function ForwardData($JSONString) {
		$data = json_decode($JSONString);
		$command = json_encode($data->Buffer);

		$this->SendDebug( __FUNCTION__ , sprintf('Received data for forwaring to parent instance: %s', $command), 0);

		if($data->Buffer->Command=='CAP' && $data->Buffer->Data=='QSTN') {
			$this->SendDebug( __FUNCTION__ , 'The data received was a query for capabilities. Returning the queried data', 0);
			$capabilities = unserialize($this->GetBuffer(Capabilities::BUFFER));

			if(count($capabilities)>0) {
				return json_encode($capabilities);
			} else {
				$this->SendDebug( __FUNCTION__ , 'Missing capabilities information. Sending a new query to the device...', 0);
				$this->GetCapabilities();
				return;
			}
		}

		$api = new ISCPCommand($command);
		
		$result = $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => $api->ToEISCP()]));

		$this->SendDebug( __FUNCTION__ , 'The data was forwarded to the parent instance', 0);

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

			$commandsToChild = [];
			
			if($bufferLength==0 || ($startPos!==false && $startPos==0)) {
				$reason = '';
				if($startPos==0) {
					$reason = 'ISCP is at position 0';
				}
				
				if($bufferLength==0) {
					$separator = '';
					if(strlen($reason)>0) {
						$separator = ' | ';
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
						
						try {
							$api = new ISCPCommand($command); 
							
							if($api->Command=='NRI') {
								$this->SendDebug( __FUNCTION__ , 'The command received was NRI', 0);
								$capabilities = new Capabilities($api->Data);
			
								if($capabilities->Decode()){
									$temp = [
										'NetserviceList' => $capabilities->NetserviceList,
										'ZoneList' => $capabilities->ZoneList,
										'SelectorList' => $capabilities->SelectorList,
										'ListenModeList' => $capabilities->ListenModeList
									];

									$this->SendDebug( __FUNCTION__ , 'Decoded NRI data', 0);
									$this->SendDebug( __FUNCTION__ , sprintf('NRI data: %s', json_encode($temp)), 0);

									if(self::Lock(Capabilities::BUFFER)) {
										$this->SetBuffer(Capabilities::BUFFER, serialize($temp));
										self::Unlock(Capabilities::BUFFER);

										$this->SendDebug( __FUNCTION__ , 'Saved NRI data to the Capabilities-buffer', 0);
									}

									$commandsToChild[] = (new ISCPCommand('CAP', $temp))->ToArray();
								} else {
									$this->SendDebug( __FUNCTION__ , 'XML decode failed!', 0);
								}
							} else {
								$commandsToChild[] = $api->ToArray();;
							}

						} catch(Exception $e) {
							$message = sprintf('Failed to decode the command. The cause was: %s', $e->getMessage());
							$this->SendDebug( __FUNCTION__ , $message, 0);	
							$this->LogMessage($message, KL_WARNING);
							break;
						} 

						$this->SendDebug( __FUNCTION__ , sprintf('Decoded command: %s', $api->ToJSON()), 0);

						break;
					} else {
						$this->SendDebug( __FUNCTION__ , sprintf('Incomplete command saved for later usage: %s', $command), 0);
						$buffer = $command;
						break;
					} 
				}
			} 
			
			$this->SendDebug( __FUNCTION__ , sprintf('New buffer after processing commands: %s', strlen($buffer)>0?$buffer:'<EMPTY>'), 0);
			
			$this->SetBuffer(self::BUFFER, serialize($buffer));

			foreach($commandsToChild as $commandToChild) {
				$filter = self::GetFilterByCommand($commandToChild['Command']);
				$this->SendDebug( __FUNCTION__ , sprintf('Sending command to child instans(es) with filter: %s', $filter), 0);
				$this->SendDataToChildren(json_encode(['DataID' => '{EF1FFC09-B63E-971C-8DC9-A2F6B37046F1}', 'Filter' => $filter, 'Buffer' => $commandToChild]));
			}

			/*if(count($commandsToChild) > 0) {
				$this->SendDebug( __FUNCTION__ , 'Sending command(s) to child instans(es)', 0);
				$filter = self::GetFilterByCommand()
				$this->SendDataToChildren(json_encode(['DataID' => '{EF1FFC09-B63E-971C-8DC9-A2F6B37046F1}', 'Buffer' => $commandsToChild]));
			}*/
			
			self::Unlock(self::BUFFER);
		}
	}

	protected function GetFilterByCommand($Command) : string {
		$filter = '';

        foreach(Zones::VARIABLES as $zoneId => $commands) {
            foreach($commands as $command => $properties) {
                if($Command == $command) {
                    return $properties['Filter'];
                }
            }
        }
		
        return $filter;
	}
}