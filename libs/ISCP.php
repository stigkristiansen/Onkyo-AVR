<?php

declare(strict_types=1);

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
            $this->Command = substr($Command, 2, 3);
            $this->Data = substr($Command, 5, -1);

            return;
        } 
        
        $json = json_decode($Command);
        $this->Command = $json->Command;
        
        if (is_bool($json->Data)) {
            $value = $this->BoolValueMapping[$json->Data];
        } elseif (is_int($json->Data)) {
            $value = sprintf('%02X', $json->Data);
        } else {
            $value = $json->Data;
        } 

        $this->Data = utf8_decode($value);
    }

    public function ToString() {
        if (is_bool($this->Data)) {
            $Value = $this->BoolValueMapping[$this->Data];
        } elseif (is_int($this->Data)) {
            $Value = sprintf('%02X', $this->Data);
        } else {
            $Value = $this->Data;
        }

        $payload = $this->Prefix . $this->Command . $Value . "\r\n";
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

