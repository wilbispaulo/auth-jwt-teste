<?php

namespace core\library;

use DateTime;
use Exception;
use DateTimeZone;
use app\models\Credential;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Checker\IssuerChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

class OAuth
{
    private string $userid;
    private string $secret;
    private ?JWK $privateKey = null;
    private ?JWK $publicKey = null;
    private array $claims = [];

    public function __construct(
        private ?string $publicPEM = null,
        private ?string $privatePEM = null
    ) {
        if (isset($this->publicPEM)) {
            $this->setPublicKey();
        }
        if (isset($this->privatePEM)) {
            $this->setPrivateKey();
        }
        if (count($p12 = glob(CERT . '*.p12')) > 0) {
            $this->setPairKeysP12($p12[0]);
        };
        if (count($cer = glob(CERT . '*.cer')) > 0) {
            $this->setPublicCER($cer[0]);
        };
    }

    public function setCredentials(string $userid, string $secret = '')
    {
        $this->userid = $userid;
        $this->secret = $secret;
    }

    public function setClaims(array $claims)
    {
        $this->claims = $claims;
    }

    public function genCredentials(string $userid)
    {
        $timeCred = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->getTimestamp();
        $credential['CLIENT_ID'] = self::uuidv4();
        $credentialPlainText = $userid . '#' . $credential['CLIENT_ID'] . '#' . (string)$timeCred;
        $credential['CLIENT_SECRET'] = base64_encode(password_hash($credentialPlainText, PASSWORD_BCRYPT));
        $credentials = new Credential();
        $arrayAssoc = [
            'clientid' => $credential['CLIENT_ID'],
            'timestamp' => $timeCred,
        ];
        if (count($credentials->findBy('username', $userid)) > 0) {
            $ok = $credentials->update($arrayAssoc, 'username', $userid);
        } else {
            $arrayAssoc['username'] = $userid;
            $ok = $credentials->create($arrayAssoc);
        };
        return ($ok === true) ? $credential : false;
    }

    public function verifyCredentials(): bool
    {
        $verify = false;
        $credential = base64_decode($this->secret);
        $credentials = new Credential();
        if (count($data = $credentials->findBy('clientid', $this->userid)) > 0) {
            $data = $data[0];
            $credentialPlainText = $data['username'] . '#' . $this->userid . '#' . (string)$data['timestamp'];
            $verify = password_verify($credentialPlainText, $credential);
            return $verify;
        } else {
            return false;
        }
        if (count($data) > 0) {
        }
    }

    public function tokenJWS()
    {
        $algoManager = new AlgorithmManager([
            new RS256(),
        ]);

        $jwsBuilder = new JWSBuilder($algoManager);
        $privateSSLKey = openssl_get_privatekey($this->privatePEM);

        $issCrypt = '';
        if (!$privateSSLKey) {
            return ['token' => 'PRIVATE KEY IS MISSING'];
        }
        openssl_private_encrypt($_ENV['ISSUER'], $issCrypt, $privateSSLKey);

        $baseClaims = [
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $_ENV['EXP_TOKEN'],
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

    public function loadJWS(string $tokenJws): array
    {
        try {
            $algoManager = new AlgorithmManager([
                new RS256(),
            ]);
            $jwsVerifier = new JWSVerifier($algoManager);
            $serializerManager = new JWSSerializerManager([
                new CompactSerializer(),
            ]);
            $jws = $serializerManager->unserialize($tokenJws);
            $isVerified = $jwsVerifier->verifyWithKey($jws, $this->publicKey, 0);

            if (!$isVerified) {
                return ['sig' => 'INVALID'];
            }
            $claims = $this->checkClaims($jws);
            if (in_array('EXPIRED', $claims)) {
                return ['token' => 'EXPIRED'];
            } else if (in_array('INVALID', $claims)) {
                return ['token' => 'INVALID'];
            }
            return ['token' => 'VALID'];
        } catch (Exception $e) {
            return ['token' => 'INVALID'];
        }
    }

    private function checkClaims(JWS $jws)
    {
        $checkClaim = [];
        $claims = json_decode(($jws->getPayload()), true);
        $publicSSL = openssl_get_publickey($this->publicPEM);
        openssl_public_decrypt(base64_decode($claims['iss']), $issuerPlain, $publicSSL);
        $claims['iss'] = $issuerPlain;

        $iss = $this->checkIssuer($claims);
        $exp = $this->checkExpiration($claims);
        $iat = $this->checkIssuedAt($claims);
        $nbf = $this->checkNotBefore($claims);
        $checkClaim = array_merge($checkClaim, $iss, $exp, $iat, $nbf);

        return $checkClaim;
    }

    private function checkExpiration(array $claims)
    {
        $clock = new StandardClock;
        $claimCheckerManager = new ClaimCheckerManager(
            [
                new ExpirationTimeChecker($clock),
            ]
        );
        try {
            $ok = $claimCheckerManager->check($claims);
            return count($ok) > 0 ? $ok : 'EXPIRED';
        } catch (Exception $e) {
            return ['exp' => 'EXPIRED'];
        }
    }

    private function checkIssuedAt(array $claims)
    {
        $clock = new StandardClock;
        $claimCheckerManager = new ClaimCheckerManager(
            [
                new IssuedAtChecker($clock),
            ]
        );
        try {
            $ok = $claimCheckerManager->check($claims);
            return count($ok) > 0 ? $ok : 'INVALID';
        } catch (Exception $e) {
            return ['iat' => 'INVALID'];
        }
    }

    private function checkNotBefore(array $claims)
    {
        $clock = new StandardClock;
        $claimCheckerManager = new ClaimCheckerManager(
            [
                new NotBeforeChecker($clock),
            ]
        );
        try {
            $ok = $claimCheckerManager->check($claims);
            return count($ok) > 0 ? $ok : 'INVALID';
        } catch (Exception $e) {
            return ['nbf' => 'INVALID'];
        }
    }

    private function checkIssuer(array $claims)
    {
        $claimCheckerManager = new ClaimCheckerManager(
            [
                new IssuerChecker([
                    $_ENV['ISSUER'],
                ]),
            ]
        );
        try {
            $ok = $claimCheckerManager->check($claims);
            return count($ok) > 0 ? $ok : 'INVALID';
        } catch (Exception $e) {
            return ['iss' => 'INVALID'];
        }
    }

    private function setPairKeysP12(string $pathToP12)
    {
        $certP12 = file_get_contents($pathToP12);
        openssl_pkcs12_read($certP12, $certPEM, $_ENV['CERT_SECRET']);
        $this->privatePEM = $certPEM['pkey'];
        $privateKey = openssl_pkey_get_private($certPEM['pkey']);
        $this->publicPEM = openssl_pkey_get_details($privateKey)['key'];
        $this->setPrivateKey();
        $this->setPublicKey();
    }

    private function setPublicCER(string $pathToCER)
    {
        $this->publicPEM = file_get_contents($pathToCER);
        $publicKey = JWKFactory::createFromCertificateFile($pathToCER);
        $this->publicKey = $publicKey;
    }

    private function setPrivateKey()
    {
        $this->privateKey = JWKFactory::createFromKey(
            $this->privatePEM,
            null,
            [
                'alg' => 'RS256',
                'use' => 'sig'
            ]
        );
    }

    private function setPublicKey()
    {
        $this->publicKey = JWKFactory::createFromKey(
            $this->publicPEM,
            null,
            [
                'alg' => 'RS256',
                'use' => 'sig'
            ]
        );
    }

    public function getClaims(): array
    {
        return $this->claims;
    }

    public static function uuidv4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function getBearerToken()
    {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            preg_match('/Bearer(?P<token>.*)/', $headers['Authorization'], $token);
            return trim($token['token']);
        }
        return false;
    }
}
