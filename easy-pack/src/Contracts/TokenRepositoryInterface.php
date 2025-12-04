<?php

namespace EasyPack\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Token Repository Interface
 * 
 * Abstracts token operations to allow easy swapping of implementations
 * when Sanctum or other auth packages change their APIs.
 */
interface TokenRepositoryInterface
{
    /**
     * Create a new access token for the user.
     *
     * @param Authenticatable $user
     * @param string $name Token name
     * @param array $abilities Token abilities/permissions
     * @param array $metadata Additional token metadata (device info, etc.)
     * @return array Contains 'token' (plain text) and 'accessToken' (model)
     */
    public function createToken(Authenticatable $user, string $name, array $abilities = ['*'], array $metadata = []): array;

    /**
     * Revoke a specific token.
     *
     * @param Authenticatable $user
     * @param int|string $tokenId
     * @return bool
     */
    public function revokeToken(Authenticatable $user, int|string $tokenId): bool;

    /**
     * Revoke all tokens for a user.
     *
     * @param Authenticatable $user
     * @return int Number of tokens revoked
     */
    public function revokeAllTokens(Authenticatable $user): int;

    /**
     * Revoke all tokens except the current one.
     *
     * @param Authenticatable $user
     * @param int|string $currentTokenId
     * @return int Number of tokens revoked
     */
    public function revokeOtherTokens(Authenticatable $user, int|string $currentTokenId): int;

    /**
     * Get all active tokens for a user.
     *
     * @param Authenticatable $user
     * @return \Illuminate\Support\Collection
     */
    public function getTokens(Authenticatable $user): \Illuminate\Support\Collection;

    /**
     * Find a token by its ID.
     *
     * @param Authenticatable $user
     * @param int|string $tokenId
     * @return object|null
     */
    public function findToken(Authenticatable $user, int|string $tokenId): ?object;

    /**
     * Update token metadata.
     *
     * @param object $token
     * @param array $metadata
     * @return bool
     */
    public function updateToken(object $token, array $metadata): bool;

    /**
     * Refresh/regenerate a token.
     *
     * @param object $token
     * @return string New token string
     */
    public function refreshToken(object $token): string;

    /**
     * Purge expired tokens.
     *
     * @return int Number of tokens purged
     */
    public function purgeExpiredTokens(): int;
}
