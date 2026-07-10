<?php

declare(strict_types=1);

$replacements = [
    "\u{00E2}\u{20AC}\u{201D}" => '-', // em dash mojibake
    "\u{00E2}\u{20AC}\u{2013}" => '-', // en dash mojibake
    "\u{00E2}\u{20AC}\u{201C}" => '-',
    "\u{00E2}\u{2020}\u{2019}" => '->', // arrow mojibake (â†')
    "\u{00E2}\u{2020}\u{2019}" => '->',
    'Â·' => ' | ',
    "\u{00E2}\u{201A}\u{00AC}" => 'EUR ', // â‚¬
    "\u{00C3}\u{0097}" => 'x', // Ã—
];

$files = [
    __DIR__.'/../docs/planning/checklist.md',
    __DIR__.'/../.env.example',
];

foreach ($files as $path) {
    if (! is_file($path)) {
        fwrite(STDERR, "Missing: {$path}\n");
        continue;
    }

    $content = file_get_contents($path);
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    $updated = str_replace(array_keys($replacements), array_values($replacements), $content);
    file_put_contents($path, $updated);
    echo "Fixed: {$path}\n";
}
