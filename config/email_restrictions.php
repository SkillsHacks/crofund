<?php

return [
    'blocked_domains' => [
        'mailinator.com',
        '10minutemail.com',
        'guerrillamail.com',
        'tempmail.com',
        'temp-mail.org',
        'yopmail.com',
        'sharklasers.com',
        'discard.email',
        'dispostable.com',
        'getnada.com',
        'maildrop.cc',
        'moakt.com',
        'trashmail.com',
        'fakeinbox.com',
    ],
    'blocked_patterns' => [
        '/^mailinator\./i',
        '/^tempmail\./i',
        '/^10minutemail\./i',
        '/^guerrillamail\./i',
        '/^yopmail\./i',
    ],
    'role_prefixes' => [
        'admin', 'administrator', 'billing', 'compliance', 'contact', 'hello', 'info',
        'legal', 'mailer-daemon', 'marketing', 'noreply', 'no-reply', 'postmaster',
        'privacy', 'sales', 'security', 'support', 'team', 'webmaster'
    ],
    'public_messages' => [
        'invalid_format' => 'Please enter a valid email address.',
        'unsupported_format' => 'This email address format is not supported.',
        'temporary_email' => 'Please use a permanent email address to create your account.',
        'cannot_verify' => 'We cannot verify this email address. Please try another email.',
        'already_registered' => 'Email already registered',
        'rate_limited' => 'We cannot verify this email address right now. Please try again later.',
    ],
];