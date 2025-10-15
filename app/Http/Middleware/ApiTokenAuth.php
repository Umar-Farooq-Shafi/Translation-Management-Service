<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');
        if (! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $tokenPlain = substr($authHeader, 7);
        if ($tokenPlain === '') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Constant-time hash check
        $tokens = ApiToken::query()->get(['id', 'token_hash']);
        $matched = null;
        foreach ($tokens as $token) {
            if (hash_equals($token->token_hash, hash('sha256', $tokenPlain))) {
                $matched = $token;
                break;
            }
        }

        if (! $matched) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $matched->forceFill(['last_used_at' => now()])->saveQuietly();

        // Optionally set request user context here if you add Users later
        return $next($request);
    }
}
