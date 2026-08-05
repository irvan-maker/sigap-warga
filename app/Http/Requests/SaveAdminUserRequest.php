<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Rt;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

abstract class SaveAdminUserRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->targetUser()),
            ],
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
            'position' => ['nullable', Rule::enum(VillagePosition::class)],
            'rw_id' => ['nullable', 'integer', Rule::exists('rws', 'id')],
            'rt_id' => ['nullable', 'integer', Rule::exists('rts', 'id')],
        ];
    }

    /** @return array<int, mixed> */
    abstract protected function passwordRules(): array;

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateUserPlacement($validator)];
    }

    protected function prepareForValidation(): void
    {
        $role = $this->input('role') ?: null;
        $position = $this->input('position') ?: null;
        if ($position === null && $role === UserRole::ADMIN->value) {
            $position = VillagePosition::SYSTEM_ADMIN->value;
        }
        if ($position === null && $role === UserRole::KELURAHAN->value) {
            $position = VillagePosition::VILLAGE_SECRETARY->value;
        }
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'role' => $role,
            'position' => $position,
            'rw_id' => $this->input('rw_id') ?: null,
            'rt_id' => $this->input('rt_id') ?: null,
        ]);
    }

    protected function targetUser(): ?User
    {
        $user = $this->route('user');

        return $user instanceof User ? $user : null;
    }

    protected function validateAdditionalRules(Validator $validator, ?UserRole $role): void
    {
        // Implemented by requests with additional target-specific rules.
    }

    private function validateUserPlacement(Validator $validator): void
    {
        $role = UserRole::tryFrom((string) $this->input('role'));
        $rwId = $this->input('rw_id');
        $rtId = $this->input('rt_id');
        $position = VillagePosition::tryFrom((string) $this->input('position'));

        if ($role === UserRole::ADMIN || $role === UserRole::KELURAHAN) {
            if ($position === null) {
                $validator->errors()->add('position', 'Jabatan desa wajib dipilih.');
            }
            if ($rwId !== null) {
                $validator->errors()->add('rw_id', 'Role ini tidak boleh ditempatkan pada RW.');
            }

            if ($rtId !== null) {
                $validator->errors()->add('rt_id', 'Role ini tidak boleh ditempatkan pada RT.');
            }
        }

        if ($role === UserRole::RW) {
            if ($position !== null) {
                $validator->errors()->add('position', 'Petugas RW tidak memakai jabatan desa.');
            }
            if ($rwId === null) {
                $validator->errors()->add('rw_id', 'RW wajib dipilih untuk role RW.');
            }

            if ($rtId !== null) {
                $validator->errors()->add('rt_id', 'Role RW tidak boleh ditempatkan pada RT.');
            }
        }

        if ($role === UserRole::RT) {
            if ($position !== null) {
                $validator->errors()->add('position', 'Petugas RT tidak memakai jabatan desa.');
            }
            if ($rwId === null) {
                $validator->errors()->add('rw_id', 'RW wajib dipilih untuk role RT.');
            }

            if ($rtId === null) {
                $validator->errors()->add('rt_id', 'RT wajib dipilih untuk role RT.');
            } elseif ($rwId !== null && ! Rt::query()
                ->whereKey($rtId)
                ->where('rw_id', $rwId)
                ->exists()) {
                $validator->errors()->add('rt_id', 'RT harus berada di dalam RW yang dipilih.');
            }
        }

        $this->validateAdditionalRules($validator, $role);
        if ($this->user()?->isVillageSecretary() && ! in_array($role, [UserRole::RW, UserRole::RT], true)) {
            $validator->errors()->add('role', 'Sekretaris Desa hanya dapat mengelola akun Petugas RW atau Petugas RT.');
        }
    }

    /** @return array<int, mixed> */
    protected function requiredPasswordRules(): array
    {
        return ['required', 'confirmed', Password::min(12)];
    }
}
