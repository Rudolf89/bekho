<?php

test('la raíz redirige al inicio de sesión cuando no hay sesión', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
