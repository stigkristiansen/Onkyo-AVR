<?php

declare(strict_types=1);

trait Variables {
    class Variables {
        const BOOLEAN = 'Boolean';
        const INTEGER = 'Integer';

        const PROPERTY = [
            'PWR' => [
                'ZOne' => 'main',
                'Ident' => 'PWR',
                'Caption' => 'Power',
                'Type' => self::BOOLEAN,
                'Profile' => '~Switch',
                'Icon' => '',
                'Assoc' => []
            ],
            'AMT' => [
                'ZOne' => 'main',
                'Ident' => 'AMT',
                'Caption' => 'Mute',
                'Type' => self::BOOLEAN,
                'Profile' => 'OAVRD.Mute',
                'Icon' => 'Speaker',
                'Assoc' => [
                    [true, 'Muted', '', -1],
                    [false, 'Unmuted', '', -1]
                ]
            ],
            'MVL' => [
                'Znne' => 'main',
                'Ident' => 'MVL',
                'Caption' => 'Volume',
                'Type' => self::INTEGER,
                'Profile' => '~Intensity.100',
                'Icon' => '',
                'Assoc' => []
            ],
            'SLI' => [
                'Znne' => 'main',
                'Ident' => 'MVL',
                'Caption' => 'Volume',
                'Type' => self::INTEGER,
                'Profile' => 'OAVRD.Input',
                'Icon' => '',
                'Assoc' => []
            ]
        ];
    }
}