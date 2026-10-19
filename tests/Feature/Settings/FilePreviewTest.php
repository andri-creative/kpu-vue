<?php

use App\Http\Controllers\Settings\FilePreviewController;
use App\Models\User;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

beforeEach(function () {
    $root = sys_get_temp_dir().'/kpu-preview-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($root);

    app()->instance('test.preview_root', $root);
    app()->instance(FilePreviewController::class, new FilePreviewController($root));
});

afterEach(function () {
    File::deleteDirectory(app('test.preview_root'));
});

test('guest cannot preview a file', function () {
    $this->get(route('settings.files.preview', ['path' => '/tmp/x.png']))
        ->assertRedirect(route('home'));
});

test('non admin cannot preview a file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.files.preview', ['path' => '/tmp/x.png']))
        ->assertRedirect(route('pemilih'));
});

test('images and pdf are streamed inline', function () {
    $admin = User::factory()->admin()->create();
    $root = app('test.preview_root');

    File::put($root.'/foto.png', 'not-a-real-png');

    $this->actingAs($admin)
        ->get(route('settings.files.preview', ['path' => $root.'/foto.png']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Content-Disposition', 'inline; filename="foto.png"');
});

test('spreadsheets are rendered as an html table', function () {
    $admin = User::factory()->admin()->create();
    $root = app('test.preview_root');

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        ['Nama', 'Kelas'],
        ['Andi Pratama', 'X IPA 1'],
    ], null, 'A1');
    (new Xlsx($spreadsheet))->save($root.'/data.xlsx');
    $spreadsheet->disconnectWorksheets();

    $this->actingAs($admin)
        ->get(route('settings.files.preview', ['path' => $root.'/data.xlsx']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Andi Pratama', false)
        ->assertSee('<table>', false);
});

test('docx files are rendered as readable text', function () {
    $admin = User::factory()->admin()->create();
    $root = app('test.preview_root');

    $zip = new ZipArchive;
    $zip->open($root.'/surat.docx', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString(
        'word/document.xml',
        '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
        .'<w:body><w:p><w:r><w:t>Halo Dunia</w:t></w:r></w:p></w:body></w:document>'
    );
    $zip->close();

    $this->actingAs($admin)
        ->get(route('settings.files.preview', ['path' => $root.'/surat.docx']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Halo Dunia', false);
});

test('text files are rendered as text', function () {
    $admin = User::factory()->admin()->create();
    $root = app('test.preview_root');

    File::put($root.'/catatan.txt', "Baris pertama\nBaris kedua");

    $this->actingAs($admin)
        ->get(route('settings.files.preview', ['path' => $root.'/catatan.txt']))
        ->assertOk()
        ->assertSee('Baris pertama', false);
});

test('previewing a file outside public/uploads returns 404', function () {
    $admin = User::factory()->admin()->create();

    $outside = sys_get_temp_dir().'/kpu-outside-'.bin2hex(random_bytes(6)).'.txt';
    File::put($outside, 'secret');

    $this->actingAs($admin)
        ->get(route('settings.files.preview', ['path' => $outside]))
        ->assertNotFound();

    File::delete($outside);
});
