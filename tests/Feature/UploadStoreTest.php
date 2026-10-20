<?php

use App\Support\UploadStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $root = sys_get_temp_dir().'/kpu-uploads-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($root);

    app()->instance('test.uploads_root', $root);
});

afterEach(function () {
    File::deleteDirectory(app('test.uploads_root'));
});

test('jpeg image is converted to a smaller webp file', function () {
    if (! function_exists('imagewebp')) {
        $this->markTestSkipped('GD WebP tidak tersedia.');
    }

    $uploads = new UploadStore(app('test.uploads_root'));

    $filename = $uploads->store(UploadedFile::fake()->image('foto.jpg', 800, 600), 'candidates');

    $path = app('test.uploads_root').'/candidates/'.$filename;

    expect($filename)->toMatch('/^\d{8}-\d{6}(-\d+)?\.webp$/')
        ->and(File::exists($path))->toBeTrue()
        ->and(mime_content_type($path))->toBe('image/webp')
        ->and(filesize($path))->toBeLessThanOrEqual(300 * 1024);
});

test('webp conversion resizes images larger than 1280 pixels', function () {
    if (! function_exists('imagewebp')) {
        $this->markTestSkipped('GD WebP tidak tersedia.');
    }

    $uploads = new UploadStore(app('test.uploads_root'));

    $filename = $uploads->store(UploadedFile::fake()->image('besar.jpg', 2000, 1000), 'candidates');

    [$width, $height] = getimagesize(app('test.uploads_root').'/candidates/'.$filename);

    expect($width)->toBeLessThanOrEqual(1280)
        ->and($height)->toBeLessThanOrEqual(1280);
});

test('non image files keep their original extension', function () {
    $uploads = new UploadStore(app('test.uploads_root'));

    $filename = $uploads->store(
        UploadedFile::fake()->create('template.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        'imports',
    );

    expect($filename)->toMatch('/^\d{8}-\d{6}(-\d+)?\.xlsx$/')
        ->and(File::exists(app('test.uploads_root').'/imports/'.$filename))->toBeTrue();
});
