<?php

test('the root URL redirects guests to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});
