<?php

use App\Models\User;
use App\Support\FileScanner;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the storage files page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('settings.files.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Files')
            ->has('files')
            ->has('summary', fn (Assert $summary) => $summary
                ->has('total_files')
                ->has('total_bytes')
                ->has('total_size_formatted')
                ->has('images')
                ->has('pdf')
                ->has('documents')
                ->has('other')
            )
            ->has('collections')
            ->has('filters')
        );
});

test('non admin cannot view the storage files page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.files.index'))
        ->assertRedirect(route('pemilih'));
});

test('guest cannot view the storage files page', function () {
    $this->get(route('settings.files.index'))
        ->assertRedirect(route('home'));
});

test('files from every uploads subfolder are listed', function () {
    $admin = User::factory()->admin()->create();
    $uploads = tempUploadsRoot();

    File::ensureDirectoryExists($uploads.'/candidates');
    File::put($uploads.'/candidates/foto.jpg', 'image');

    File::ensureDirectoryExists($uploads.'/imports');
    File::put($uploads.'/imports/data-siswa.xlsx', 'excel');

    app()->instance(FileScanner::class, new FileScanner($uploads));

    $this->actingAs($admin)
        ->get(route('settings.files.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Files')
            ->has('files', 2)
            ->where('summary.total_files', 2)
            ->where('summary.images', 1)
            ->where('summary.documents', 1)
            ->where('collections.0.key', 'candidates')
            ->where('collections.1.key', 'imports')
        );

    File::deleteDirectory($uploads);
});

test('files can be filtered by type, search, and subfolder', function () {
    $admin = User::factory()->admin()->create();
    $uploads = tempUploadsRoot();

    File::ensureDirectoryExists($uploads.'/candidates');
    File::put($uploads.'/candidates/foto.jpg', 'image');

    File::ensureDirectoryExists($uploads.'/imports');
    File::put($uploads.'/imports/laporan.pdf', 'pdf');

    app()->instance(FileScanner::class, new FileScanner($uploads));

    $this->actingAs($admin)
        ->get(route('settings.files.index', ['type' => 'pdf']))
        ->assertInertia(fn (Assert $page) => $page->has('files', 1));

    $this->actingAs($admin)
        ->get(route('settings.files.index', ['search' => 'foto']))
        ->assertInertia(fn (Assert $page) => $page->has('files', 1));

    $this->actingAs($admin)
        ->get(route('settings.files.index', ['collection' => 'imports']))
        ->assertInertia(fn (Assert $page) => $page->has('files', 1));

    File::deleteDirectory($uploads);
});

test('admin can delete a stored file', function () {
    $admin = User::factory()->admin()->create();
    $uploads = tempUploadsRoot();

    File::ensureDirectoryExists($uploads.'/candidates');
    File::put($uploads.'/candidates/hapus.jpg', 'image');

    app()->instance(FileScanner::class, new FileScanner($uploads));

    $this->actingAs($admin)
        ->from(route('settings.files.index'))
        ->delete(route('settings.files.destroy'), [
            'collection' => 'candidates',
            'path' => $uploads.'/candidates/hapus.jpg',
        ])
        ->assertRedirect(route('settings.files.index'))
        ->assertSessionHasNoErrors();

    expect(File::exists($uploads.'/candidates/hapus.jpg'))->toBeFalse();

    File::deleteDirectory($uploads);
});

test('deleting a file outside public/uploads is refused', function () {
    $admin = User::factory()->admin()->create();
    $uploads = tempUploadsRoot();

    File::put($uploads.'/catatan.txt', 'catatan');

    $outside = sys_get_temp_dir().'/kpu-secret-'.bin2hex(random_bytes(6)).'.txt';
    File::put($outside, 'secret');

    app()->instance(FileScanner::class, new FileScanner($uploads));

    $this->actingAs($admin)
        ->from(route('settings.files.index'))
        ->delete(route('settings.files.destroy'), [
            'collection' => 'root',
            'path' => $outside,
        ])
        ->assertSessionHasNoErrors();

    expect(File::exists($outside))->toBeTrue();

    File::delete($outside);
    File::deleteDirectory($uploads);
});

function tempUploadsRoot(): string
{
    $root = sys_get_temp_dir().'/kpu-uploads-'.bin2hex(random_bytes(6));

    File::ensureDirectoryExists($root);

    return $root;
}
