<?php

test('home page loads and shows the app name', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Our Little List');
});
