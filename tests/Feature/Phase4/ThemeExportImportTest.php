<?php

namespace Tests\Feature\Phase4;

use App\Models\Theme;
use App\Models\User;
use App\Services\ZipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * STEP 1.5 — IMP-05: Theme Export & Import (ZIP)
 *
 * Tests cover:
 *   E1 — Export: admin receives a ZIP download response
 *   E2 — Export: ZIP contains the theme's files
 *   E3 — Export: ZIP contains theme_config.json with customization + widget data
 *   E4 — Export: unauthenticated user cannot export
 *
 *   I1 — Import: valid ZIP installs theme and redirects with success
 *   I2 — Import: theme is registered in DB after import
 *   I3 — Import: non-ZIP file is rejected with validation error
 *   I4 — Import: ZIP with no theme.json is rejected
 *   I5 — Import: ZIP with theme.json missing required field is rejected
 *   I6 — Import: ZIP containing a PHP file is rejected
 *   I7 — Import: ZIP with path traversal entry is rejected
 *   I8 — Import: unauthenticated user cannot import
 *
 *   Z1 — ZipService: createFromDirectory zips all files with correct relative paths
 *   Z2 — ZipService: createFromDirectory includes extra files (theme_config.json)
 *   Z3 — ZipService: extractTheme handles manifest at root level
 *   Z4 — ZipService: extractTheme handles manifest one folder deep (common ZIP tool convention)
 */
class ThemeExportImportTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // E1 — Export: admin receives a ZIP download
    // -------------------------------------------------------------------------

    public function test_admin_can_export_a_theme_as_a_zip_download(): void
    {
        $theme = $this->luxuryTheme();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.themes.export', $theme));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');
        $this->assertStringContainsString(
            $theme->slug . '-',
            $response->headers->get('Content-Disposition') ?? ''
        );
    }

    // -------------------------------------------------------------------------
    // E2 — Export ZIP contains the theme's files
    // -------------------------------------------------------------------------

    public function test_exported_zip_contains_theme_json(): void
    {
        $theme = $this->luxuryTheme();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.themes.export', $theme));

        $zipContent = $response->streamedContent();
        $tempPath   = sys_get_temp_dir() . '/bp-test-export-' . uniqid() . '.zip';

        file_put_contents($tempPath, $zipContent);

        $zip = new ZipArchive();
        $zip->open($tempPath);

        $this->assertNotFalse(
            $zip->locateName('theme.json'),
            'Exported ZIP must contain theme.json'
        );

        $zip->close();
        @unlink($tempPath);
    }

    // -------------------------------------------------------------------------
    // E3 — Export ZIP contains theme_config.json
    // -------------------------------------------------------------------------

    public function test_exported_zip_contains_theme_config_json_with_customization(): void
    {
        $theme = $this->luxuryTheme();
        $theme->update(['customization' => ['--frontend-gold' => '#aabbcc']]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.themes.export', $theme));

        $zipContent = $response->streamedContent();
        $tempPath   = sys_get_temp_dir() . '/bp-test-config-' . uniqid() . '.zip';

        file_put_contents($tempPath, $zipContent);

        $zip = new ZipArchive();
        $zip->open($tempPath);

        $configJson = $zip->getFromName('theme_config.json');
        $zip->close();
        @unlink($tempPath);

        $this->assertNotFalse($configJson, 'Exported ZIP must contain theme_config.json');

        $config = json_decode($configJson, true);
        $this->assertArrayHasKey('customization', $config);
        $this->assertSame('#aabbcc', $config['customization']['--frontend-gold']);
        $this->assertArrayHasKey('exported_at', $config);
        $this->assertArrayHasKey('widgets', $config);
    }

    // -------------------------------------------------------------------------
    // E4 — Export: unauthenticated
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_export_a_theme(): void
    {
        $theme = $this->luxuryTheme();

        $this->get(route('admin.themes.export', $theme))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // I1 — Import: valid ZIP installs theme
    // -------------------------------------------------------------------------

    public function test_admin_can_import_a_valid_theme_zip(): void
    {
        $admin   = User::factory()->admin()->create();
        $zipPath = $this->makeThemeZip('my-imported-theme', [
            'name'    => 'My Imported Theme',
            'slug'    => 'my-imported-theme',
            'version' => '2.0.0',
            'author'  => 'Test Author',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.themes.import'),
            ['theme_zip' => new UploadedFile($zipPath, 'my-imported-theme.zip', 'application/zip', null, true)]
        );

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');

        @unlink($zipPath);
    }

    // -------------------------------------------------------------------------
    // I2 — Import: theme is registered in DB
    // -------------------------------------------------------------------------

    public function test_imported_theme_is_registered_in_the_themes_table(): void
    {
        $admin   = User::factory()->admin()->create();
        $zipPath = $this->makeThemeZip('db-test-theme', [
            'name'    => 'DB Test Theme',
            'slug'    => 'db-test-theme',
            'version' => '1.5.0',
        ]);

        $this->actingAs($admin)->post(
            route('admin.themes.import'),
            ['theme_zip' => new UploadedFile($zipPath, 'db-test-theme.zip', 'application/zip', null, true)]
        );

        $this->assertDatabaseHas('themes', [
            'slug'    => 'db-test-theme',
            'name'    => 'DB Test Theme',
            'version' => '1.5.0',
        ]);

        // Cleanup extracted theme directory.
        $this->cleanTempDir(base_path('themes/db-test-theme'));
        @unlink($zipPath);
    }

    // -------------------------------------------------------------------------
    // I3 — Import: non-ZIP rejected
    // -------------------------------------------------------------------------

    public function test_import_rejects_non_zip_file(): void
    {
        $admin = User::factory()->admin()->create();
        $fake  = UploadedFile::fake()->create('theme.txt', 10, 'text/plain');

        $response = $this->actingAs($admin)
            ->from(route('admin.themes.index'))
            ->post(route('admin.themes.import'), ['theme_zip' => $fake]);

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHasErrors('theme_zip');
    }

    // -------------------------------------------------------------------------
    // I4 — Import: ZIP missing theme.json
    // -------------------------------------------------------------------------

    public function test_import_rejects_zip_with_no_theme_json(): void
    {
        $admin   = User::factory()->admin()->create();
        $zipPath = $this->makeEmptyZip(['readme.txt' => 'Hello world']);

        $response = $this->actingAs($admin)
            ->from(route('admin.themes.index'))
            ->post(
                route('admin.themes.import'),
                ['theme_zip' => new UploadedFile($zipPath, 'bad.zip', 'application/zip', null, true)]
            );

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('error');

        @unlink($zipPath);
    }

    // -------------------------------------------------------------------------
    // I5 — Import: theme.json with missing required field
    // -------------------------------------------------------------------------

    public function test_import_rejects_zip_when_theme_json_is_missing_required_field(): void
    {
        $admin   = User::factory()->admin()->create();
        $zipPath = $this->makeThemeZip('incomplete-theme', [
            'name' => 'Incomplete Theme',
            // 'slug' missing — required
            'version' => '1.0.0',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.themes.index'))
            ->post(
                route('admin.themes.import'),
                ['theme_zip' => new UploadedFile($zipPath, 'incomplete.zip', 'application/zip', null, true)]
            );

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('error');

        @unlink($zipPath);
    }

    // -------------------------------------------------------------------------
    // I6 — Import: PHP file blocked
    // -------------------------------------------------------------------------

    public function test_import_rejects_zip_containing_a_php_file(): void
    {
        $admin   = User::factory()->admin()->create();
        $zipPath = $this->makeThemeZipWithExtra('php-threat-theme', [
            'name'    => 'PHP Threat Theme',
            'slug'    => 'php-threat-theme',
            'version' => '1.0.0',
        ], ['shell.php' => '<?php system($_GET["cmd"]); ?>']);

        $response = $this->actingAs($admin)
            ->from(route('admin.themes.index'))
            ->post(
                route('admin.themes.import'),
                ['theme_zip' => new UploadedFile($zipPath, 'threat.zip', 'application/zip', null, true)]
            );

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('error');

        $this->assertStringContainsString('blocked', session('error'));

        @unlink($zipPath);
    }

    // -------------------------------------------------------------------------
    // I7 — Import: path traversal blocked
    // -------------------------------------------------------------------------

    public function test_import_rejects_zip_with_path_traversal_entry(): void
    {
        $admin   = User::factory()->admin()->create();
        $zipPath = $this->makeZipWithTraversal();

        $response = $this->actingAs($admin)
            ->from(route('admin.themes.index'))
            ->post(
                route('admin.themes.import'),
                ['theme_zip' => new UploadedFile($zipPath, 'traversal.zip', 'application/zip', null, true)]
            );

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('error');

        @unlink($zipPath);
    }

    // -------------------------------------------------------------------------
    // I8 — Import: unauthenticated
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_import_a_theme(): void
    {
        $this->post(route('admin.themes.import'), [])
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Z1 — ZipService: createFromDirectory
    // -------------------------------------------------------------------------

    public function test_zip_service_create_from_directory_includes_all_files(): void
    {
        $sourceDir = sys_get_temp_dir() . '/bp-zip-source-' . uniqid();
        mkdir($sourceDir . '/sub', 0755, true);
        file_put_contents($sourceDir . '/theme.json', '{"name":"Test"}');
        file_put_contents($sourceDir . '/sub/style.css', 'body {}');

        $zipPath = app(ZipService::class)->createFromDirectory($sourceDir);

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $this->assertNotFalse($zip->locateName('theme.json'));
        $this->assertNotFalse($zip->locateName('sub/style.css'));
        $zip->close();

        @unlink($zipPath);
        $this->cleanTempDir($sourceDir);
    }

    // -------------------------------------------------------------------------
    // Z2 — ZipService: extra files included
    // -------------------------------------------------------------------------

    public function test_zip_service_create_includes_extra_files(): void
    {
        $sourceDir = sys_get_temp_dir() . '/bp-zip-extra-' . uniqid();
        mkdir($sourceDir);
        file_put_contents($sourceDir . '/theme.json', '{"name":"Test"}');

        $zipPath = app(ZipService::class)->createFromDirectory($sourceDir, [
            'theme_config.json' => '{"exported_at":"2026-01-01"}',
        ]);

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $config = $zip->getFromName('theme_config.json');
        $zip->close();

        $this->assertNotFalse($config);
        $this->assertStringContainsString('exported_at', $config);

        @unlink($zipPath);
        $this->cleanTempDir($sourceDir);
    }

    // -------------------------------------------------------------------------
    // Z3 — ZipService: extractTheme root-level manifest
    // -------------------------------------------------------------------------

    public function test_zip_service_extract_theme_handles_root_level_manifest(): void
    {
        $zipPath   = $this->makeThemeZip('root-level-theme', [
            'name' => 'Root Level Theme', 'slug' => 'root-level-theme', 'version' => '1.0.0',
        ]);
        $targetBase = sys_get_temp_dir() . '/bp-extract-root-' . uniqid();
        mkdir($targetBase);

        $manifest = app(ZipService::class)->extractTheme($zipPath, $targetBase);

        $this->assertSame('root-level-theme', $manifest['slug']);
        $this->assertFileExists($targetBase . '/root-level-theme/theme.json');

        @unlink($zipPath);
        $this->cleanTempDir($targetBase);
    }

    // -------------------------------------------------------------------------
    // Z4 — ZipService: extractTheme one-folder-deep manifest
    // -------------------------------------------------------------------------

    public function test_zip_service_extract_theme_handles_manifest_one_folder_deep(): void
    {
        $zipPath = $this->makeThemeZipInSubfolder('deep-theme', [
            'name' => 'Deep Theme', 'slug' => 'deep-theme', 'version' => '1.0.0',
        ]);
        $targetBase = sys_get_temp_dir() . '/bp-extract-deep-' . uniqid();
        mkdir($targetBase);

        $manifest = app(ZipService::class)->extractTheme($zipPath, $targetBase);

        $this->assertSame('deep-theme', $manifest['slug']);
        $this->assertFileExists($targetBase . '/deep-theme/theme.json');

        @unlink($zipPath);
        $this->cleanTempDir($targetBase);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function luxuryTheme(): Theme
    {
        return Theme::create([
            'name'      => 'Bintan Prestige Luxury',
            'slug'      => 'bintan-prestige-luxury',
            'directory' => 'themes/bintan-prestige-luxury',
            'version'   => '1.0.0',
            'is_active' => true,
        ]);
    }

    /** Build a ZIP with theme.json at the root level. */
    private function makeThemeZip(string $slug, array $manifest): string
    {
        $path = sys_get_temp_dir() . '/bp-import-' . uniqid() . '.zip';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('theme.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('assets/style.css', 'body { margin: 0; }');
        $zip->close();

        return $path;
    }

    /** Build a ZIP with theme.json at one level deep (e.g. my-theme/theme.json). */
    private function makeThemeZipInSubfolder(string $slug, array $manifest): string
    {
        $path = sys_get_temp_dir() . '/bp-import-deep-' . uniqid() . '.zip';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString("{$slug}/theme.json", json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString("{$slug}/assets/style.css", 'body {}');
        $zip->close();

        return $path;
    }

    /** Build a ZIP with extra additional files beyond theme.json. */
    private function makeThemeZipWithExtra(string $slug, array $manifest, array $extras): string
    {
        $path = sys_get_temp_dir() . '/bp-import-extra-' . uniqid() . '.zip';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('theme.json', json_encode($manifest, JSON_PRETTY_PRINT));

        foreach ($extras as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $path;
    }

    /** Build a ZIP with no theme.json, only arbitrary files. */
    private function makeEmptyZip(array $files): string
    {
        $path = sys_get_temp_dir() . '/bp-import-empty-' . uniqid() . '.zip';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $path;
    }

    /** Build a ZIP that contains a path traversal entry. */
    private function makeZipWithTraversal(): string
    {
        $path = sys_get_temp_dir() . '/bp-import-traversal-' . uniqid() . '.zip';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('theme.json', json_encode(['name' => 'X', 'slug' => 'x', 'version' => '1.0.0']));
        $zip->addFromString('../../../etc/passwd', 'root:x:0:0');
        $zip->close();

        return $path;
    }

    private function cleanTempDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
