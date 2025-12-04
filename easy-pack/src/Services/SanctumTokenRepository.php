<?php

namespace EasyPack\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use EasyPack\Contracts\TokenRepositoryInterface;
use EasyPack\Models\PersonalAccessToken;

/**
 * Sanctum Token Repository
 * 
 * Implementation of TokenRepositoryInterface using Laravel Sanctum.
 * This can be swapped out for different auth token systems in the future.
 */
class SanctumTokenRepository implements TokenRepositoryInterface
{
    /**
     * Create a new access token for the user.
     */
    public function createToken(Authenticatable $user, string $name, array $abilities = ['*'], array $metadata = []): array
    {
        // Generate device ID if not provided
        if (!isset($metadata['device_id'])) {
            $metadata['device_id'] = Str::uuid()->toString();
        }

        // Create token with device metadata
        $token = $user->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
            'device_id' => $metadata['device_id'] ?? null,
            'device_type' => $metadata['device_type'] ?? null,
            'device_name' => $metadata['device_name'] ?? null,
            'device_push_token' => $metadata['device_push_token'] ?? null,
            'ip_address' => $metadata['ip_address'] ?? request()->ip(),
            'user_agent' => $metadata['user_agent'] ?? request()->userAgent(),
            'last_used_at' => now(),
            'expires_at' => $metadata['expires_at'] ?? now()->addDays(config('sanctum.expiration', 365)),
        ]);

        return [
            'token' => $token->getKey() . '|' . $plainTextToken,
            'accessToken' => $token,
        ];
    }

    /**
     * Revoke a specific token.
     */
    public function revokeToken(Authenticatable $user, int|string $tokenId): bool
    {
        $token = $user->tokens()->where('id', $tokenId)->first();
        
        if ($token) {
            return (bool) $token->delete();
        }

        return false;
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAllTokens(Authenticatable $user): int
    {
        return $user->tokens()->delete();
    }

    /**
     * Revoke all tokens except the current one.
     */
    public function revokeOtherTokens(Authenticatable $user, int|string $currentTokenId): int
    {
        return $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();
    }

    /**
     * Get all active tokens for a user.
     */
    public function getTokens(Authenticatable $user): Collection
    {
        return $user->tokens()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('last_used_at')
            ->get();
    }

    /**
     * Find a token by its ID.
     */
    public function findToken(Authenticatable $user, int|string $tokenId): ?object
    {
        return $user->tokens()->where('id', $tokenId)->first();
    }

    /**
     * Update token metadata.
     */
    public function updateToken(object $token, array $metadata): bool
    {
        $updateData = array_intersect_key($metadata, array_flip([
            'device_name',
            'device_push_token',
            'ip_address',
            'user_agent',
            'last_used_at',
            'topic_subscriptions',
        ]));

        return $token->update($updateData);
    }

    /**
     * Refresh/regenerate a token.
     */
    public function refreshToken(object $token): string
    {
        $plainTextToken = Str::random(40);
        
        $token->update([
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(config('sanctum.expiration', 365)),
            'last_used_at' => now(),
        ]);

        return $token->getKey() . '|' . $plainTextToken;
    }

    /**
     * Purge expired tokens.
     */
    public function purgeExpiredTokens(): int
    {
        return PersonalAccessToken::where('expires_at', '<', now())->delete();
    }
}
