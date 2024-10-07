<?php

return [

    /*
     * Application title to display in <title> tag
     */
    'title' => 'Java mas Engine',

    /*
     * Text to put in the top-left of the menu bar. logo_mini is shown when the navbar is collapsed.
     * NOTE: This is a non-escaped string, so you can put HTML in here
     */
    'logo' => '<strong>JAVAMAS</strong>Engine',

    /*
     * Menu builder
     */
    'menu' => [
        [
            'text' => 'Dashboard', // The text to be displayed inside the menu.
            'url' => 'dashboard', // The URL behind the text. Mutually exclusive with "route" option.
            'icon' => 'chart-bar far', // Name of FontAwesome icon to display. Note that you have to use the "far", "fas" or "fal" modifier behind the icon.
            // 'target' => '_blank',           // Target attribute of <a> tag.

            'badge' => [ // Optional. Displays a badge behind the text of the menu item.
                'text' => 'New!', // Text to display in badge.
                'context' => 'danger', // Coloring of the badge, uses CoreUI/Bootstrap context: primary, danger, warning, etc. Default is 'primary'.
                'pill' => true, // Whether badge should have rounded corners. Defaults to false;
            ],
        ],
        [
            'text' => 'Administrator',
            'can' => 'crud store',
            'icon' => 'users-cog',
            'fa-family' => 'fas', // Change the FontAwesome family: fas, far, fab, etc. Default is 'fas'
            'submenu' => [
                [
                    'text' => 'Permissions',
                    'icon' => 'address-book', // Tip: always set icons. It's more accessible and user friendly.
                    'route' => 'administrator.index',
                ],
                [
                    'text' => 'Create Permission',
                    'icon' => 'plus-circle', // Tip: always set icons. It's more accessible and user friendly.
                    'route' => 'administrator.create',
                ],
                [
                    'text' => 'Assign Permission',
                    'icon' => 'unlock', // Tip: always set icons. It's more accessible and user friendly.
                    'route' => 'admin.users.assign',
                    'can' => 'show administrator permission',
                ],
                [
                    'text' => 'Assign Role to User',
                    'icon' => 'unlock', // Tip: always set icons. It's more accessible and user friendly.
                    'route' => 'administrator.users.assignRole',
                    'can' => 'show administrator permission',
                ],
            ],
        ],
        [
            'text' => 'User',
            'can' => 'edit article',
            'icon' => 'users',
            'fa-family' => 'fas', // Change the FontAwesome family: fas, far, fab, etc. Default is 'fas'
            'submenu' => [
                [
                    'text' => 'Users List',
                    'icon' => 'address-book', // Tip: always set icons. It's more accessible and user friendly.
                    // 'route' => 'users.list',
                ],
                [
                    'text' => 'Add New',
                    'icon' => 'plus-circle', // Tip: always set icons. It's more accessible and user friendly.
                    // 'route' => 'user.create',
                ]
            ],
        ],
        // 'Admin only',
        // [
        //     'can' => 'show administrator permission', // Use Laravel's Gate functionality via the 'can' keyword to show menu items according to your Gate. Note that you need to uncomment the GateFilter in the Filters array below!
        //     'text' => 'Settings',
        //     'icon' => 'cog',
        //     'submenu' => [
        //         [
        //             'text' => 'Level one',
        //             'icon' => 'bell', // Tip: always set icons. It's more accessible and user friendly.
        //             'url' => 'admin/settings/level-one',
        //         ],
        //         [
        //             'text' => 'Level two',
        //             'icon' => 'clock',
        //             'submenu' => [
        //                 [
        //                     'text' => 'Add as many as you like',
        //                     'url' => '#',
        //                 ],
        //             ],
        //         ],
        //     ],
        // ],
        [
            'text' => 'Contact',
            'url' => 'dashboard',
            'icon' => 'phone-square-alt'
        ],
        [
            'text' => 'About Us',
            'url' => 'dashboard',
            'icon' => 'info-circle',
            'can' => 'crud store'
        ]
    ],

    /**
     * Filters that parse above menu configuration and add some sparkling things, like .active classes on active
     * menu items and parsing routes and URLs into the correct href attributes.
     */
    'filters' => [
        HzHboIct\LaravelCoreUI\Menu\Filters\HrefFilter::class,
        HzHboIct\LaravelCoreUI\Menu\Filters\ActiveFilter::class,
        HzHboIct\LaravelCoreUI\Menu\Filters\SubmenuFilter::class,
        HzHboIct\LaravelCoreUI\Menu\Filters\ClassesFilter::class,
        // Uncomment below filter if you want to use the 'can' functionality of the menu builder.
        HzHboIct\LaravelCoreUI\Menu\Filters\GateFilter::class,
    ],
];
