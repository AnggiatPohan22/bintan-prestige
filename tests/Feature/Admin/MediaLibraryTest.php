<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use App\Services\ImageOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_admin_routes_require_an_authenticated_admin(): void
    {
        $this->get(route('admin.media.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.media.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.media.index'))
            ->assertOk();
    }

    public function test_admin_can_upload_a_valid_image_and_register_media_metadata(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $gif = UploadedFile::fake()->createWithContent(
            'pixel.gif',
            base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='),
        );

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'files' => [$gif],
        ])->assertRedirect(route('admin.media.index'));

        $media = Media::query()->firstOrFail();

        $this->assertSame('pixel.gif', $media->original_name);
        $this->assertSame('gif', $media->extension);
        $this->assertSame('image/gif', $media->mime_type);
        $this->assertSame($admin->id, $media->uploaded_by);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_standard_quick_and_batch_uploads_share_the_webp_optimization_pipeline(): void
    {
        Storage::fake('public');
        $optimizer = \Mockery::mock(ImageOptimizationService::class);
        $optimizer->shouldReceive('upload')
            ->times(3)
            ->andReturnUsing(function ($file, string $folder): string {
                $path = $folder.'/'.Str::uuid().'.webp';
                Storage::disk('public')->put($path, 'optimized-webp');

                return $path;
            });
        $this->app->instance(ImageOptimizationService::class, $optimizer);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('standard.png')],
        ])->assertRedirect(route('admin.media.index'));

        $this->actingAs($admin)->postJson(route('admin.media.upload-quick'), [
            'image' => UploadedFile::fake()->image('quick.jpg'),
        ])->assertOk();

        $this->actingAs($admin)->postJson(route('admin.media.upload-batch'), [
            'files' => [UploadedFile::fake()->image('batch.webp')],
        ])->assertOk();

        $this->assertSame(3, Media::query()->where('extension', 'webp')->count());
        $this->assertSame(3, Media::query()->where('mime_type', 'image/webp')->count());
    }

    public function test_media_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->from(route('admin.media.index'))
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->create('payload.txt', 2, 'text/plain')],
            ])->assertRedirect(route('admin.media.index'))
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_all_media_upload_endpoints_reject_spoofed_extensions_and_invalid_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $spoofed = UploadedFile::fake()->createWithContent(
            'payload.php',
            base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='),
        );

        $this->actingAs($admin)
            ->post(route('admin.media.store'), ['files' => [$spoofed]])
            ->assertSessionHasErrors('files.0');

        $this->actingAs($admin)
            ->postJson(route('admin.media.upload-quick'), ['image' => $spoofed])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->actingAs($admin)
            ->postJson(route('admin.media.upload-batch'), ['files' => [UploadedFile::fake()->create('payload.txt', 2, 'text/plain')]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files.0');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_media_upload_rejects_oversized_files_and_batch_overflow(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->image('too-large.jpg')->size(5121)],
            ])
            ->assertSessionHasErrors('files.0');

        $files = collect(range(1, 21))
            ->map(fn (int $index) => UploadedFile::fake()->image("image-{$index}.jpg"))
            ->all();

        $this->actingAs($admin)
            ->postJson(route('admin.media.upload-batch'), ['files' => $files])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_admin_can_update_media_alt_text_and_caption(): void
    {
        $media = $this->media();

        $this->actingAs($this->admin())->put(route('admin.media.update', $media), [
            'alt' => 'Luxury resort coastline',
            'caption' => 'Lagoi Bay at sunset.',
        ])->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'alt' => 'Luxury resort coastline',
            'caption' => 'Lagoi Bay at sunset.',
        ]);
    }

    public function test_admin_can_delete_media_record_and_stored_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test/delete-me.gif', 'gif-content');
        $media = $this->media(['path' => 'media/test/delete-me.gif']);

        $this->actingAs($this->admin())
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing('media/test/delete-me.gif');
    }

    public function test_media_in_use_by_a_page_block_cannot_be_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test/in-use.gif', 'gif-content');
        $media = $this->media(['path' => 'media/test/in-use.gif']);
        $page = $this->page();
        PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'gallery',
            'label' => 'Used gallery',
            'data' => ['images' => [['src' => $media->path, 'alt' => 'Used image']]],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.media.index'))
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHasErrors('media');

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_missing_media_file_record_can_be_safely_deleted(): void
    {
        Storage::fake('public');
        $media = $this->media(['path' => 'media/test/missing.gif']);

        $this->actingAs($this->admin())
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_orphan_cleanup_preserves_registered_and_referenced_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/test/registered.gif', 'registered');
        Storage::disk('public')->put('media/test/referenced.webp', 'referenced');
        Storage::disk('public')->put('media/test/orphan.webp', 'orphan');
        $this->media(['path' => 'media/test/registered.gif']);
        $page = $this->page();
        PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'hero',
            'label' => 'Referenced hero',
            'data' => ['image' => 'media/test/referenced.webp'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.media.orphans.destroy'))
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('success', '1 orphaned file(s) removed.');

        Storage::disk('public')->assertExists('media/test/registered.gif');
        Storage::disk('public')->assertExists('media/test/referenced.webp');
        Storage::disk('public')->assertMissing('media/test/orphan.webp');
    }

    public function test_media_index_supports_search_extension_filter_and_picker_view(): void
    {
        $this->media([
            'original_name' => 'hero-beach.gif',
            'filename' => 'hero-beach.gif',
            'extension' => 'gif',
            'mime_type' => 'image/gif',
        ]);
        $this->media([
            'original_name' => 'resort-card.webp',
            'filename' => 'resort-card.webp',
            'extension' => 'webp',
            'mime_type' => 'image/webp',
            'path' => 'media/test/resort-card.webp',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.media.index', [
            'search' => 'hero',
            'type' => 'gif',
            'picker' => 1,
        ]));

        $response->assertOk()
            ->assertViewIs('backend.media.picker')
            ->assertSee('hero-beach.gif')
            ->assertSee('media/test/sample.gif')
            ->assertDontSee('resort-card.webp');
    }

    public function test_page_block_editor_exposes_media_picker_for_hero_image_gallery_and_background(): void
    {
        $page = $this->page();

        foreach (['hero', 'image', 'gallery'] as $index => $type) {
            PageBlock::create([
                'page_id' => $page->id,
                'block_type' => $type,
                'label' => ucfirst($type),
                'data' => [],
                'sort_order' => $index,
                'is_visible' => true,
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Choose from Media Library')
            ->assertSee('hero-image-', false)
            ->assertSee('image-block-', false)
            ->assertSee('gallery-block-', false)
            ->assertSee('background-', false);
    }

    public function test_uploads_are_stored_under_their_collection_folder(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $gif = UploadedFile::fake()->createWithContent(
            'hero.gif',
            base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='),
        );

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'files' => [$gif],
            'collection' => 'hero',
        ])->assertRedirect(route('admin.media.index'));

        $media = Media::query()->firstOrFail();

        $this->assertSame('hero', $media->collection);
        $this->assertStringStartsWith('media/hero/', $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_unknown_collection_falls_back_to_the_default(): void
    {
        Storage::fake('public');
        $gif = UploadedFile::fake()->createWithContent(
            'evil.gif',
            base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='),
        );

        $this->actingAs($this->admin())->post(route('admin.media.store'), [
            'files' => [$gif],
            'collection' => '../../escape',
        ])->assertRedirect(route('admin.media.index'));

        $media = Media::query()->firstOrFail();

        $this->assertSame(config('media.default_collection'), $media->collection);
        $this->assertStringStartsWith('media/'.config('media.default_collection').'/', $media->path);
    }

    public function test_media_index_filters_by_collection_including_uncategorized_legacy_rows(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->media([
            'original_name' => 'in-hero-collection.webp',
            'path' => 'media/hero/2026/07/a.webp',
            'collection' => 'hero',
        ]);
        $this->media([
            'original_name' => 'legacy-uncategorized.webp',
            'path' => 'media/2026/06/b.webp',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.media.index', ['collection' => 'hero']))
            ->assertOk()
            ->assertSee('in-hero-collection.webp')
            ->assertDontSee('legacy-uncategorized.webp');

        $this->actingAs($admin)
            ->get(route('admin.media.index', ['collection' => 'uncategorized']))
            ->assertOk()
            ->assertSee('legacy-uncategorized.webp')
            ->assertDontSee('in-hero-collection.webp');
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function media(array $overrides = []): Media
    {
        return Media::create(array_merge([
            'filename' => 'sample.gif',
            'original_name' => 'sample.gif',
            'mime_type' => 'image/gif',
            'extension' => 'gif',
            'size' => 10,
            'width' => 1,
            'height' => 1,
            'path' => 'media/test/sample.gif',
            'disk' => 'public',
        ], $overrides));
    }

    private function page(): Page
    {
        return Page::create([
            'title' => 'Media Picker Page',
            'slug' => 'media-picker-page',
            'status' => 'draft',
        ]);
    }
}
