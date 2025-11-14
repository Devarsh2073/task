<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->hasPermissionTo('view-any-task')
            ? Task::query()
            : $user->tasks();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%");
            });
        }

        if ($tags = $request->query('tags')) {
            $tagArray = explode(',', $tags);
            $query->whereHas('tags', fn($q) => $q->whereIn('name', $tagArray));
        }

        if ($from = $request->query('due_from')) {
            $query->whereDate('due_date', '>=', $from);
        }
        if ($to = $request->query('due_to')) {
            $query->whereDate('due_date', '<=', $to);
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $tasks = $query->with(['user.roles', 'user.permissions', 'tags'])->paginate(15);

        return response()->json([
            'data' => collect($tasks->items())->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'due_date' => $task->due_date,
                    'created_at' => $task->created_at,
                    'user' => [
                        'id' => $task->user->id,
                        'name' => $task->user->name,
                        'email' => $task->user->email,
                        'roles' => $task->user->getRoleNames()->toArray(),
                        'permissions' => $task->user->getAllPermissions()->pluck('name')->toArray(),
                    ],
                    'tags' => $task->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name])->toArray(),
                ];
            })->toArray(),
            'current_page' => $tasks->currentPage(),
            'total' => $tasks->total(),
            'per_page' => $tasks->perPage(),
            'last_page' => $tasks->lastPage(),
        ]);
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:pending,in-progress,completed',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $task = $request->user()->tasks()->create($validated);

        if ($request->has('tags')) {
            foreach ($request->tags as $tag) {
                $task->tags()->create(['name' => $tag]);
            }
        }

        $task->load(['user.roles', 'user.permissions', 'tags']);

        return response()->json([
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'due_date' => $task->due_date,
            'created_at' => $task->created_at,
            'user' => [
                'id' => $task->user->id,
                'name' => $task->user->name,
                'email' => $task->user->email,
                'roles' => $task->user->getRoleNames()->toArray(),
                'permissions' => $task->user->getAllPermissions()->pluck('name')->toArray(),
            ],
            'tags' => $task->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]),
        ], 201);
    }

    public function show($id)
    {
        $task = Task::with(['user.roles', 'user.permissions', 'tags'])->findOrFail($id);

        $user = Auth::user();
        if ($task->user_id !== $user->id && !$user->hasPermissionTo('view-any-task')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'due_date' => $task->due_date,
            'created_at' => $task->created_at,
            'user' => [
                'id' => $task->user->id,
                'name' => $task->user->name,
                'email' => $task->user->email,
                'roles' => $task->user->getRoleNames()->toArray(),
                'permissions' => $task->user->getAllPermissions()->pluck('name')->toArray(),
            ],
            'tags' => $task->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]),
        ]);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $user = $request->user();

        if ($task->user_id !== $user->id && !$user->hasPermissionTo('update-any-task')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $task->update($validated);

        if ($request->has('tags')) {
            $task->tags()->delete();
            foreach ($request->tags as $tag) {
                $task->tags()->create(['name' => $tag]);
            }
        }

        $task->load(['user.roles', 'user.permissions', 'tags']);

        return response()->json([
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'due_date' => $task->due_date,
            'created_at' => $task->created_at,
            'user' => [
                'id' => $task->user->id,
                'name' => $task->user->name,
                'email' => $task->user->email,
                'roles' => $task->user->getRoleNames()->toArray(),
                'permissions' => $task->user->getAllPermissions()->pluck('name')->toArray(),
            ],
            'tags' => $task->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name]),
        ]);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $user = Auth::user();

        if ($task->user_id !== $user->id && !$user->hasPermissionTo('delete-any-task')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->delete();
        return response()->json(null, 204);
    }
}