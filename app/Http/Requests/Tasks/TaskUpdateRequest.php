<?php

namespace App\Http\Requests\Tasks;

use App\Rules\OneOfBoardLabels;
use App\Rules\OneOfBoardMembers;
use App\Rules\OneOfBoardStatuses;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('update', request('task'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'status_id'    => ['nullable', 'exists:statuses,id', new OneOfBoardStatuses()],
            'assignee_id'  => ['nullable', 'exists:statuses,id', new OneOfBoardMembers()],
            'title'        => ['nullable', 'string'],
            'description'  => ['nullable', 'string'],
            'image'        => ['nullable', 'string'],
            'due_date'     => ['nullable', 'date'], 'after_or_equal:today',
            'labels'       => ['nullable', 'array'],
            'labels.*.id'  => ['required', new OneOfBoardLabels()]
        ];
    }
}
