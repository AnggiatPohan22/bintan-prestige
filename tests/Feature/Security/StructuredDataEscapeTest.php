<?php

namespace Tests\Feature\Security;

use App\Support\StructuredDataBuilder;
use Tests\TestCase;

/**
 * The site-wide JSON-LD (@graph) must hex-escape angle brackets so a user-editable
 * value (business name, product name, page title) containing </script> cannot break
 * out of the <script type="application/ld+json"> block. Mirrors the C1 fix for the
 * single-entry JSON-LD.
 */
class StructuredDataEscapeTest extends TestCase
{
    public function test_jsonld_escapes_script_breakout_in_business_name(): void
    {
        $context = [
            'seoDefaultSettings' => ['site_name' => '', 'canonical_base_url' => ''],
            'businessIdentity'   => ['brand_name' => 'Evil </script><script>alert(1)</script>'],
        ];

        $json = StructuredDataBuilder::jsonLd($context);

        $this->assertNotNull($json);
        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $json);
        // The value is still present, with angle brackets hex-escaped.
        $this->assertStringContainsString('Evil', $json);
        $this->assertStringNotContainsString('<script>', $json);
    }
}
