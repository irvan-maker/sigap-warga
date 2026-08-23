<?php

namespace App\Models\Concerns;

use App\Models\LetterSubmission;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait GuardsImmutableLetterSubmission
{
    public static function bootGuardsImmutableLetterSubmission(): void
    {
        static::creating(function (Model $model): void {
            /** @var LetterSubmission|null $submission */
            $submission = $model->immutableLetterSubmission();

            if ($submission === null) {
                throw new LogicException('Induk snapshot pengajuan surat tidak valid.');
            }

            if ($submission->sealed_at !== null) {
                throw new LogicException('Snapshot pengajuan surat telah disegel dan tidak dapat ditambah.');
            }

            $model->assertImmutableLetterSubmissionOwnership($submission);
        });

        static::updating(function (Model $model): void {
            throw new LogicException('Data pengajuan surat yang telah disimpan tidak dapat diubah.');
        });

        static::deleting(function (Model $model): void {
            throw new LogicException('Data pengajuan surat yang telah disimpan tidak dapat dihapus langsung.');
        });
    }

    protected function immutableLetterSubmission(): ?LetterSubmission
    {
        return null;
    }

    protected function assertImmutableLetterSubmissionOwnership(LetterSubmission $submission): void
    {
        // Models with version-pinned definition IDs override this boundary check.
    }
}
