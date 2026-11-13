<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_creates_the_owner_and_website_once(): void
    {
        $this->artisan('app:install', ['username' => 'Boss@Example.test', '--password' => 'secret-pass-1'])->assertExitCode(0);

        $owner = User::where('username', 'boss@example.test')->firstOrFail();
        $this->assertSame('super_admin', $owner->role);
        $this->assertTrue((bool) $owner->must_change_password);
        $this->assertTrue(Hash::check('secret-pass-1', $owner->password));
        $this->assertTrue(Site::where('owner_key', 'platform')->exists());

        $this->artisan('app:install', ['username' => 'other@example.test'])->assertExitCode(1);
        $this->assertSame(1, User::where('role', 'super_admin')->count());
    }
}
