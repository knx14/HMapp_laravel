<?php

namespace App\Services\Cognito;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class JwksProvider
{
    public function getJwks(): array
    {
        $url = config('cognito.jwks_url');
        $ttl = config('cognito.jwks_cache_ttl_seconds');

        return Cache::remember('cognito_jwks', $ttl, function () use ($url) {
            $res = Http::timeout(5)->get($url);
            $res->throw();
            return $res->json(); // { keys: [...] }
        });
    }
}