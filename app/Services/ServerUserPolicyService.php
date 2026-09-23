<?php

namespace App\Services;

use App\Models\ProxyServerConfig;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

/**
 * Resolves whether the current GUI user is an admin on a given proxy server.
 * Matches the authenticated user to the server's "users" resource by unique_id or email,
 * then reads the server's "admin" column (boolean).
 */
class ServerUserPolicyService
{
    /**
     * Match GUI user to server user by unique_id first, then email.
     * Server users from GET /api/users are expected to have: unique_id, email, admin.
     */
    public function isAdminForServer(?string $serverSlug): bool
    {
        if (!$serverSlug) {
            return false;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $cacheKey = "server_admin:{$serverSlug}:{$user->id}";
        $request = request();

        if ($request && $request->attributes->has('_server_admin_' . $serverSlug)) {
            return (bool) $request->attributes->get('_server_admin_' . $serverSlug);
        }

        $serverUsers = $this->fetchServerUsers($serverSlug);
        if ($serverUsers === null) {
            return false;
        }

        $serverUser = $this->findMatchingServerUser($serverUsers, $user);
        $isAdmin = $serverUser !== null && $this->readAdminValue($serverUser);

        if ($request) {
            $request->attributes->set('_server_admin_' . $serverSlug, $isAdmin);
        }

        return $isAdmin;
    }

    /**
     * Fetch users list from the server (GET /api/users).
     *
     * @return array|null List of server users or null on failure
     */
    protected function fetchServerUsers(string $serverSlug): ?array
    {
        $config = ProxyServerConfig::where('slug', $serverSlug)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return null;
        }

        $user = Auth::user();
        $baseUrl = rtrim($config->server, '/');
        $fullUrl = "{$baseUrl}/api/users";

        try {
            $response = Http::withBasicAuth($config->username, $config->getDecryptedPassword())
                ->withHeaders([
                    'X-Unique-Id' => $user->unique_id,
                    'Accept' => 'application/json',
                ])
                ->get($fullUrl);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Find server user that matches the given GUI user (by unique_id or email).
     *
     * @param array $serverUsers List of server user arrays
     * @param User $guiUser
     * @return array|null The matching server user row or null
     */
    protected function findMatchingServerUser(array $serverUsers, User $guiUser): ?array
    {
        foreach ($serverUsers as $row) {
            if (!is_array($row)) {
                continue;
            }
            $uniqueId = $row['unique_id'] ?? $row['unique_uid'] ?? null;
            $email = $row['email'] ?? null;
            if ($uniqueId !== null && (string) $uniqueId === (string) $guiUser->unique_id) {
                return $row;
            }
            if ($email !== null && strcasecmp((string) $email, (string) $guiUser->email) === 0) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Read admin flag from server user row (supports boolean or 0/1).
     */
    protected function readAdminValue(array $serverUser): bool
    {
        $admin = $serverUser['admin'] ?? $serverUser['is_admin'] ?? false;
        return $admin === true || $admin === 1 || $admin === '1';
    }
}
