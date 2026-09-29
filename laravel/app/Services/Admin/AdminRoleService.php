<?php

namespace App\Services\Admin;

use App\Models\AdminRoleEvent;
use App\Models\AppUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminRoleService
{
    public function isGrantKeyConfigured(): bool
    {
        return filled(config('admin.grant_key_hash'));
    }

    public function grantKeyMatches(string $key): bool
    {
        $hash = (string) config('admin.grant_key_hash');
        if ($hash === '') {
            return false;
        }

        try {
            return Hash::check($key, $hash);
        } catch (\RuntimeException $e) {
            Log::error('ADMIN_GRANT_KEY_HASH is not a valid bcrypt hash', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param  array{ip_address?: string|null, user_agent?: string|null}  $context
     */
    public function grant(AppUser $user, array $context = []): void
    {
        DB::transaction(function () use ($user, $context) {
            $user->forceFill([
                'role' => AppUser::ROLE_ADMIN,
                'admin_granted_at' => now(),
            ])->save();

            $this->record($user, AdminRoleEvent::ACTION_GRANTED, $user, $context);
        });
    }

    /**
     * @param  array{ip_address?: string|null, user_agent?: string|null}  $context
     */
    public function revoke(AppUser $user, ?AppUser $actor, array $context = []): void
    {
        DB::transaction(function () use ($user, $actor, $context) {
            $user->forceFill([
                'role' => AppUser::ROLE_USER,
                'admin_granted_at' => null,
            ])->save();

            $this->record($user, AdminRoleEvent::ACTION_REVOKED, $actor, $context);
        });
    }

    /**
     * @param  array{ip_address?: string|null, user_agent?: string|null}  $context
     */
    public function recordGrantFailure(AppUser $user, array $context = []): void
    {
        $this->record($user, AdminRoleEvent::ACTION_GRANT_FAILED, $user, $context);
    }

    /**
     * @param  array{ip_address?: string|null, user_agent?: string|null}  $context
     */
    private function record(AppUser $user, string $action, ?AppUser $actor, array $context): void
    {
        AdminRoleEvent::create([
            'app_user_id' => $user->id,
            'action' => $action,
            'actor_app_user_id' => $actor?->id,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => isset($context['user_agent']) ? mb_substr((string) $context['user_agent'], 0, 512) : null,
        ]);

        Log::info('Admin role event', [
            'app_user_id' => $user->id,
            'action' => $action,
            'actor_app_user_id' => $actor?->id,
            'ip_address' => $context['ip_address'] ?? null,
        ]);
    }
}
