<?php

namespace core\library;

use app\models\User;

class Auth
{
    private int $auth = 0;
    private string $userid;
    private string $secret;
    private string $fieldid;

    public function __construct(
        private User $userObj
    ) {}

    public function setCredentials(string $userid, string $secret = '', string $fieldid = '')
    {
        $this->userid = $userid;
        $this->secret = $secret;
        $this->fieldid = $fieldid;
    }

    public function Auth(): array
    {
        if (!isset($this->userid) or !isset($this->secret) or !isset($this->fieldid)) {
            return false;
        }
        $userFound = $this->userObj->findByObj($this->fieldid, $this->userid);
        if ($userFound !== false) {
            $this->auth += 1;
            $this->userObj = $userFound;
        }
        if ($this->auth > 0 and $userFound->password != null) {
            password_verify($this->secret, $userFound->password) ? $this->auth += 2 : null;
        }
        if ($this->auth < 3) {
            $result = [
                'password' => 'FAIL'
            ];
        } else if ($this->auth < 1) {
            $result = [
                'username' => 'NOT_FOUND'
            ];
        } else {
            $result = [
                'auth' => 'OK'
            ];
        }
        return $result;
    }

    public function getUserid()
    {
        return $this->userid;
    }

    public function getLevel(string $username): int
    {
        return $this->userObj->findBy('email', $username)[0]['nivel'];
    }

    public function getAllUserData(): array
    {
        return (array)$this->userObj;
    }

    public function getUserData(string $field): string|bool
    {
        return $this->userObj->$field ?? false;
    }
}
