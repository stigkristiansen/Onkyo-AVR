<?php

declare(strict_types=1);


class Zones {
    const BOOLEAN = 'Boolean';
    const INTEGER = 'Integer';
    const STRING = 'String';

    const MAIN = 1;
    const ZONE2 = 2;

    const ZoneNames = [
        Zones::MAIN => 'Main',
        Zones::ZONE2 => 'Zone2'
    ];
    

    const VARIABLES = [
        Zones::MAIN => [
            'PWR' => [
                'Ident' => 'PWR',
                'Caption' => 'Power',
                'Type' => Zones::BOOLEAN,
                'Profile' => '~Switch',
                'Icon' => '',
                'Assoc' => [],
                'Enabled' => true
            ],
            'AMT' => [
                'Ident' => 'AMT',
                'Caption' => 'Mute',
                'Type' => Zones::BOOLEAN,
                'Profile' => 'OAVRD.Mute',
                'Icon' => 'Speaker',
                'Assoc' => [
                    [true, 'Muted', '', -1],
                    [false, 'Unmuted', '', -1]
                ],
                'Enabled' => true
            ],
            'MVL' => [
                'Ident' => 'MVL',
                'Caption' => 'Volume',
                'Type' => Zones::INTEGER,
                'Profile' => '~Intensity.100',
                'Icon' => 'Speaker',
                'Assoc' => [],
                'Enabled' => true
            ],
            'SLI' => [
                'Ident' => 'SLI',
                'Caption' => 'Input',
                'Type' => Zones::INTEGER,
                'Profile' => 'OAVRD.Input',
                'Icon' => 'Music',
                'Assoc' => 'OAVRD_Input',
                'Enabled' => true
            ],
            'LMD' => [
                'Ident' => 'LMD',
                'Caption' => 'Listen Mode:',
                'Type' => Zones::INTEGER,
                'Profile' => 'OAVRD.ListenMode',
                'Icon' => 'Music',
                'Assoc' => 'OAVRD_ListenMode',
                'Enabled' => true
            ]
        ]
    ];

    public function GetAssocArray(string $Function, mixed $Capabilities, int $Zone ) : array {
        return self::{$Function}($Capabilities, $Zone);
    }

    private function OAVRD_Input(mixed $Capabilities, int $Zone) : array {
        
        $selectorList = [];

        foreach($Capabilities['SelectorList'] as $id => $selector) {
            if($selector['Zone'] & $Zone) {
                $selectorList[] = [$id, $selector['Name'], '', -1];
            }
        }

        return $selectorList;
    }

    private function OAVRD_ListenMode(mixed $Capabilities, int $Zone) : array {
        
        $listenModeList = [];

        foreach($Capabilities['ListenModeList'] as $listenMode) {
            if($listenMode['Zone'] & $Zone) {
                $listenModeList[] = [$listenMode['Code'], $listenMode['Name'], '', -1];
            }
        }

        return $listenModeList;
    }
    
}
