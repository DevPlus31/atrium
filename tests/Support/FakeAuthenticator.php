<?php

declare(strict_types=1);

namespace Tests\Support;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;

/**
 * A deterministic software WebAuthn authenticator for feature tests.
 *
 * It completes registration (attestation) and login (assertion) ceremonies
 * against the options issued by the server, signing with a P-256 keypair
 * exactly like a real platform authenticator would.
 */
final class FakeAuthenticator
{
    private const int FLAG_USER_PRESENT = 0x01;

    private const int FLAG_USER_VERIFIED = 0x04;

    private const int FLAG_ATTESTED_CREDENTIAL_DATA = 0x40;

    private readonly OpenSSLAsymmetricKey $key;

    private readonly string $credentialId;

    private string $userHandle = '';

    public function __construct(private readonly string $origin = 'http://localhost')
    {
        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        throw_if($key === false, RuntimeException::class, 'Unable to generate a P-256 keypair.');

        $this->key = $key;
        $this->credentialId = random_bytes(32);
    }

    /**
     * The base64url credential id, as stored in the passkeys table.
     */
    public function credentialId(): string
    {
        return Base64UrlSafe::encodeUnpadded($this->credentialId);
    }

    /**
     * Build the browser payload for POST /user/passkeys from the creation
     * options returned by GET /user/passkeys/options.
     *
     * @param  array{challenge: string, rp: array{id: string}, user: array{id: string}}  $options
     * @return array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, attestationObject: string, transports: list<string>}}
     */
    public function attest(array $options): array
    {
        $this->userHandle = Base64UrlSafe::decodeNoPadding($options['user']['id']);

        $clientDataJson = $this->clientDataJson('webauthn.create', $options['challenge']);

        $flags = self::FLAG_USER_PRESENT | self::FLAG_USER_VERIFIED | self::FLAG_ATTESTED_CREDENTIAL_DATA;

        $authData = hash('sha256', $options['rp']['id'], true)
            .chr($flags)
            .pack('N', 0)
            .str_repeat("\0", 16)
            .pack('n', mb_strlen($this->credentialId, '8bit'))
            .$this->credentialId
            .$this->cosePublicKey();

        $attestationObject = (string) MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientDataJson),
                'attestationObject' => Base64UrlSafe::encodeUnpadded($attestationObject),
                'transports' => ['internal'],
            ],
        ];
    }

    /**
     * Build the browser payload for POST /passkeys/login from the request
     * options returned by GET /passkeys/login/options.
     *
     * @param  array{challenge: string, rpId: string}  $options
     * @return array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, authenticatorData: string, signature: string, userHandle: string}}
     */
    public function assert(array $options): array
    {
        $clientDataJson = $this->clientDataJson('webauthn.get', $options['challenge']);

        $authenticatorData = hash('sha256', $options['rpId'], true)
            .chr(self::FLAG_USER_PRESENT | self::FLAG_USER_VERIFIED)
            .pack('N', 1);

        $signed = openssl_sign(
            $authenticatorData.hash('sha256', $clientDataJson, true),
            $signature,
            $this->key,
            OPENSSL_ALGO_SHA256,
        );

        throw_unless($signed, RuntimeException::class, 'Unable to sign the assertion.');

        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientDataJson),
                'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
                'signature' => Base64UrlSafe::encodeUnpadded($signature),
                'userHandle' => Base64UrlSafe::encodeUnpadded($this->userHandle),
            ],
        ];
    }

    /**
     * The collected client data for a ceremony, echoing the base64url
     * challenge issued by the server.
     */
    private function clientDataJson(string $type, string $challenge): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $challenge,
            'origin' => $this->origin,
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * The COSE EC2 (ES256, P-256) public key for the attested credential data.
     */
    private function cosePublicKey(): string
    {
        $details = openssl_pkey_get_details($this->key);

        throw_if($details === false || ! isset($details['ec']['x'], $details['ec']['y']), RuntimeException::class, 'Unable to extract the EC public key coordinates.');

        return (string) MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create(mb_str_pad((string) $details['ec']['x'], 32, "\0", STR_PAD_LEFT, '8bit')))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create(mb_str_pad((string) $details['ec']['y'], 32, "\0", STR_PAD_LEFT, '8bit')));
    }
}
