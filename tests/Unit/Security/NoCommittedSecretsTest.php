<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

final class NoCommittedSecretsTest extends TestCase
{
    /** @var list<string> */
    private const SCAN_PATHS = [
        'app',
        'config',
        'database',
        'resources',
        'routes',
        '.github',
    ];

    /** @var list<string> */
    private const EXCLUDED_FILES = [
        '.env.example',
    ];

    /** @var list<string> */
    private const PATTERNS = [
        '/sk-or-v1-[a-zA-Z0-9]{16,}/',
        '/shpat_[a-f0-9]{20,}/',
        '/shpss_[a-f0-9]{20,}/',
        '/re_[a-zA-Z0-9]{20,}/',
        '/AKIA[0-9A-Z]{16}/',
    ];

    public function test_source_files_do_not_contain_hardcoded_secrets(): void
    {
        $files = $this->sourceFiles();

        $this->assertNotEmpty($files);

        $violations = [];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            foreach (self::PATTERNS as $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
                    break;
                }
            }
        }

        $this->assertSame([], $violations, 'Potential secrets found in source files.');
    }

    public function test_env_file_is_gitignored(): void
    {
        $gitignore = file_get_contents(base_path('.gitignore'));

        $this->assertIsString($gitignore);
        $this->assertStringContainsString('.env', $gitignore);
        $this->assertStringContainsString('!.env.example', $gitignore);
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        $files = [];

        foreach (self::SCAN_PATHS as $scanPath) {
            $absolute = base_path($scanPath);

            if (! is_dir($absolute)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute));
            $phpFiles = new RegexIterator($iterator, '/^.+\.(php|tsx?|ya?ml|json)$/i', RegexIterator::GET_MATCH);

            foreach ($phpFiles as $match) {
                $path = $match[0];

                if (in_array(basename($path), self::EXCLUDED_FILES, true)) {
                    continue;
                }

                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }
}
