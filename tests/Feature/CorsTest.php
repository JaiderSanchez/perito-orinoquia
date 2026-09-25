<?php

test('the local frontend can call the api through cors', function () {
    $response = $this->withHeaders([
        'Origin' => 'http://127.0.0.1:5173',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type',
    ])->options('/api/login');

    $response
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173')
        ->assertHeader('Access-Control-Allow-Methods');
});

test('vercel production and preview deployments can call the api through cors', function (string $origin) {
    $response = $this->withHeaders([
        'Origin' => $origin,
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type,authorization',
    ])->options('/api/login');

    $response
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', $origin)
        ->assertHeader('Access-Control-Allow-Methods');
})->with([
    'production' => 'https://perito-orinoquia.vercel.app',
    'preview' => 'https://perito-orinoquia-a7vpxu50f-diaz20.vercel.app',
]);
