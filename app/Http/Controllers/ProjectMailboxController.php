<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class ProjectMailboxController extends Controller
{
    public function index(Project $project)
    {
        $emails = $project->emailLogs()->with('sender')->latest()->get();
        return view('projects.mailbox', compact('project', 'emails'));
    }

    public function show(Project $project, EmailLog $email)
    {
        return view('projects.mailbox_show', compact('project', 'email'));
    }
}
