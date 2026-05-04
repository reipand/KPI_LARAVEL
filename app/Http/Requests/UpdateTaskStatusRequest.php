<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'on_progress', 'done', 'Pending', 'Dalam Proses', 'Selesai'])],
            'actual_value' => ['nullable', 'numeric', 'min:0'],
            'end_date' => ['nullable', 'date'],
            'file_evidence' => [
                Rule::requiredIf(fn () => $this->requiresEvidence()),
                'file',
                'mimes:pdf,png,jpg,jpeg,doc,docx,xlsx',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file_evidence.required' => 'Evidence wajib diunggah saat task HR ditandai selesai.',
        ];
    }

    private function requiresEvidence(): bool
    {
        $task = $this->route('task');

        if (! $task instanceof Task) {
            return false;
        }

        return Task::normalizeStatus((string) $this->input('status')) === Task::STATUS_DONE
            && ! $task->file_evidence
            && ! $this->hasFile('file_evidence');
    }
}
