<?php

namespace Tests\Feature;

use App\Models\DesignResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_browse_published_resources_but_not_hidden_ones(): void
    {
        DesignResource::factory()->create(['title_en' => 'Paper Grain Mockups']);
        DesignResource::factory()->draft()->create(['title_en' => 'Secret Kit']);

        $this->get(route('resources.index'))
            ->assertOk()
            ->assertSee('Design Resources Library')
            ->assertSee('Paper Grain Mockups')
            ->assertDontSee('Secret Kit');
    }

    public function test_the_library_shows_a_friendly_note_when_empty(): void
    {
        $this->get(route('resources.index'))->assertOk()->assertSee('The library is being stocked.');
    }

    public function test_downloading_needs_an_account(): void
    {
        $resource = DesignResource::factory()->create();

        $this->get(route('resources.download', $resource))->assertRedirect(route('login'));
        $this->assertSame(0, $resource->fresh()->downloads_count);
    }

    public function test_an_uploaded_file_is_handed_out_and_counted(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('design-resources/kit.zip', 'zip-bytes');
        $resource = DesignResource::factory()->create([
            'file_path' => 'design-resources/kit.zip',
            'file_name' => 'Grain Kit.zip',
            'file_size' => 9,
            'external_url' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('resources.download', $resource))
            ->assertOk()
            ->assertDownload('Grain Kit.zip');

        $this->assertSame(1, $resource->fresh()->downloads_count);
    }

    public function test_a_link_resource_redirects_to_its_link_and_is_counted(): void
    {
        $resource = DesignResource::factory()->create(['external_url' => 'https://drive.example.com/kit']);

        $this->actingAs(User::factory()->create())
            ->get(route('resources.download', $resource))
            ->assertRedirect('https://drive.example.com/kit');

        $this->assertSame(1, $resource->fresh()->downloads_count);
    }

    public function test_a_hidden_resource_cannot_be_downloaded(): void
    {
        $resource = DesignResource::factory()->draft()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('resources.download', $resource))
            ->assertNotFound();
    }

    public function test_bookmarking_toggles_and_reports_the_count(): void
    {
        $user = User::factory()->create();
        $resource = DesignResource::factory()->create();

        $this->actingAs($user)->postJson(route('resources.bookmark', $resource))
            ->assertOk()->assertJson(['saved' => true, 'count' => 1]);
        $this->assertTrue($user->savedResources()->whereKey($resource->id)->exists());

        $this->actingAs($user)->postJson(route('resources.bookmark', $resource))
            ->assertOk()->assertJson(['saved' => false, 'count' => 0]);
    }

    public function test_guests_cannot_bookmark(): void
    {
        $resource = DesignResource::factory()->create();

        $this->post(route('resources.bookmark', $resource))->assertRedirect(route('login'));
    }

    public function test_the_admin_buttons_show_to_admins_only(): void
    {
        DesignResource::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('resources.index'))
            ->assertOk()->assertDontSee('Manage &amp; Upload Resources', false);

        $this->actingAs(User::factory()->admin()->create())->get(route('resources.index'))
            ->assertOk()->assertSee('Manage &amp; Upload Resources', false)
            ->assertSee(route('admin.resources.create'), false);
    }

    public function test_an_admin_uploads_a_resource_with_a_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.resources.store'), [
            'title_en' => 'Kbach Vector Brushes',
            'title_km' => 'ក្បាច់ Vector',
            'type' => 'Brush Library (.AI)',
            'software' => 'Illustrator',
            'category' => 'Illustrator',
            'tags' => 'kbach, #vector, kbach , pattern',
            'file' => UploadedFile::fake()->create('brushes.ai', 120),
            'thumbnail' => UploadedFile::fake()->image('cover.jpg', 800, 500),
            'is_published' => '1',
            'is_featured' => '1',
        ])->assertRedirect(route('admin.resources'));

        $resource = DesignResource::query()->sole();
        $this->assertSame('Kbach Vector Brushes', $resource->title_en);
        $this->assertSame(['kbach', 'vector', 'pattern'], $resource->tags);
        $this->assertSame('brushes.ai', $resource->file_name);
        $this->assertTrue($resource->is_featured);
        Storage::disk('local')->assertExists($resource->file_path);
        $this->assertStringStartsWith('/storage/resource-pictures/', $resource->thumbnail_url);
    }

    public function test_a_resource_needs_a_file_or_a_link(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.resources.store'), [
            'title_en' => 'Nothing to download',
            'type' => 'Template',
            'software' => 'Figma',
            'category' => 'Figma',
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, DesignResource::query()->count());
    }

    public function test_deleting_a_resource_removes_its_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('design-resources/old.zip', 'x');
        $admin = User::factory()->admin()->create();
        $resource = DesignResource::factory()->create(['file_path' => 'design-resources/old.zip', 'file_name' => 'old.zip']);

        $this->actingAs($admin)->delete(route('admin.resources.destroy', $resource))->assertRedirect(route('admin.resources'));

        $this->assertModelMissing($resource);
        Storage::disk('local')->assertMissing('design-resources/old.zip');
    }

    public function test_students_cannot_reach_the_resource_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.resources'))->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $resource = DesignResource::factory()->create(['title_en' => 'Lottie Pack']);

        $this->actingAs($admin)->get(route('admin.resources'))->assertOk()->assertSee('Lottie Pack');
        $this->actingAs($admin)->get(route('admin.resources.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.resources.edit', $resource))->assertOk()->assertSee('Lottie Pack');
    }
}
