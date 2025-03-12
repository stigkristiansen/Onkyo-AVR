<?php

declare(strict_types=1);


class Converter { 
    private string $Command;

    private array $SupportedCommands = [
        'PWR',
        'SLI',
        'IFV',
        'IFA',
        'MVL',
        'SWL', // Må implementeres
        'CTL'  // Må implemeteres
    ];

    public function __construct(string $Command) {
        if(array_search($Command, $this->SupportedCommands)===false) {
            throw new Exception(sprintf('The command "%s" is not supported', $Command));
        }

        $this->Command = strtoupper($Command);
    }

    public function Execute(mixed $Data) {
        return self::{$this->Command}($Data);
    }

    private function SWL(mixed $Data) : mixed {

    }

    private function MVL(mixed $Data) : mixed {
        if(is_string($Data)) {
            $Data = strtoupper($Data);

            if(ctype_xdigit($Data)) {
                return (int)$Data;
            }

            switch($Data) {
                case 'QSTN':
                case 'UP':
                case 'DOWN':
                case 'UP1':
                case 'DOWN1': 
                    return $Data;
                default:
                    throw new Exception('Invalid Data!');            
            }
        }

        if(is_numeric($Data)) {
            return sprintf('%02X', $Data);
        }

        throw new Exception('Invalid Data!');
    }

    private function IFV(mixed $Data) : String {
        if(is_string($Data)) {
            return $Data;
        }

        throw new Exception('Invalid Data!');
    }

    private function IFA(mixed $Data) : String {
        if(is_string($Data)) {
            return $Data;
        }

        throw new Exception('Invalid Data!');
    }

    private function SLI(mixed $Data) : mixed{
        if(is_string($Data)) {
            $Data = strtoupper($Data);

            if($Data=='QSTN') {
                return $Data;
            }  

            if(ctype_xdigit($Data)) {
                return (int)$Data;
            } else {
                throw new Exception('Invalid hexadesimal number!');
            }
        }

        if(is_numeric($Data)) {
            return sprintf('%02X', $Data);
        }

        throw new Exception('Invalid Data!');
    }

    private function PWR(mixed $Data) : mixed {
        if(is_string($Data)) {
            $Data = strtoupper($Data);
            switch($Data) {
                case '00':
                    return false;
                case '01':
                    return true;
                case 'QSTN':
                    return 'QSTN';
                default:
                    throw new Exception('Invalid Data!');
            }
        }

        if(is_bool($Data)) {
            switch($Data) {
                case true:
                    return '01';
                case false:
                    return '00';
                default:
                    throw new Exception('Invalid Data!');
            }
        }

        throw new Exception('Invalid Data!');
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
            
            $convert = new Converter($this->Command);
            $data = substr($Command, 21, -1);
            $this->Data = $convert->Execute($data);

            return;
        } 
        
        $json = json_decode($Command);
        $this->Command = $json->Command;
        $this->Data = $json->Data;
    }

    public function ToString() {
        $convert = new Converter($this->Command);
        $value = $convert->Execute($this->Data);

        $payload = $this->Prefix . $this->Command . $value . "\r\n";
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

