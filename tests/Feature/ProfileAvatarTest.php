<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_upload_avatar_and_it_shows_in_header(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('foto.jpg', 300, 300)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Foto profil diperbarui');

        $admin->refresh();
        Storage::disk('public')->assertExists($admin->avatar);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($admin->avatarUrl(), false);
    }

    public function test_replacing_avatar_deletes_the_old_file(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.png')]);
        $old = $admin->refresh()->avatar;

        $this->actingAs($admin)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('b.png')]);

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($admin->refresh()->avatar);
    }

    public function test_avatar_can_be_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.png')]);
        $path = $admin->refresh()->avatar;

        $this->actingAs($admin)->delete(route('profile.avatar.destroy'))->assertSessionHas('status', 'Foto profil dihapus');

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($admin->refresh()->avatar);
    }

    public function test_only_images_up_to_2mb_are_accepted(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('gambar.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('besar.jpg')->size(3000),
        ] as $file) {
            $this->actingAs($admin)
                ->post(route('profile.avatar.update'), ['avatar' => $file])
                ->assertSessionHasErrors('avatar');
        }

        $this->assertNull($admin->refresh()->avatar);
    }

    public function test_guest_cannot_upload_avatar(): void
    {
        $this->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('a.png')])
            ->assertRedirect(route('login'));
    }
}
