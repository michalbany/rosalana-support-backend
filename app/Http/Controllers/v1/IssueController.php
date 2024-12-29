<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\IssueResource;
use App\Models\Issue;
use Illuminate\Http\Request;
use PHPUnit\TestRunner\IssueFilter;

class IssueController extends Controller
{
    public function index(IssueFilter $filter)
    {
        $issues = Issue::filter($filter)->get();

        return $this->ok('Issues fetched successfully', IssueResource::collection($issues));
    }

    public function show(int $id)
    {
        return $this->ok('Issue fetched successfully', IssueResource::make(Issue::findOrFail($id)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:50',
            'content' => 'required|string',
            'app_id' => 'required|integer|exists:apps,id',
            'user_id' => 'required|integer|exists:users,id',
            'visibility' => 'required|string|in:public,private',
        ]);

        $issue = Issue::create([
            'title' => $request->title,
            'content' => $request->content,
            'app_id' => $request->app_id,
            'user_id' => $request->user_id,
            'visibility' => $request->visibility,
        ]);

        return $this->ok('Issue created successfully', IssueResource::make($issue));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'title' => 'string|max:50',
            'content' => 'string',
            'app_id' => 'integer|exists:apps,id',
            'user_id' => 'integer|exists:users,id',
            'visibility' => 'string|in:public,private',
            'status' => 'string|in:open,closed,solved',
        ]);

        $issue = Issue::findOrFail($id);

        if ($request->status === 'closed') {
            $issue->close();
        } elseif ($request->status === 'open') {
            $issue->open();
        } elseif ($request->status === 'solved') {
            $issue->solve();
        }

        if ($request->visibility === 'private') {
            $issue->private();
        }

        if ($request->visibility === 'public') {
            $issue->public();
        }

        $issue->update($request->all());

        return $this->ok('Issue updated successfully', IssueResource::make($issue));
    }

    public function destroy(int $id)
    {
        $issue = Issue::findOrFail($id);

        if (!request()->user()->is_admin) {
            return $this->unauthorized(new \Exception('You are not authorized to perform this action'));
        }

        $issue->delete();

        return $this->ok('Issue deleted successfully');
    }
}
