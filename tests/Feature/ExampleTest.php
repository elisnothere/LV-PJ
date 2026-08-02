<?php

test('the application redirects home to the storefront catalog', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('catalog.index'));
});
