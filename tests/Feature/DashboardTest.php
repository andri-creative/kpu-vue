<?php

use App\Models\Candidate;
use App\Models\Profile;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteLog;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('home'));
});

test('admin can visit the dashboard', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('kandidat is redirected away from dashboard', function () {
    $kandidat = User::factory()->kandidat()->create();
    $this->actingAs($kandidat);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('kandidat'));
});

test('pemilih is redirected away from dashboard', function () {
    $pemilih = User::factory()->create(); // default role Pemilih

    $response = $this->actingAs($pemilih)->get(route('dashboard'));

    $response->assertRedirect(route('pemilih'));
});

test('dashboard statistics props are calculated', function () {
    $admin = User::factory()->admin()->create();
    $pemilih = User::factory()->count(5)->create();

    $kandidatSatu = User::factory()->kandidat()->create();
    $kandidatDua = User::factory()->kandidat()->create();

    $candidateSatu = Candidate::create([
        'user_id' => $kandidatSatu->id,
        'foto' => 'satu.jpg',
        'nama_lengkap' => 'Kandidat Satu',
        'nomor_urut' => 1,
        'nis' => '1001',
        'kelas' => 'XII IPA 1',
        'visi' => 'Visi',
        'misi' => 'Misi',
    ]);
    $candidateDua = Candidate::create([
        'user_id' => $kandidatDua->id,
        'foto' => 'dua.jpg',
        'nama_lengkap' => 'Kandidat Dua',
        'nomor_urut' => 2,
        'nis' => '1002',
        'kelas' => 'XII IPA 1',
        'visi' => 'Visi',
        'misi' => 'Misi',
    ]);

    Vote::create(['user_id' => $admin->id, 'candidate_id' => $candidateSatu->id, 'voted_at' => now()]);
    Vote::create(['user_id' => $kandidatSatu->id, 'candidate_id' => $candidateDua->id, 'voted_at' => now()]);
    Vote::create(['user_id' => $kandidatDua->id, 'candidate_id' => $candidateDua->id, 'voted_at' => now()]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->where('stats.sudah_voting', 3)
            ->where('stats.belum_voting', 2)
            ->where('stats.partisipasi', 60)
            ->has('perolehan', 2)
            ->where('perolehan.0.nama_lengkap', 'Kandidat Satu')
            ->where('perolehan.0.votes', 1)
            ->where('perolehan.0.percentage', 33.3)
            ->where('perolehan.1.votes', 2)
            ->where('perolehan.1.percentage', 66.7)
            ->has('kelas')
            ->has('aktivitas', 0)
    );
});

test('dashboard lists the ten most recent vote logs', function () {
    $admin = User::factory()->admin()->create();
    $kandidat = User::factory()->kandidat()->create();

    $candidate = Candidate::create([
        'user_id' => $kandidat->id,
        'foto' => 'kandidat.jpg',
        'nama_lengkap' => 'Kandidat Satu',
        'nomor_urut' => 1,
        'nis' => '1001',
        'kelas' => 'XII IPA 1',
        'visi' => 'Visi',
        'misi' => 'Misi',
    ]);

    // 12 log dibuat berurutan; hanya 10 terbaru yang boleh tampil.
    foreach (range(1, 12) as $index) {
        $pemilih = User::factory()->create();

        Profile::create([
            'user_id' => $pemilih->id,
            'nama_lengkap' => 'Pemilih '.sprintf('%02d', $index),
            'kelas' => 'XII IPA '.($index % 2 === 0 ? 1 : 2),
        ]);

        VoteLog::create([
            'user_id' => $pemilih->id,
            'candidate_id' => $candidate->id,
            'action' => 'vote_cast',
            'ip_address' => '127.0.0.1',
        ]);
    }

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->has('aktivitas', 10)
            ->where('aktivitas.0.nama_pemilih', 'Pemilih 12')
            ->where('aktivitas.0.kelas', 'XII IPA 1')
            ->where('aktivitas.0.nomor_urut', 1)
            ->where('aktivitas.0.nama_kandidat', 'Kandidat Satu')
            ->where('aktivitas.0.aksi', 'vote_cast')
            ->where('aktivitas.0.ip_address', '127.0.0.1')
            ->where('aktivitas.9.nama_pemilih', 'Pemilih 03')
    );
});

test('dashboard shows zero statistics when there is no vote', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->where('stats.sudah_voting', 0)
            ->where('stats.belum_voting', 2)
            ->where('stats.partisipasi', 0)
    );
});

test('dashboard class distribution is ordered by participation percentage', function () {
    $admin = User::factory()->admin()->create();
    $kandidat = User::factory()->kandidat()->create();

    $candidate = Candidate::create([
        'user_id' => $kandidat->id,
        'foto' => 'k.jpg',
        'nama_lengkap' => 'Kandidat',
        'nomor_urut' => 1,
        'nis' => '1001',
        'kelas' => 'XII-A',
        'visi' => 'Visi',
        'misi' => 'Misi',
    ]);

    // X-A: 2 pemilih, 1 sudah -> 50%
    $a1 = User::factory()->create();
    $a2 = User::factory()->create();
    Profile::create(['user_id' => $a1->id, 'nama_lengkap' => 'A1', 'kelas' => 'X-A']);
    Profile::create(['user_id' => $a2->id, 'nama_lengkap' => 'A2', 'kelas' => 'X-A']);
    Vote::create(['user_id' => $a1->id, 'candidate_id' => $candidate->id, 'voted_at' => now()]);

    // X-B: 2 pemilih, 2 sudah -> 100%
    $b1 = User::factory()->create();
    $b2 = User::factory()->create();
    Profile::create(['user_id' => $b1->id, 'nama_lengkap' => 'B1', 'kelas' => 'X-B']);
    Profile::create(['user_id' => $b2->id, 'nama_lengkap' => 'B2', 'kelas' => 'X-B']);
    Vote::create(['user_id' => $b1->id, 'candidate_id' => $candidate->id, 'voted_at' => now()]);
    Vote::create(['user_id' => $b2->id, 'candidate_id' => $candidate->id, 'voted_at' => now()]);

    // X-C: 1 pemilih, belum voting -> 0%
    $c1 = User::factory()->create();
    Profile::create(['user_id' => $c1->id, 'nama_lengkap' => 'C1', 'kelas' => 'X-C']);

    $this->actingAs($admin)->get(route('dashboard'))->assertInertia(
        fn ($page) => $page
            ->has('kelas', 3)
            ->where('kelas.0.kelas', 'X-B')
            ->where('kelas.0.persentase', 100)
            ->where('kelas.1.kelas', 'X-A')
            ->where('kelas.1.persentase', 50)
            ->where('kelas.2.kelas', 'X-C')
            ->where('kelas.2.persentase', 0)
    );
});

test('dashboard class distribution only counts pemilih', function () {
    $admin = User::factory()->admin()->create();
    $kandidat = User::factory()->kandidat()->create();

    Profile::create(['user_id' => $admin->id, 'nama_lengkap' => 'Admin', 'kelas' => '']);
    Profile::create(['user_id' => $kandidat->id, 'nama_lengkap' => 'Kandidat', 'kelas' => 'XII-A']);

    $candidate = Candidate::create([
        'user_id' => $kandidat->id,
        'foto' => 'k.jpg',
        'nama_lengkap' => 'Kandidat',
        'nomor_urut' => 1,
        'nis' => '1001',
        'kelas' => 'XII-A',
        'visi' => 'Visi',
        'misi' => 'Misi',
    ]);

    $a = User::factory()->create();
    Profile::create(['user_id' => $a->id, 'nama_lengkap' => 'A', 'kelas' => 'X-A']);
    $b = User::factory()->create();
    Profile::create(['user_id' => $b->id, 'nama_lengkap' => 'B', 'kelas' => 'X-A']);
    $c = User::factory()->create();
    Profile::create(['user_id' => $c->id, 'nama_lengkap' => 'C', 'kelas' => 'X-B']);

    Vote::create(['user_id' => $a->id, 'candidate_id' => $candidate->id, 'voted_at' => now()]);

    $this->actingAs($admin)->get(route('dashboard'))->assertInertia(
        fn ($page) => $page
            ->has('kelas', 2)
            ->where('kelas.0.kelas', 'X-A')
            ->where('kelas.0.sudah', 1)
            ->where('kelas.0.belum', 1)
            ->where('kelas.0.total', 2)
            ->where('kelas.0.persentase', 50)
            ->where('kelas.1.kelas', 'X-B')
            ->where('kelas.1.sudah', 0)
    );
});
