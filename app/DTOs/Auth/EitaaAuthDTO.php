<?php

namespace App\DTOs\Auth;

class EitaaAuthDTO
{
    public function __construct(
        public readonly string $eitaaData,
        public readonly ?string $deviceName = null,
        public readonly ?string $deviceType = null,
        public readonly ?string $platform = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            eitaaData: $data['eitaa_data'],
            deviceName: $data['device_name'] ?? null,
            deviceType: $data['device_type'] ?? null,
            platform: $data['platform'] ?? null,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );
    }
}

class AuthResponseDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $accessToken = null,
        public readonly ?string $refreshToken = null,
        public readonly ?string $tokenType = 'Bearer',
        public readonly ?int $expiresIn = null,
        public readonly ?string $expiresAt = null,
        public readonly ?array $user = null,
        public readonly ?string $message = null,
        public readonly bool $isNewUser = false,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt,
            'user' => $this->user,
            'message' => $this->message,
            'is_new_user' => $this->isNewUser,
        ], fn($value) => !is_null($value));
    }
}

class RefreshTokenDTO
{
    public function __construct(
        public readonly string $refreshToken,
        public readonly ?string $deviceName = null,
        public readonly ?string $ipAddress = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            refreshToken: $data['refresh_token'],
            deviceName: $data['device_name'] ?? null,
            ipAddress: request()->ip(),
        );
    }
}

class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $displayName,
        public readonly ?string $username = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $avatar = null,
        public readonly string $status = 'active',
        public readonly ?string $createdAt = null,
    ) {}

    public static function fromModel($user): self
    {
        return new self(
            id: $user->id,
            displayName: $user->display_name,
            username: $user->username,
            email: $user->email,
            phone: $user->phone,
            avatar: $user->avatar,
            status: $user->status,
            createdAt: $user->created_at?->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->displayName,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
