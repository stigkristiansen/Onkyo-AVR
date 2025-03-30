<?php

declare(strict_types=1);

class Capabilities {
    private $xml;

    const BUFFER = 'Capabilities';
    
    public $ModelName = '';
    public $ModelYear = '';
    public $FirmwareVersion = '';

    public $NetserviceList = [];
    public $ZoneList = [];
    public $SelectorList = [];
    public $ListenModeList = [];

    public function __construct(string $NRIXML) {
        try {
            $this->xml = new SimpleXMLElement($NRIXML, LIBXML_NOBLANKS + LIBXML_NONET + LIBXML_NOERROR);
        } catch(Exception $e) {
            $this->xml = null;
        }
    }

    public function Decode() : bool {
        if(is_object($this->xml) && $this->xml==null) {
            return false;
        }

        foreach($this->xml->xpath('//model') as $model) {
            $this->ModelName = (string)$model;
        }

        foreach($this->xml->xpath('//year') as $year) {
            $this->ModelYear = (string)$year;
        }

        foreach($this->xml->xpath('//firmwareversion') as $firmwareVersion) {
            $this->FirmwareVersion = (string)$firmwareVersion;
        }

        $netserviceList = [];
        foreach($this->xml->xpath('//netservice') as $netService) {
            if ((string)$netService['value']=='0') {
                continue;
            }

            $netserviceList[hexdec((string)$netService['id'])] = trim((string)$netService['name']);
        }
        $this->NetserviceList = $netserviceList;

        $zoneList = [];
        foreach($this->xml->xpath('//zone') as $zone) {
            if ((string)$zone['value']=='0') {
                continue;
            }

            $zoneList[hexdec((string)$zone['id'])] = [
                'Name'    => trim((string)$zone['name']),
                'VolMax'  => (int)$zone['volmax'],
                'VolStep' => (int)$zone['volstep']
            ];
        }
        $this->ZoneList = $zoneList;

        $selectorList = [];
        foreach($this->xml->xpath('//selector') as $selector) {
            if ((string)$selector['value']=='0') {
                continue;
            }

            $selectorList[hexdec((string)$selector['id'])] = [
                'Name' => trim((string)$selector['name']),
                'Zone' => (int)$selector['zone'],
            ];
        }
        $this->SelectorList = $selectorList;

        $listenModeList = [];
        foreach($this->xml->xpath('//control') as $control) {
            if ((string)$control['value']=='0') {
                continue;
            }

            $id = trim((string)$control['id']);
            if(strpos($id, 'LMD')===0) {
                $listenModeList[(int)$control['position']] = [
                    'Name' => trim(substr($id, 4)),
                    'Code' => (string)$control['code'],
                ];
            }
        }
        $this->ListenModeList = $listenModeList;

        return true;
    }
}