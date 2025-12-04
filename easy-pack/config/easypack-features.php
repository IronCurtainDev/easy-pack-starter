<?php

/*
|--------------------------------------------------------------------------
| Enable or disable application features from this file
|--------------------------------------------------------------------------
|
| Instead of copying and pasting code blocks, add all the standard features
| and enable or disable the features as they're required by the clients.
|
| The features listed here must be available across sandbox and live environments.
| If you need to have custom features across sandbox and live environments, move
| them to an .env file parameter, and refer to it on this page.
|
| Usage: Use the has_feature() helper function to check feature status.
| Example: has_feature('auth.public_users_can_register')
|          has_feature('api.active')
|          has_feature('notifications.push_enabled')
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | API FEATURES
    |--------------------------------------------------------------------------
    */

    'api' => [
        // Master switch to enable/disable the entire API
        'active' => env('API_ACTIVE', true),

        // Enable API documentation endpoints (auto-disabled in production)
        'documentation_enabled' => env('API_DOCS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION FEATURES
    |--------------------------------------------------------------------------
    */

    'auth' => [
        // Allow any user to register via API
        'public_users_can_register' => env('REGISTRATIONS_ENABLED', false),

        // Force email verification after registration
        'email_verification_required' => env('EMAIL_VERIFICATION_REQUIRED', false),

        // Allow password resets via "forgot password" flow
        'allow_forgot_password_resets' => env('ALLOW_PASSWORD_RESETS', true),

        // Allow users to delete their own account
        'allow_account_deletion' => env('ALLOW_ACCOUNT_DELETION', true),

        // Enable social login (OAuth)
        'social_login_enabled' => env('SOCIAL_LOGIN_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION FEATURES
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        // Enable push notifications
        'push_enabled' => env('PUSH_NOTIFICATIONS_ENABLED', true),

        // Enable email notifications
        'email_enabled' => env('EMAIL_NOTIFICATIONS_ENABLED', true),

        // Allow users to manage notification preferences
        'user_preferences_enabled' => true,

        // Enable topic-based subscriptions
        'topics_enabled' => env('NOTIFICATION_TOPICS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | MEDIA FEATURES
    |--------------------------------------------------------------------------
    */

    'media' => [
        // Enable media uploads
        'uploads_enabled' => env('MEDIA_UPLOADS_ENABLED', true),

        // Enable public media gallery/browsing
        'public_gallery_enabled' => env('MEDIA_PUBLIC_GALLERY', false),

        // Enable user avatars
        'avatars_enabled' => true,

        // Enable document uploads for users
        'documents_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | SECURITY FEATURES
    |--------------------------------------------------------------------------
    */

    'security' => [
        // Enable reCAPTCHA on registration and other forms
        'recaptcha_enabled' => env('RECAPTCHA_ENABLED', false),

        // Enable rate limiting
        'rate_limiting_enabled' => env('RATE_LIMITING_ENABLED', true),

        // Force HTTPS (redirect HTTP to HTTPS)
        'force_https' => env('FORCE_HTTPS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | INVITATION FEATURES
    |--------------------------------------------------------------------------
    */

    'invitations' => [
        // Enable user invitation system
        'enabled' => env('INVITATIONS_ENABLED', true),

        // Allow invited users to set their own password
        'allow_password_set' => true,

        // Invitation expiry in days
        'expiry_days' => env('INVITATION_EXPIRY_DAYS', 7),
    ],

];
