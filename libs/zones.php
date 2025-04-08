<?php

declare(strict_types=1);


class Zones {
    const BOOLEAN = 'Boolean';
    const INTEGER = 'Integer';
    const STRING = 'String';

    const MAIN = 1;
    const ZONE2 = 2;
    const ZONE3 = 3;
    const ZONE4 = 4;
    const ALL = 999;

    const Zones = [
        Zones::MAIN => [
            'Name' => 'Main',
            'Filter' => '##Main##'
        ],
        Zones::ZONE2 => [
            'Name' => 'Zone2',
            'Filter' => '##Zone2##'
        ],
        Zones::ZONE3 => [
            'Name' => 'Zone3',
            'Filter' => '##Zone3##'
        ],
        Zones::ZONE4 => [
            'Name' => 'Zone4',
            'Filter' => '##Zone4##'
        ],
        Zones::ALL => [
            'Name' => 'AllZones',
            'Filter' => '##AllZones##'
        ]
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
                'Enabled' => true,
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
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
                'Enabled' => true,
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'MVL' => [
                'Ident' => 'MVL',
                'Caption' => 'Volume',
                'Type' => Zones::INTEGER,
                'Profile' => '~Intensity.100',
                'Icon' => 'Speaker',
                'Assoc' => [],
                'Enabled' => true,
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'SLI' => [
                'Ident' => 'SLI',
                'Caption' => 'Input',
                'Type' => Zones::INTEGER,
                'Profile' => 'OAVRD.Input',
                'Icon' => 'Music',
                'Assoc' => 'OAVRD_Input',
                'Enabled' => true,
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'LMD' => [
                'Ident' => 'LMD',
                'Caption' => 'Listen Mode:',
                'Type' => Zones::INTEGER,
                'Profile' => 'OAVRD.ListenMode',
                'Icon' => 'Music',
                'Assoc' => 'OAVRD_ListenMode',
                'Enabled' => true,
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ]
        ]
    ];

    const COMMANDS = [
        Zones::MAIN => [
            'PWR' => [
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'AMT' => [
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'MVL' => [
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'SLI' => [
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'LMD' => [
                'Filter' => Zones::Zones[Zones::MAIN]['Filter']
            ],
            'NRI' => [
                'Filter' => Zones::Zones[Zones::ALL]['Filter']
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
        
        $listenModeList = [
            [0x00, 'STEREO', '', -1],
            [0x01, 'DIRECT', '', -1],
            [0x02, 'SURROUND', '', -1],
            [0x03, 'FILM', '', -1],
            [0x04, 'THX', '', -1],
            [0x05, 'ACTION', '', -1],
            [0x06, 'MUSICAL', '', -1],
            [0x08, 'ORCHESTRA', '', -1],
            [0x09, 'UNPLUGGED', '', -1],
            [0x0A, 'STUDIO-MIX', '', -1],
            [0x0B, 'TV LOGIC', '', -1],
            [0x0C, 'ALL CH STEREO', '', -1],
            [0x0D, 'THEATER-DIMENSIONAL', '', -1],
            [0x0E, 'ENHANCED', '', -1],
            [0x0F, 'MONO', '', -1],
            [0x11, 'PURE AUDIO', '', -1],
            [0x13, 'FULL MONO', '', -1],
            [0x40, 'Straight Decode', '', -1],
            [0x42, 'THX Cinema', '', -1],
            [0x43, 'THX Surround EX', '', -1],
            [0x44, 'THX Music', '', -1],
            [0x45, 'THX Games', '', -1],
            [0x50, 'THX Cinema Mode, THX U2/S2/I/S Cinema', '', -1],
            [0x51, 'THX Music Mode, THX U2/S2/I/S Music', '', -1],
            [0x52, 'THX Games Mode, THX U2/S2/I/S Games', '', -1],
            [0x80, 'PLII/PLIIx Movie', '', -1],
            [0x81, 'PLII/PLIIx Music', '', -1],
            [0x82, 'Neo:6 Cinema/Neo:X Cinema', '', -1],
            [0x83, 'Neo:6 Music/Neo:X Music', '', -1],
            [0x84, 'PLII/PLIIx THX Cinema', '', -1],
            [0x85, 'Neo:6/Neo:X THX Cinema', '', -1],
            [0x86, 'PLII/PLIIx Game', '', -1],
            [0x89, 'PLII/PLIIx THX Games', '', -1],
            [0x8A, 'Neo:6/Neo:X THX Games', '', -1],
            [0x8B, 'PLII/PLIIx THX Music', '', -1],
            [0x8C, 'Neo:6/Neo:X THX Music', '', -1]
        ];

        return $listenModeList;
    }
    
}
