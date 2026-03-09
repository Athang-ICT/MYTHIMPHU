<?php
return [

    'openid_config'=> [],
    'sso' => [
        'client_id' => 'demo-php1',
        'redirect_uri' => 'http://localhost/mythimphu/public/auth/callback',
        'openid_configuration_uri' =>'https://api.sso.one.athang.com/.well-known/openid-configuration',
        'logoutUri' => 'https://sso.one.athang.com',
        'validIssuers' => 'https://sso.one.athang.com,https://api.sso.one.athang.com',
        'logoutEndpoint' => 'https://api.sso.one.athang.com/protocol/openid-connect/logout',
        'postLogoutRedirectUri' => 'https://admin.one.athang.com',
    ],
    'session_keys'           => [
        'state'         => 'oidc_state',
        'code_verifier' => 'oidc_code_verifier',
        'access_token'  => 'oidc_access_token',
        'id_token'      => 'oidc_id_token',
        'refresh_token' => 'oidc_refresh_token',
    ],

];