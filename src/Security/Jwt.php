<?php
// src/Security/Jwt.php
declare(strict_types=1);
namespace App\Security;

use Firebase\JWT\JWT as LibJWT;
use Firebase\JWT\Key;

final class Jwt
{
    /**
     * @param string $secret The secret key to use for encoding and decoding
     * @param int $ttlSeconds The TTL (time to live) for the JWT in seconds. Defaults to 1 hour.
     * @param string $algo The algorithm to use for encoding and decoding. Defaults to HS256.
     */
    public function __construct(
        private string $secret,
        private int $ttlSeconds = 3600,
        private string $algo = 'HS256'
    ) {
        if (empty($this->secret)) {
            throw new \InvalidArgumentException('JWT secret cannot be empty');
        }
        if ($this->ttlSeconds <= 0) {
            throw new \InvalidArgumentException('JWT TTL must be greater than 0');
        }
        if (!in_array($this->algo, ['HS256', 'HS384', 'HS512'])) {
            throw new \InvalidArgumentException('JWT algorithm must be one of HS256, HS384, or HS512');
        }
        $this->secret = $secret;
        $this->ttlSeconds = $ttlSeconds;
        $this->algo = $algo;
    }

    /**
     * Issue a JWT token.
     *
     * @param array $claims The array of claims to encode into the JWT.
     * @return string The JWT token.
     */
    public function issue(array $claims): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ]);
        return LibJWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Verify a JWT token.
     *
     * @param string $token The JWT token to verify.
     * @return array The decoded claims.
     * @throws \Firebase\JWT\ExpiredException If the token has expired.
     * @throws \Firebase\JWT\SignatureInvalidException If the token signature is invalid.
     * @throws \Firebase\JWT\BeforeValidException If the token is not yet valid.
     * @throws \DomainException If the token is invalid or malformed.
     */
    public function verify(string $token): array
    {
        // decode the token
        $decoded = LibJWT::decode($token, new Key($this->secret, $this->algo));
        // cast stdClass -> array
        return json_decode(json_encode($decoded), true);
    }
}
