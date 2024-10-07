<?php
return [

    /*
    |-------------------------------------------------------------------------------------------
    | URL - One Signal have different endpoint.
    |-------------------------------------------------------------------------------------------
    |
    */
    
    'url'    => env('ONE_SIGNAL_URL', 'https://onesignal.com/api/v1/'),

    /*
    |-------------------------------------------------------------------------------------------
    | App Id - One Signal have different app id for every app.
    |
    | Based on App you are using, you can change the App Id here and specify here
    |-------------------------------------------------------------------------------------------
    |
    */

    // sementera defaul di staging dulu
    
    'app_id' => env('ONE_SIGNAL_APP_ID', "2bf2113a-0204-42ed-ae7b-76c7664930b2"),

    /*
    |-------------------------------------------------------------------------------------------
    | Authorize - One Signal have different Authorize for every app.
    |
    | Based on App you are using, you can change the Authorize here and specify here
    |-------------------------------------------------------------------------------------------
    |
    */

    'authorize'       => env('ONE_SIGNAL_AUTHORIZE', "ODExZGY3MmEtOTM3MC00NjM4LTkyZWItM2MyNDkxM2I2ZWY5"),

    /*
    |-------------------------------------------------------------------------------------------
    | mutable_content - Always defaults to true and cannot be turned off. Allows tracking of notification receives
    | and changing of the notification content in app before it is displayed.
    |-------------------------------------------------------------------------------------------
    |
    */
    'mutable_content' => env('ONE_SIGNAL_MUTABLE_CONTENT', true),

    /*
    |-------------------------------------------------------------------------------------------
    | Auth Key - One Signal have Auth key of account.
    |
    | You can manage apps
    |-------------------------------------------------------------------------------------------
    |
   */
    'auth_key' => env('ONE_SIGNAL_AUTH_KEY', "MzU2Y2VkZWItMTBmMS00OTNiLWI2NjEtZTNmMzc5MTdmNDA3")
];
