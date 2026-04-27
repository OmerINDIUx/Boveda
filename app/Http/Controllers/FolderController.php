<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Folder;
use App\Models\Document;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'discipline_id' => 'nullable|exists:disciplines,id'
        ]);

        $folder = Folder::create([
            'project_id' => $project->id,
            'parent_id' => $request->parent_id,
            'discipline_id' => $request->discipline_id,
            'name' => $request->name
        ]);

        return back()->with('success', 'Carpeta creada correctamente.');
    }

    public function moveDocument(Request $request, Document $document)
    {
        $request->validate([
            'folder_id' => 'nullable|exists:folders,id'
        ]);

        $document->update(['folder_id' => $request->folder_id]);

        return response()->json(['status' => 'success']);
    }
}
