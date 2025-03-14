<?php

declare(strict_types=1);

require __DIR__ . '/../libs/ISCPMain.php';

class Converter { 
    use MainCommands;

    private string $Command;

    private array $SupportedCommands = [
        'PWR',
        'SLI',
        'IFV',
        'IFA',
        'MVL',
        'SWL',
        'CTL', 
        'AMT', 
        'TGA',
        'TGB',
        'TGC',
        'LMD'
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
        
        $ISCPHeader = $payloadLen . "\x01\x00\x00\x00";
        $ISCPHeaderLen = pack('N', strlen($ISCPHeader) + 8);
        
        return 'ISCP' . $ISCPHeaderLen . $ISCPHeader . $payload;
     }

     public function ToJSON() {
        $data = [
            'Command' => $this->Command,
            'Data' => $this->Data
        ];

        return json_encode($data);
     }

}

