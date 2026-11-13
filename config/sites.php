<?php

return [
    // Your own domain, for free school addresses such as school-name.mskdevs.com. Empty turns those addresses off.
    'base_domain' => env('SITE_BASE_DOMAIN'),

    // The public IP address of the server, shown in the setup guide and compared with each domain's DNS.
    'server_ip' => env('SITE_SERVER_IP'),

    // https once SSL works on the server. Use http only while testing.
    'scheme' => env('SITE_SCHEME', 'https'),

    // Names that can never be a school's free address.
    'reserved' => ['www', 'app', 'admin', 'api', 'mail', 'webmail', 'cpanel', 'ftp', 'cdn', 'static', 'portal', 'login', 'demo', 'ns1', 'ns2'],
];
