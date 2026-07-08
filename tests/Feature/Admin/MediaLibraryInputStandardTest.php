<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Architecture guard for the Canonical Image Input Standard
 * (ai/skills/media-library-skill.md ⭐). Every admin image/file input must go
 * through the Media Library via <x-admin.media-image-field> /
 * <x-admin.media-gallery-field>, which submit path strings and carry no `name`
 * on their internal upload <input type="file">. A *named* raw file input in an
 * admin view therefore means someone bypassed the standard.
 *
 * Only sanctioned exception: the favicon (.ico/.svg are outside the library's
 * allowed types).
 */
class MediaLibraryInputStandardTest extends TestCase
{
    /**
     * Named file inputs allowed to remain raw:
     *  - favicon  → .ico/.svg are outside the library's allowed types
     *  - theme_zip → theme import is a ZIP archive, not a media asset
     */
    private const ALLOWED_NAMES = ['favicon', 'theme_zip'];

    public function test_admin_views_have_no_raw_named_file_inputs_except_favicon(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views/backend')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = $file->getContents();

            // Match each <input ...> tag (may span multiple lines).
            preg_match_all('/<input\b[^>]*>/s', $contents, $tags);

            foreach ($tags[0] as $tag) {
                if (! preg_match('/type\s*=\s*["\']file["\']/', $tag)) {
                    continue;
                }

                // The picker components' upload inputs are intentionally nameless.
                if (! preg_match('/\bname\s*=\s*["\']([^"\']+)["\']/', $tag, $match)) {
                    continue;
                }

                $name = preg_replace('/\[.*$/', '', $match[1]); // strip array suffix e.g. gallery[]

                if (! in_array($name, self::ALLOWED_NAMES, true)) {
                    $offenders[] = $file->getRelativePathname().' → name="'.$match[1].'"';
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Raw named file inputs found in admin views. Use <x-admin.media-image-field> / "
            ."<x-admin.media-gallery-field> instead (Media Library standard). Offenders:\n- "
            .implode("\n- ", $offenders),
        );
    }
}
