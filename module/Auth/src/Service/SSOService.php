<?php
namespace Auth\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Json\Json;
use Laminas\Session\Container;

class SSOService
{
    private array $config;
    private Client $httpClient;
    private Container $session;

    public function __construct(array $config)
    {
        $this->httpClient = new Client();
        $this->session    = new Container('sso_auth');
        $this->config     = $config;

        // Initialize nested config keys if not set
        if (! isset($this->config['openid_config'])) {
            $this->config['openid_config'] = [];
        }
    }

    /**
     * Lazy-load OpenID configuration from the SSO provider
     * This is called only when needed, not during construction
     */
    private function ensureOpenIdConfigLoaded(): void
    {
        // Return early if config is already loaded
        if (! empty($this->config['openid_config'])) {
            return;
        }

        try {
            // Send request to fetch OpenID configuration
            $request = new Request();
            $request->setUri($this->config['sso']['openid_configuration_uri']);
            $request->setMethod(Request::METHOD_GET);
            $response = $this->httpClient->send($request);

            if (! $response->isSuccess()) {
                throw new \Exception('Failed to fetch OpenID configuration: ' . $response->getReasonPhrase());
            }

            // Decode response
            $discovered = json_decode($response->getBody(), true);

            // Add keys from discovered config without overwriting
            foreach ($discovered as $key => $value) {
                if (! array_key_exists($key, $this->config['openid_config'])) {
                    $this->config['openid_config'][$key] = $value;
                }
            }
        } catch (\Exception $e) {
            error_log('[SSO] Error loading OpenID configuration: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAuthorizationUrl(string $state = null): string
    {
        // Ensure OpenID configuration is loaded before using it
        $this->ensureOpenIdConfigLoaded();

        // Generate PKCE parameters
        $pkce = $this->generatePkceParameters();

        // Store in session for later verification
        $this->session->state         = $state;
        $this->session->code_verifier = $pkce['code_verifier'];

        $params = [
            'client_id'             => $this->config['sso']['client_id'],
            'response_type'         => $this->config['openid_config']['response_types_supported'][0],
            'scope'                 => $this->config['openid_config']['scopes_supported'][0],
            'redirect_uri'          => $this->config['sso']['redirect_uri'],
            'state'                 => $state,
            'code_challenge'        => $pkce['code_challenge'],
            'code_challenge_method' => $this->config['openid_config']['code_challenge_methods_supported'][0],
        ];

        if ($state) {
            $params['state'] = $state;
        }

        $url = $this->config['openid_config']['authorization_endpoint'] . '?' . http_build_query($params);
        error_log('[SSO] Full authorization URL (with state via HTTPS): ' . $url);
        
        return $url;
    }

    public function generatePkceParameters(): array
    {
                                         // Generate a random 32-byte binary string
        $randomBytes = random_bytes(32); // Safe: 32 bytes ≈ 43 characters when base64url encoded

        // base64url-encode the code_verifier (RFC 7636)
        $codeVerifier = $this->base64UrlEncode($randomBytes);

        // Generate code_challenge: SHA256 → base64url
        $codeChallenge = $this->base64UrlEncode(hash('sha256', $codeVerifier, true));

        return [
            'code_verifier'  => $codeVerifier,
            'code_challenge' => $codeChallenge,
        ];
    }

    /**
     * Base64 URL encode
     */
    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function exchangeCodeForToken(string $code, string $codeVerifier): array
    {
        // Ensure OpenID configuration is loaded before using it
        $this->ensureOpenIdConfigLoaded();

        $this->httpClient->setUri($this->config['openid_config']['token_endpoint']);
        $this->httpClient->setMethod('POST');
        $this->httpClient->setParameterPost([
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->config['sso']['client_id'],
            'code'          => $code,
            'redirect_uri'  => $this->config['sso']['redirect_uri'],
            'code_verifier' => $codeVerifier,
        ]);

        $response = $this->httpClient->send();

        if (! $response->isSuccess()) {
            throw new \Exception('Failed to exchange code for token: ' . $response->getBody());
        }

        return Json::decode($response->getBody(), Json::TYPE_ARRAY);
    }

    public function getUserInfo(string $accessToken): array
    {
        // Ensure OpenID configuration is loaded before using it
        $this->ensureOpenIdConfigLoaded();

        $this->httpClient->setUri($this->config['openid_config']['userinfo_endpoint']);
        $this->httpClient->setMethod('GET');
        $this->httpClient->setHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ]);

        $response = $this->httpClient->send();

        if (! $response->isSuccess()) {
            throw new \Exception('Failed to get user info: ' . $response->getBody());
        }
        return Json::decode($response->getBody(), Json::TYPE_ARRAY);
    }

    public function verifyJwtToken(string $token, string $secret): array
    {
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid JWT token: ' . $e->getMessage());
        }
    }

    public function generateState(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function getLogoutUrl(string $postLogoutRedirectUri = null): string
    {
        // Ensure OpenID configuration is loaded before using it
        $this->ensureOpenIdConfigLoaded();

        if (! isset($this->config['openid_config']['end_session_endpoint']) || empty($this->config['openid_config']['end_session_endpoint'])) {
            return $postLogoutRedirectUri ?? '/';
        }

        $params = [
            'client_id' => $this->config['sso']['client_id'],
        ];

        if ($postLogoutRedirectUri) {
            $params['post_logout_redirect_uri'] = $postLogoutRedirectUri;
        }

        // Add ID token
        $idToken = $this->session->offsetGet($this->config['session_keys']['id_token']);
        if ($idToken) {
            $params['id_token_hint'] = $idToken;
        }

        return $this->config['openid_config']['end_session_endpoint'] . '?' . http_build_query($params);
    }

    public function initializeConfig(array $config)
    {
        // This method is now superseded by lazy loading in ensureOpenIdConfigLoaded()
        // But keeping it for backward compatibility
        $this->config = $config;
        
        // Initialize config
        if (! isset($this->config['openid_config'])) {
            $this->config['openid_config'] = [];
        }

        try {
            $this->ensureOpenIdConfigLoaded();
        } catch (\Exception $e) {
            // Log error but don't fail - OpenID config will be loaded on first use
            error_log('[SSO] Could not preload OpenID config: ' . $e->getMessage());
        }

        return $this->config;
    }

    public function getSSOLoginURL()
    {

        // Generate and store state for CSRF protection
        $state = $this->generateState();
        $this->session->state = $state;
        

        // Get authorization URL
        $authUrl = $this->getAuthorizationUrl($state);
        //echo '<pre>';print_r($authUrl);exit;
        return $authUrl;

    }

    public function storeTokens(array $tokens): void
    {
        $keys = $this->config['session_keys'];

        if (isset($tokens['access_token'])) {
            $this->session->offsetSet($keys['access_token'], $tokens['access_token']);
        }

        if (isset($tokens['refresh_token'])) {
            $this->session->offsetSet($keys['refresh_token'], $tokens['refresh_token']);
        }

    }

    public function getJwkEndpointContent(string $jwksUri): array
    {
        $this->httpClient->setUri($jwksUri);
        $this->httpClient->setMethod('GET');

        $response = $this->httpClient->send();

        if (! $response->isSuccess()) {
            throw new \Exception('Failed to fetch JWK set: ' . $response->getBody());
        }

        return Json::decode($response->getBody(), Json::TYPE_ARRAY);
    }

    public function jwkToPem(array $jwk): string
    {
        if (! isset($jwk['n'], $jwk['e'])) {
            throw new \InvalidArgumentException("JWK must contain 'n' and 'e'.");
        }

        $modulus  = $this->base64UrlDecode($jwk['n']);
        $exponent = $this->base64UrlDecode($jwk['e']);

        // Build the DER-encoded ASN.1 structure
        $modulus             = $this->encodeAsn1Integer($modulus);
        $exponent            = $this->encodeAsn1Integer($exponent);
        $sequence            = $this->encodeAsn1Sequence($modulus . $exponent);
        $bitString           = $this->encodeAsn1BitString($sequence);
        $algorithmIdentifier = hex2bin("300D06092A864886F70D0101010500"); // rsaEncryption OID

        $publicKeyInfo = $this->encodeAsn1Sequence($algorithmIdentifier . $bitString);
        $pem           = "-----BEGIN PUBLIC KEY-----\n" .
        chunk_split(base64_encode($publicKeyInfo), 64, "\n") .
            "-----END PUBLIC KEY-----\n";

        return $pem;

    }

    private function base64UrlDecode(string $data): string
    {

        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padLength = 4 - $remainder;
            $data .= str_repeat('=', $padLength);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function encodeAsn1Integer(string $value): string
    {
        if (ord($value[0]) > 0x7f) {
            $value = "\x00" . $value;
        }
        return "\x02" . $this->encodeLength(strlen($value)) . $value;
    }

    public function encodeAsn1BitString(string $value): string
    {
        return "\x03" . $this->encodeLength(strlen($value) + 1) . "\x00" . $value;
    }

    public function encodeAsn1Sequence(string $value): string
    {
        return "\x30" . $this->encodeLength(strlen($value)) . $value;
    }

    public function decodeJwtPayload(string $jwt): array
    {
        $parts   = $this->splitJwt($jwt);
        $payload = json_decode($this->base64UrlDecode($parts['payload']), true);
        return $payload;
    }
    public function splitJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \Exception("Invalid JWT structure.");
        }
        return [
            'header'    => $parts[0],
            'payload'   => $parts[1],
            'signature' => $parts[2],
        ];
    }

    public function encodeLength($length)
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $lenBytes = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($lenBytes)) . $lenBytes;
    }

    public function verifyJwtSignature(string $jwt, string $pemPublicKey): bool
    {
        $parts     = $this->splitJwt($jwt);
        $data      = $parts['header'] . '.' . $parts['payload'];
        $signature = $this->base64UrlDecode($parts['signature']);
        $key = openssl_pkey_get_public($pemPublicKey);

        if (! $key) {
            throw new \Exception('Invalid public key');
        }
        $result = openssl_verify($data, $signature, $key, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    public function getSession(): Container
    {
        return $this->session;
    }

}
