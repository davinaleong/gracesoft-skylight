<?php

it('is reachable without authentication and embeds the GraceSoft Capture form', function () {
    $this->get(route('feedback'))
        ->assertOk()
        ->assertSee('https://capture.gracesoft.dev/form/frm_9003b576689d16fe08b8b2affe2cc301?surface=none', false);
});
