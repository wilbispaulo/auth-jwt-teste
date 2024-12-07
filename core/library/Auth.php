<?php

namespace core\library;

use DateTime;
use DateTimeZone;
use app\models\User;
use app\models\Grant;
use core\library\Filters;
use app\models\Credential;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWS;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Checker;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\KeyManagement\JWKFactory;
// use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\IssuerChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\KeyManagement\KeyConverter\KeyConverter;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;

class Auth
{
    private int $auth = 0;
    private string $userid;
    private string $secret;
    private string $fieldid;
    private JWK $privateKey;
    private JWK $publicKey;
    private array $claims = [];

    public function __construct(
        private User $userObj,
        private ?Grant $grantObj = null
    ) {}

    public function setCredentials(string $userid, string $secret = '', string $fieldid = '')
    {
        $this->userid = $userid;
        $this->secret = $secret;
        $this->fieldid = $fieldid;
    }

    public function setClaims(array $claims)
    {
        $this->claims = $claims;
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

    public function genCredentials()
    {
        $timeCred = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->getTimestamp();
        $credential['CLIENT_ID'] = self::uuidv4();
        $credentialPlainText = $this->userid . '#' . $credential['CLIENT_ID'] . '#' . (string)$timeCred;
        $credential['CLIENT_SECRET'] = base64_encode(password_hash($credentialPlainText, PASSWORD_BCRYPT));
        $credentials = new Credential();
        $arrayAssoc = [
            'clientid' => $credential['CLIENT_ID'],
            'timestamp' => $timeCred,
        ];
        if (count($credentials->findBy('username', $this->userid)) > 0) {
            $ok = $credentials->update($arrayAssoc, 'username', $this->userid);
        } else {
            $arrayAssoc['username'] = $this->userid;
            $ok = $credentials->create($arrayAssoc);
        };
        return $ok === true ? $credential : false;
    }

    public function verifyCredentials(): bool
    {
        $verify = false;
        $credential = base64_decode($this->secret);
        $credentials = new Credential();
        $data = $credentials->findBy('clientid', $this->userid)[0];
        if (count($data) > 0) {
            $credentialPlainText = $data['username'] . '#' . $this->userid . '#' . (string)$data['timestamp'];
            $verify = password_verify($credentialPlainText, $credential);
        }
        return $verify;
    }

    public function tokenJWS()
    {
        $algoManager = new AlgorithmManager([
            new RS256(),
        ]);

        $this->setPrivateKey();

        $jwsBuilder = new JWSBuilder($algoManager);
        $privateSSLKey = openssl_get_privatekey($_ENV['PRIVATE_PEM']);

        $issCrypt = '';
        openssl_private_encrypt($_ENV['ISSUER'], $issCrypt, $privateSSLKey);

        $baseClaims = [
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'iss' => base64_encode($issCrypt),
        ];

        $claims = array_merge($baseClaims, $this->claims);
        $payload = json_encode($claims);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature(
                $this->privateKey,
                [
                    'alg' => 'RS256',
                    'typ' => 'JWT',
                ]
            )
            ->build();

        $serializer = new CompactSerializer;
        return $serializer->serialize($jws, 0);
    }

    public function loadJWS(string $tokenJws)
    {
        $algoManager = new AlgorithmManager([
            new RS256(),
        ]);

        $this->setPublicKey();

        $jwsVerifier = new JWSVerifier($algoManager);
        $serializerManager = new JWSSerializerManager([
            new CompactSerializer(),
        ]);

        $jws = $serializerManager->unserialize($tokenJws);
        $isVerified = $jwsVerifier->verifyWithKey($jws, $this->publicKey, 0);

        if (!$isVerified) {
            return false;
        }

        return $this->checkClaims($jws);
    }

    private function checkClaims(JWS $jws)
    {
        $checkClaim = [];
        $claims = json_decode(($jws->getPayload()), true);
        $publicSSL = openssl_get_publickey($_ENV['PUBLIC_PEM']);
        openssl_public_decrypt(base64_decode($claims['iss']), $issuerPlain, $publicSSL);
        $claims['iss'] = $issuerPlain;
        if ($claims['iss'] !== $_ENV['ISSUER']) {
            $checkClaim['issuer'] = 'INVALID';
        }
        return count($checkClaim) === 0 ? $claims : $checkClaim;
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

    public function getClaims(): array
    {
        return $this->claims;
    }

    public function getPrivateKey()
    {
        return $this->privateKey->all();
    }

    public function getPublicKey()
    {
        return $this->privateKey->toPublic();
    }

    public function setPrivateKey()
    {
        $this->privateKey = JWKFactory::createFromKey(
            $_ENV['PRIVATE_PEM'],
            null,
            [
                'alg' => 'RS256',
                'use' => 'sig'
            ]
        );
    }

    public function setPublicKey()
    {
        $this->publicKey = JWKFactory::createFromKey(
            $_ENV['PUBLIC_PEM'],
            null,
            [
                'alg' => 'RS256',
                'use' => 'sig'
            ]
        );
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

    public static function uuidv4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
