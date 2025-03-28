<?php

declare(strict_types=1);

require __DIR__ . '/../libs/ISCPMain.php';

class Converter { 
    use MainCommands;

    private string $Command;

    private array $SupportedCommands = [
        'PWR',
        'SLI',
        'MVL',
        'AMT', 
        'LMD',
        'ECN',
        'TGA',
        'TGB',
        'TGC',
        'IFV',
        'IFA',
        'SWL',
        'CTL',
        'NRI'
    ];

    public function __construct(string $Command) {
        if(array_search($Command, $this->SupportedCommands)===false) {
            throw new Exception(sprintf('The command "%s" is not supported', $Command));
        }

        $this->Command = strtoupper($Command);
    }

    public function Convert(mixed $Data) {
        return self::{$this->Command}($Data);
    }

    
}

class ISCPCommand {
    public $Command;
    public $Data;

    private $Prefix = '';

    private $BoolValueMapping = [
        false => '00',
        true  => '01',
        ];
    
    public function __construct(string $Command = null, string $Data = null, string $Prefix = '!1') {
        if ($Command === null) {
            return;
        }

        $this->Prefix = $Prefix;
        
        if ($Data !== null) {
            $this->Command = $Command;
            $this->Data = $Data;
            
            return;
        }

        if ($Command[strlen($Command) - 1] === "\x1A") {
            $this->Command = substr($Command, 18, 3);
            
            $converter = new Converter($this->Command);
            $data = substr($Command, 21, -1);
            $this->Data = $converter->Convert($data);

            return;
        } 
        
        $json = json_decode($Command);
        $this->Command = $json->Command;
        $this->Data = $json->Data;
    }

    public function ToString() {
        $converter = new Converter($this->Command);
        $data = $converter->Convert($this->Data);

        $payload = $this->Prefix . $this->Command . $data . "\r\n";
        $payloadLen = pack('N', strlen($payload));
        
        $iscpHeader = $payloadLen . "\x01\x00\x00\x00";
        $iscpHeaderLen = pack('N', strlen($iscpHeader) + 8);
        
        return 'ISCP' . $iscpHeaderLen . $iscpHeader . $payload;
     }

     public function ToJSON() {
        /*$data = [
            'Command' => $this->Command,
            'Data' => $this->Data
        ];*/

        return json_encode($this->ToArray());
     }

     public function ToArray() {
        $data = [
            'Command' => $this->Command,
            'Data' => $this->Data
        ];

        return $data;
     }

}

