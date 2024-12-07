<?php

namespace core\library;

class Session
{
    public static function init(int $lifeTime = 0, bool $httpOnly = true)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $options = OPTIONS;
            $options['lifetime'] = $lifeTime;
            session_set_cookie_params($options);
            session_start();
            session_regenerate_id(true);
        }
    }

    public static function load()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    public static function set(string $index, mixed $value)
    {
        $_SESSION[$index] = $value;
    }

    public static function has(string $index)
    {
        return isset($_SESSION[$index]);
    }

    public static function hasCookie(string $index)
    {
        return isset($_COOKIE[$index]);
    }

    public static function expiredSession(): bool
    {
        if (!$expires = self::getSessionCookie()) {
            return true;
        }
        return !(time() < (int)$expires);
    }

    public static function get(string $index)
    {
        if (self::has($index)) {
            return $_SESSION[$index];
        }
        return false;
    }

    public static function getCookie(string $index)
    {
        if (self::hasCookie($index)) {
            return $_COOKIE[$index];
        }
        return false;
    }

    public static function remove(string $index)
    {
        if (self::has($index)) {
            unset($_SESSION[$index]);
        }
    }

    public static function removeCookie(string $index)
    {
        if (self::hasCookie($index)) {
            $options = OPTIONS;
            $options["expires"] = time() - 86400;
            setcookie($index, "", $options);
        }
    }

    public static function removeAll()
    {
        session_destroy();
    }

    public static function setSessionCookie(int $expiresSession, int $expiresCookie)
    {
        $iv = base64_decode($_ENV['TKRS_IV']);
        $tokenSessionExp = openssl_encrypt((string)$expiresSession, $_ENV['TKRS_ALGO'], $_ENV['TKRS_PASS'], 0, $iv);
        $options = OPTIONS;
        $options['expires'] = $expiresCookie;
        setcookie($_ENV['SESSION_COOKIE'], $tokenSessionExp, $options);
    }

    public static function setUserCookie(array $user, int $expiresCookie)
    {
        $useridStr = json_encode($user);
        $iv = base64_decode($_ENV['TKRS_IV']);
        $tokenUserid = openssl_encrypt($useridStr, $_ENV['TKRS_ALGO'], $_ENV['TKRS_PASS'], 0, $iv);
        $options = OPTIONS;
        $options['expires'] = $expiresCookie;
        setcookie($_ENV['USER_COOKIE'], $tokenUserid, $options);
    }

    public static function getUserCookie(): array | false
    {
        if (!$userCookie = self::getCookie($_ENV['USER_COOKIE'])) {
            return false;
        };
        $iv = base64_decode($_ENV['TKRS_IV']);
        return json_decode(openssl_decrypt($userCookie, $_ENV['TKRS_ALGO'], $_ENV['TKRS_PASS'], 0, $iv), true);
    }

    public static function getSessionCookie(): string | false
    {
        if (!$sessionCookie = self::getCookie($_ENV['SESSION_COOKIE'])) {
            return false;
        };
        $iv = base64_decode($_ENV['TKRS_IV']);
        return openssl_decrypt($sessionCookie, $_ENV['TKRS_ALGO'], $_ENV['TKRS_PASS'], 0, $iv);
    }

    public static function getUsernameCookie(): string
    {
        $user = self::getUserCookie();
        return $user['USERNAME'] !== '' ? $user['USERNAME'] : substr($user['USERID'], 0, -strpos($user['USERID'], '@') - 1);
    }
}
