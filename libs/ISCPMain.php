<?php

declare(strict_types=1);

trait MainCommands { 
    private function TGA($Data) : mixed {
        return self::PWR($Data);
    }

    private function TGB($Data) : mixed {
        return self::PWR($Data);
    }
    
    private function TGC($Data) : mixed {
        return self::PWR($Data);
    }

    private function AMT(mixed $Data) : mixed {
        return self::PWR($Data);    
    }

    private function CTL(mixed $Data) : mixed {
        return self::SWL($Data);
    }
    
    private function SWL(mixed $Data) : mixed {
        if(is_string($Data)) {
            $prefix = substr($Data, 0,1);
            $neg = 1;
            if($prefix=='-' or $prefix='+') {
                $Data = substr($Data, 1);

                if($prefix=='-') {
                    $neg = -1;
                }
            }

            if(ctype_xdigit($Data)) {
                return (int)$Data*$neg;
            }

            $Data = strtoupper($Data);

            switch($Data) {
                case 'QSTN':
                case 'UP':
                case 'DOWN':
                    return $Data;
                default:
                    throw new Exception(sprintf('Invalid data string: %s', $Data));            
            }
        }

        if(is_numeric($Data)) {
            $prefix = substr(sprinf('%+d', $data), 0,1);
            return sprintf('%s%s', prefix, sprintf('%X', $Data));
        }

        throw new Exception(sprint('Invalid Data: %s', (string)$Data));
    }

    private function LMD($Dsata) : mixed {
        if(is_string($Data)) {
            $Data = strtoupper($Data);

            if(ctype_xdigit($Data)) {
                return hexdec($Data);
            }

            switch($Data) {
                case 'QSTN':
                case 'STEREO':
                case 'SURR':
                case 'AUTO':
                case 'THX':
                case 'GAME':
                case 'MUSIC':
                case 'MOVIE':
                case 'DOWN':
                case 'UP':                    
                    return $Data;
                default:
                    throw new Exception(sprintf('Invalid Data: %s', (string)$Data));            
            }
        }

        if(is_numeric($Data)) {
            return sprintf('%02X', $Data);
        }

        throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
    }

    
    private function MVL(mixed $Data) : mixed {
        if(is_string($Data)) {
            $Data = strtoupper($Data);

            if(ctype_xdigit($Data)) {
                return hexdec($Data);
            }

            switch($Data) {
                case 'QSTN':
                case 'UP':
                case 'DOWN':
                case 'UP1':
                case 'DOWN1': 
                    return $Data;
                default:
                    throw new Exception(sprintf('Invalid Data: %s', (string)$Data));            
            }
        }

        if(is_numeric($Data)) {
            return sprintf('%02X', $Data);
        }

        throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
    }

    private function IFV(mixed $Data) : String {
        if(is_string($Data)) {
            return $Data;
        }

        throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
    }

    private function IFA(mixed $Data) : String {
        if(is_string($Data)) {
            return $Data;
        }

        throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
    }

    private function SLI(mixed $Data) : mixed{
        if(is_string($Data)) {
            $Data = strtoupper($Data);

            if($Data=='QSTN') {
                return $Data;
            }  

            if(ctype_xdigit($Data)) {
                return hexdec($Data);
            } else {
                throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
            }
        }

        if(is_numeric($Data)) {
            return sprintf('%02X', $Data);
        }

        throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
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
                    throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
            }
        }

        if(is_bool($Data)) {
            switch($Data) {
                case true:
                    return '01';
                case false:
                    return '00';
                default:
                    throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
            }
        }

        throw new Exception(sprintf('Invalid Data: %s', (string)$Data));
    }
}