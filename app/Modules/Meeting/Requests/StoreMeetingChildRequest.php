<?php

namespace App\Modules\Meeting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->rulesFor((string) $this->route('child'), false);
    }

    protected function rulesFor(string $child, bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return match ($child) {
            'participants' => [
                'user_id' => [$required, 'integer', 'exists:users,id'],
                'role' => ['nullable', 'in:chair,secretary,delegate,guest'],
                'position' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', 'in:pending,present,absent,delegated'],
                'checkin_at' => ['nullable', 'date'],
                'absence_reason' => ['nullable', 'string'],
                'delegated_to_id' => ['nullable', 'integer', 'exists:users,id'],
                'sort_order' => ['nullable', 'integer'],
            ],
            'agendas' => [
                'title' => [$required, 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer'],
                'duration_minutes' => ['nullable', 'integer', 'min:0'],
                'presenter_id' => ['nullable', 'integer', 'exists:users,id'],
                'status' => ['nullable', 'in:pending,in_progress,completed,cancelled'],
            ],
            'documents' => [
                'agenda_id' => ['nullable', 'integer', 'exists:m_agendas,id'],
                'document_type_id' => ['nullable', 'integer', 'exists:m_document_types,id'],
                'document_field_id' => ['nullable', 'integer', 'exists:m_document_fields,id'],
                'issuing_agency_id' => ['nullable', 'integer', 'exists:m_issuing_agencies,id'],
                'document_signer_id' => ['nullable', 'integer', 'exists:m_document_signers,id'],
                'title' => [$required, 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'document_number' => ['nullable', 'string', 'max:100'],
                'issued_at' => ['nullable', 'date'],
                'status' => ['nullable', 'in:draft,active,archived'],
                'attachments' => ['nullable', 'array'],
                'attachments.*' => ['file'],
                'remove_attachment_ids' => ['nullable', 'array'],
                'remove_attachment_ids.*' => ['integer'],
            ],
            'conclusions' => [
                'agenda_id' => ['nullable', 'integer', 'exists:m_agendas,id'],
                'title' => [$required, 'string', 'max:255'],
                'content' => [$required, 'string'],
            ],
            'speech-requests' => [
                'agenda_id' => ['nullable', 'integer', 'exists:m_agendas,id'],
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'content' => [$required, 'string'],
                'status' => ['nullable', 'in:pending,approved,rejected'],
                'review_note' => ['nullable', 'string'],
                'reviewed_by' => ['nullable', 'integer', 'exists:users,id'],
                'reviewed_at' => ['nullable', 'date'],
            ],
            'votings' => [
                'agenda_id' => ['nullable', 'integer', 'exists:m_agendas,id'],
                'title' => [$required, 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'type' => ['nullable', 'in:public,anonymous'],
                'status' => ['nullable', 'in:pending,open,closed'],
                'options' => ['nullable', 'array'],
                'opened_at' => ['nullable', 'date'],
                'closed_at' => ['nullable', 'date'],
            ],
            'personal-notes' => [
                'document_id' => ['nullable', 'integer', 'exists:m_documents,id'],
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'content' => [$required, 'string'],
            ],
            'reminders' => [
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'title' => [$required, 'string', 'max:255'],
                'content' => ['nullable', 'string'],
                'remind_at' => [$required, 'date'],
                'status' => ['nullable', 'in:pending,sent,cancelled'],
            ],
            default => abort(404, 'Không tìm thấy chức năng con của Meeting.'),
        };
    }
}
