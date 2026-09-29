<?php

it('redirects guests from the top page to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});
