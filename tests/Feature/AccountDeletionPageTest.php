<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccountDeletionPageTest extends TestCase
{
    public function test_account_deletion_page_is_public(): void
    {
        $this->get('/account-deletion')
            ->assertOk()
            ->assertSee('Delete your Ekaadh account', false)
            ->assertSee('hello@ekaadh.com', false)
            ->assertSee('Profile', false);
    }

    public function test_privacy_page_is_public(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('Privacy policy', false)
            ->assertSee('WaafiPay', false)
            ->assertSee('hello@ekaadh.com', false);
    }

    public function test_terms_page_is_public(): void
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('Terms of service', false)
            ->assertSee('hello@ekaadh.com', false);
    }
}
