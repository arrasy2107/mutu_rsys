<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('denies user management to authenticated users who are not super administrators', function () {
    $user = User::factory()->create(['role' => 'komite_mutu']);

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertForbidden();
});

it('creates an account with an administrator-set password that must be changed', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);
    $password = 'Aa1234567890';

    $response = $this->actingAs($administrator)->post(route('users.store'), [
        'username' => 'pic_igd',
        'name' => 'PIC IGD',
        'email' => null,
        'role' => 'pic_unit',
        'unit_id' => null,
        'aktif' => true,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', ['username' => 'pic_igd', 'must_change_password' => true]);
    $createdUser = User::where('username', 'pic_igd')->firstOrFail();
    expect(Hash::check($password, $createdUser->password))->toBeTrue();
});

it('rejects a weak temporary password when creating an account', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($administrator)->post(route('users.store'), [
        'username' => 'pic_igd',
        'name' => 'PIC IGD',
        'email' => null,
        'role' => 'pic_unit',
        'unit_id' => null,
        'aktif' => true,
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseMissing('users', ['username' => 'pic_igd']);
});

it('requires a user to change an administrator-set password at next login', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);
    $user = User::factory()->create([
        'username' => 'pic_igd',
        'role' => 'pic_unit',
        'must_change_password' => false,
    ]);
    $password = 'Bb1234567890';

    $response = $this->actingAs($administrator)->put(route('users.update', $user), [
        'username' => $user->username,
        'name' => $user->name,
        'nama_lengkap' => $user->nama_lengkap,
        'nip' => $user->nip,
        'email' => $user->email,
        'role' => $user->role,
        'unit_id' => $user->unit_id,
        'aktif' => true,
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertRedirect(route('users.index'));
    expect($user->fresh()->must_change_password)->toBeTrue()
        ->and(Hash::check($password, $user->fresh()->password))->toBeTrue();
});

it('preserves the current password and change requirement when password fields are blank', function () {
    $administrator = User::factory()->create(['role' => 'superadmin']);
    $user = User::factory()->create([
        'username' => 'pic_igd',
        'role' => 'pic_unit',
        'must_change_password' => false,
    ]);
    $currentPassword = $user->password;

    $response = $this->actingAs($administrator)->put(route('users.update', $user), [
        'username' => $user->username,
        'name' => $user->name,
        'nama_lengkap' => $user->nama_lengkap,
        'nip' => $user->nip,
        'email' => $user->email,
        'role' => $user->role,
        'unit_id' => $user->unit_id,
        'aktif' => true,
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertRedirect(route('users.index'));
    expect($user->fresh()->password)->toBe($currentPassword)
        ->and($user->fresh()->must_change_password)->toBeFalse();
});

it('rejects a weak password during the required password change', function () {
    $user = User::factory()->create([
        'role' => 'pic_unit',
        'must_change_password' => true,
        'password' => Hash::make('TempOldPass123'),
    ]);

    $response = $this->actingAs($user)->post(route('password.force-change.update'), [
        'password' => 'alllowercase123',
        'password_confirmation' => 'alllowercase123',
    ]);

    $response->assertSessionHasErrors('password');
    expect($user->fresh()->must_change_password)->toBeTrue()
        ->and(Hash::check('TempOldPass123', $user->fresh()->password))->toBeTrue();
});

it('changes the required temporary password and clears the change requirement', function () {
    $user = User::factory()->create([
        'role' => 'pic_unit',
        'must_change_password' => true,
        'password' => Hash::make('TempOldPass123'),
    ]);
    $newPassword = 'NewStrongPass123';

    $response = $this->actingAs($user)->post(route('password.force-change.update'), [
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertRedirect(route('dashboard'));
    expect($user->fresh()->must_change_password)->toBeFalse()
        ->and(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();
});
