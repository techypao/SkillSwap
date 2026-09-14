<?php

$commaSeparated = fn (?string $value): array => array_values(array_filter(array_map('trim', explode(',', (string) $value))));

$stunUrls = $commaSeparated(env('WEBRTC_STUN_URLS', 'stun:stun.l.google.com:19302,stun:stun1.l.google.com:19302'));
$turnUrls = $commaSeparated(env('WEBRTC_TURN_URLS'));

return [

    /*
    |--------------------------------------------------------------------------
    | ICE Servers
    |--------------------------------------------------------------------------
    |
    | Handed to RTCPeerConnection for 1-to-1 swap calls. STUN lets each browser
    | discover its public address; TURN relays media when no direct path
    | exists (symmetric NAT, strict firewalls). STUN alone does not guarantee
    | connectivity, so production deployments should configure TURN.
    |
    */

    'ice_servers' => array_values(array_filter([
        $stunUrls === [] ? null : ['urls' => $stunUrls],
        $turnUrls === [] ? null : [
            'urls' => $turnUrls,
            'username' => env('WEBRTC_TURN_USERNAME'),
            'credential' => env('WEBRTC_TURN_CREDENTIAL'),
        ],
    ])),

    /*
    |--------------------------------------------------------------------------
    | Presence & Signal Retention
    |--------------------------------------------------------------------------
    |
    | A participant counts as "in the call" while their browser keeps polling
    | within the presence window. Signaling rows are only needed while a call
    | is being set up, so older rows are discarded.
    |
    */

    'presence_ttl_seconds' => (int) env('WEBRTC_PRESENCE_TTL_SECONDS', 20),

    'signal_retention_minutes' => (int) env('WEBRTC_SIGNAL_RETENTION_MINUTES', 60),

];
