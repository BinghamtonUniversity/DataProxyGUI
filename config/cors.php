<?php
// config/cors.php

// add a path to the resource here if you want it accessible to external origins
// for example no need to explicitly tell allowed origins
// what origins should gain access to api/* routes
return [
    
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],

    // explicitly tell which origins needs access to the resource
    'allowed_origins' => ['*', 'https://mywebsite.com', 'http://mywebsite.com'],

    // or use regex pattern, helpful if you want to grant
    // access to origins with certain pattern (i.e. an origin under a subdomain etc.)
    'allowed_origins_patterns' => ['/https?:\/\/mywebsite\.com\/?\z/'],

    // no changes made below
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];