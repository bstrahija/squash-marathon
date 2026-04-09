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
                'label' => 'Ekipa',
                'route' => 'home',
                'fragment' => 'participants',
            ],
            [
                'label' => 'Poredak',
                'route' => 'home',
                'fragment' => 'leaderboard',
            ],
            [
                'label' => 'Kronologija',
                'route' => 'home',
                'fragment' => 'timeline',
            ],
        ],
        'cta' => [
            'label' => 'Live',
            'route' => 'tv',
        ],
    ],
];
