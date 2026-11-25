<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * TranslationKeysTest
 *
 * A one-word __() key named like a lang/{locale}/*.php file ("Pagination", "Auth") returns that whole file as an
 * array on case-insensitive filesystems, which breaks the page. Such keys need a lang/{locale}.json entry.
 */
class TranslationKeysTest extends TestCase
{
    public function test_keys_named_like_lang_files_resolve_to_text(): void
    {
        $groups = collect(File::files(lang_path('en')))->map(fn ($file) => ucfirst($file->getFilenameWithoutExtension()));

        foreach ($groups as $key) {
            $this->assertIsString(__($key), "__('{$key}') must be text: add it to lang/en.json.");
        }
    }
}
