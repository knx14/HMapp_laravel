<?php

return [
'issuer' =>env('COGNITO_ISSUER'),
'client_id' =>env('COGNITO_CLIENT_ID'),
'jwks_url' =>env('COGNITO_ISSUER') .'/.well-known/jwks.json',
'jwks_cache_ttl_seconds' => (int)env('COGNITO_JWKS_CACHE_TTL_SECONDS',21600),
];