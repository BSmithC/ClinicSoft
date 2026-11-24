<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_public_registration_is_disabled(): void
    {
        $this->assertFalse(Route::has('register'));

        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_welcome_page_does_not_advertise_registration(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('canRegister', false)
        );
    }

    public function test_frontend_does_not_reference_disabled_register_route(): void
    {
        $views = [
            resource_path('js/Pages/Welcome.vue'),
            resource_path('js/Pages/Auth/Login.vue'),
        ];

        foreach ($views as $view) {
            $this->assertStringNotContainsString("route('register')", (string) file_get_contents($view), $view);
        }

        $this->assertFileDoesNotExist(resource_path('js/Pages/Auth/Register.vue'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Auth/RegisteredUserController.php'));
    }
}
