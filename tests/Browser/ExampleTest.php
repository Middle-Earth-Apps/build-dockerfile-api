<?php

test('example', function () {
    // Increase timeout for browser operations
    \Pest\Browser\Playwright\Playwright::setTimeout(30_000); // 30 seconds
    
    $page = visit('/');
    
    $page->assertSee('Laravel');
});
