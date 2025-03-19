<?php

declare(strict_types=1);

class capabilities {
    private $xml;
    
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
            $this->xml = '';
        }
    }

    public function Decode() : Boolean {
        if($xml=='') {
            return false;
        }

        foreach($this->xml->xpath('//model') as $model) {
            $this->ModelName = (string)$model;
        }

        foreach($this->xml->xpath('//year') as $year) {
            $this->ModelYear = (string)$year;
        }

        foreach($Xml->xpath('//firmwareversion') as $firmwareVersion) {
            $this->FirmwareVersion = (string)$firmwareVersion;
        }

        $netServiceList = [];
        foreach($Xml->xpath('//netservice') as $netService) {
            if ((string)$netService['value']=='0') {
                continue;
            }

            $netServiceList[hexdec((string)$netService['id'])] = trim((string)$netService['name']);
        }
        $this->NetServiceList = $netServiceList;

        $zoneList = [];
        foreach($Xml->xpath('//zone') as $zone) {
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
        foreach($Xml->xpath('//selector') as $selector) {
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
        foreach($Xml->xpath('//control') as $control) {
            if ((string)$control['value']=='0') {
                continue;
            }

            if(strpos((string)$control['id'], 'LMD')===0) {
                $listenModeList[(int)$control['position']] = [
                    'Name' => trim(substr((string)$control['id'], 4)),
                    'Code' => (string)$control['code'],
                ];
            }
        }
        $this->ListenModeList = $listenModeList;

        return true;
    }
}