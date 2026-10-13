<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'status'];

    protected static function booted(): void
    {
        static::created(function (Task $task) {
            // New tasks start above the existing list without renumbering it.
            $task->position = -$task->id;
            $task->saveQuietly();
        });
    }

    protected function casts(): array
    {
        return ['status' => TaskStatus::class, 'position' => 'integer'];
    }
}
