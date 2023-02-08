<?php

namespace App\Repositories;

use App\Events\TakingAnActionOnTask;
use App\Http\Resources\TaskIndexResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Models\Board;
use App\Models\Status;
use Illuminate\Auth\Access\AuthorizationException;

class TaskRepository extends BaseRepository
{
    public function search(Board $board, $data)
    {
        $query = Task::canBrowse()->where('board_id', $board->id)->latest();
        isset($data['title']) ?? $query->whereRaw("LOWER(`title`) LIKE '%" . strtolower($data['title']) . "%'");
        $tasks=$query->get();
        return $this->paginate(TaskIndexResource::collection($tasks));
    }

    public function create(Board $board, $data)
    {
        $data['status_id'] = isset($data['status_id']) ?: $board->statuses()->where('initial', true)->first()->id;
        if (isset($data['assignee_id']) && isset($data['status_id']) && !$this->canAssign($data['status_id'], $data['assignee_id'])) {
            throw new AuthorizationException('task cannot be assigned to the user');
        }
        $data['board_id'] = $board->id;
        $task = Task::create($data);
        event(new TakingAnActionOnTask($task, 'Task created'));
        return $task;
    }

    public function update(Task $Task, $data)
    {
        $Task->update($data);
        return $Task;
    }

    public function delete(Task $Task)
    {
        return $Task->delete();
    }

    public function canAssign($status_id, $user_id)
    {
        $user = User::find($user_id);
        return $user->role->canBeAssignedStatuses()->where('statuses.id', $status_id)->exists();
    }

    public function canMove($user_id, $status_id)
    {
        $user = User::find($user_id);
        return  $user->role->canMoveStatuses()->where('statuses.id', $status_id)->exists();
    }

    public function assign(Task $task, $data)
    {
        if (!$this->canAssign($task->id, $data['user_id'])) {
            throw new AuthorizationException('task cannot be assigned to the user');
        }
        $assignee = $task->board->users()->where('user_id', $data['user_id'])->first();
        $task->update([
            'assignee_id' => $assignee->pivot->id
        ]);
        event(new TakingAnActionOnTask($task, 'Task assigned to' . $assignee->user_id));
        return new TaskResource($task);
    }

    public function move(Task $task, $data)
    {
        if (!$this->canMove(auth()->user()->id, $task->id)) {
            throw new AuthorizationException('task cannot be assigned to the user');
        }
        $oldStatus = $task->currentStatus;
        $newStatus = Status::find($data['status_id']);
        $task->update([
            'status_id' => $data['status_id']
        ]);
        event(new TakingAnActionOnTask($task, 'Task moved from' . $oldStatus->name . 'To ' . $newStatus->name));
        return $task;
    }
}