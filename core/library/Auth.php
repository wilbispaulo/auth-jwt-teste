<?php

namespace core\library;

use DateTime;
use Exception;
use DateTimeZone;
use app\models\User;
use app\models\Grant;
use core\library\Filters;
use app\models\Credential;
use Jose\Component\Checker;
use Jose\Component\Core\JWK;
use core\library\StandardClock;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Checker\IssuerChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

class Auth
{
    private int $auth = 0;
    private string $userid;
    private string $secret;
    private string $fieldid;

    public function __construct(
        private User $userObj,
        private ?Grant $grantObj = null,
    ) {}

    public function setCredentials(string $userid, string $secret = '', string $fieldid = '')
    {
        $this->userid = $userid;
        $this->secret = $secret;
        $this->fieldid = $fieldid;
    }

    public function Auth(): int | false
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
        return $this->auth;
    }

    public function Autho(string $idModule): string | false
    {
        if (!isset($this->grantObj)) {
            return false;
        }

        $table1 = $this->userObj->getTable();
        $table2 = $this->grantObj->getTable();
        $filter = new Filters();
        $filter->join(
            $table2,
            null,
            "{$table1}.idusuario",
            "=",
            "{$table2}.idusuario"
        );
        $filter->where("{$table1}.idusuario", "=", User::getUserid(), "and");
        $filter->where("{$table2}.idmodulo", "=", $idModule);
        $this->userObj->setFilters($filter);
        $this->userObj->setFields('crudval');
        $userAutho = $this->userObj->findBy();

        return $userAutho[0]['crudval'] ?? false;
    }

    public function getLevel(): int
    {
        return $this->userObj->findBy('idusuario', User::getUserid())[0]['nivel'];
    }

    public function getUsername(): string
    {
        return $this->userObj->getUsername();
    }

    public function getAllUserData(): array
    {
        return (array)$this->userObj;
    }

    public function getUserData(string $field): string|bool
    {
        return $this->userObj->$field ?? false;
    }

    public function isAutho(string $idModule, string $method): bool
    {
        if ($this->getLevel() === 9999) {
            return true;
        }
        if (($crudval = $this->Autho($idModule)) === false) {
            return false;
        }
        return str_contains($crudval, $method);
    }
}
