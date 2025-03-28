<?php

declare(strict_types=1);


class Zones {
    const BOOLEAN = 'Boolean';
    const INTEGER = 'Integer';

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
                'Icon' => '',
                'Assoc' => [],
                'Enabled' => true
            ],
            'SLI' => [
                'Ident' => 'SLI',
                'Caption' => 'Input',
                'Type' => Zones::INTEGER,
                'Profile' => 'OAVRD.Input',
                'Icon' => '',
                'Assoc' => [],
                'Enabled' => true
            ]
        ]
    ];
}
