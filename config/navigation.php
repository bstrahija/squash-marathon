<?php

return [
    'public' => [
        'links' => [
            [
                'label' => 'O eventu',
                'route' => 'home',
            ],
            [
                'label' => 'Mečevi',
                'route' => 'matches.index',
            ],
            [
                'label' => 'Runde',
                'route' => 'rounds.index',
            ],
            [
                'label'    => 'Ekipa',
                'route'    => 'home',
                'fragment' => 'participants',
            ],
            [
                'label' => 'Poredak',
                'route' => 'stats.index',
            ],
        ],
        'cta' => [
            'label' => 'Live',
            'route' => 'tv',
        ],
    ],
];
