<?php

declare(strict_types=1);

namespace Mileena\Web;

/**
 * An abstract utility class for handling user authentication and session management.
 *
 * Note: This class relies on a session being active. Ensure `session_start()` is called
 * before using any of these methods.
 */
abstract class Auth
{
    /**
     * Protects a page by ensuring only authenticated users can access it.
     * If the user is not logged in, they are redirected to the login page.
     */
    public static function protect(): void
    {
        if (!self::isLoggedIn()) {
            $app = WebApp::getInstance();
            // Redirect to the login page.
            header('Location: ' . $app->config->get('app.default_login_page'));
            exit;
        }
    }

    /**
     * Checks if a user is currently logged in.
     *
     * @return bool True if the user is authenticated, false otherwise.
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['auth']['user_id']);
    }

    /**
     * Retrieves the ID of the currently logged-in user.
     *
     * @return int|null The user's ID, or null if the user is not logged in.
     */
    public static function getUserId(): ?int
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        $userId = (int) $_SESSION['auth']['user_id'];

        return $userId > 0 ? $userId : null;
    }

    /**
     * Retrieves the username of the currently logged-in user.
     *
     * @return string|null The username, or null if the user is not logged in.
     */
    public static function getUsername(): ?string
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return $_SESSION['auth']['username'] ?? null;
    }

    /**
     * Checks whether the currently authenticated user has a specific role.
     *
     * @param string $role The role identifier to check against the authenticated user's role.
     * @return bool Returns true if the user is logged in and their role matches the given $role, false otherwise.
     */
    public static function checkRole(string $role): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        return $_SESSION['auth']['role'] === $role;
    }

    /**
     * Establishes a user session upon successful login.
     *
     * @param array{id: int, username: string, role?: string, debug?: false, allow?: array}|array{} $user An associative array containing user data.
     * @return bool Always returns true on success.
     */
    public static function login(array $user): bool
    {
        if ($user === []) {
            return false;
        }
        // Regenerate the session ID to prevent session fixation attacks.
        session_regenerate_id(true);
        $_SESSION = [];

        $_SESSION['auth'] = [
            'user_id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'role' => (string) (isset($user['role']) ? $user['role'] : 'member'),
            'debug' => (bool) ($user['debug'] ?? false),
            'allow' => (array) ($user['allow'] ?? []),
        ];

        return true;
    }

    /**
     * Checks if the current session belongs to an authenticated user.
     * Alias for isLoggedIn().
     *
     * @return bool True if the user is authenticated, false otherwise.
     */
    public static function isAuth(): bool
    {
        return self::isLoggedIn();
    }

    /**
     * Logs the user out by destroying the current session.
     */
    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
    }

    public static function canDebug(): bool
    {
        return !empty($_SESSION['allow']['debug']);
    }
}
