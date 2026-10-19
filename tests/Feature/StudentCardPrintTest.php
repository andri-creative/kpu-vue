<?php

use App\Models\Profile;
use App\Models\User;

test('guests are redirected from the student card print route', function () {
    $this->post(route('users.cards.print'), ['student_ids' => [1]])
        ->assertRedirect(route('home'));
});

test('pemilih users cannot print student cards', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('users.cards.print'), ['student_ids' => [$user->id]])
        ->assertRedirect(route('pemilih'));
});

test('admin can print cards with only student ids and receives a pdf', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    Profile::create([
        'user_id' => $student->id,
        'nama_lengkap' => 'Andi Pratama',
        'kelas' => 'X IPA 1',
    ]);

    $response = $this->actingAs($admin)->post(route('users.cards.print'), [
        'student_ids' => [$student->id],
    ]);

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

test('the printed card exposes the plaintext kode from the database', function () {
    $student = User::factory()->withKode('654321')->create();

    Profile::create([
        'user_id' => $student->id,
        'nama_lengkap' => 'Andi Pratama',
        'kelas' => 'X IPA 1',
    ]);

    $html = view('pdf.student-cards', [
        'students' => collect([$student]),
        'schoolName' => 'SMA Negeri 1',
    ])->render();

    expect($html)->toContain('654321');
});

test('the printed card shows a placeholder when a student has no kode', function () {
    $student = User::factory()->create(['kode' => null]);

    Profile::create([
        'user_id' => $student->id,
        'nama_lengkap' => 'Budi Santoso',
        'kelas' => 'X IPA 2',
    ]);

    $html = view('pdf.student-cards', [
        'students' => collect([$student]),
        'schoolName' => 'SMA Negeri 1',
    ])->render();

    expect($html)->toContain('–');
});
