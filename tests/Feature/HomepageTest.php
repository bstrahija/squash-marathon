<?php

test('homepage loads', function () {
    $this->withoutVite();

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Squash Marathon Tracker');
    $response->assertSee('Leaderboard');
    $response->assertSee('Participants');
});
