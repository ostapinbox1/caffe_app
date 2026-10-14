<?php

test('home redirects to the dashboard', function () {
    $this->get(route('home'))->assertRedirect('/dashboard');
});
