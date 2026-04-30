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

        $folder = Folder::firstOrCreate([
            'project_id' => $project->id,
            'discipline_id' => $request->discipline_id,
            'parent_id' => $request->parent_id,
            'name' => $request->name
        ]);

        return redirect()->route('projects.show', [$project->id, 'folder' => $folder->id])->with('success', 'Carpeta creada correctamente.');
    }

    public function update(Request $request, Folder $folder)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $folder->update(['name' => $request->name]);
        return back()->with('success', 'Carpeta renombrada correctamente.');
    }

    public function destroy(Folder $folder)
    {
        $folder->delete(); // Soft delete
        return back()->with('success', 'Carpeta enviada a la papelera.');
    }

    public function restore($id)
    {
        $folder = Folder::withTrashed()->findOrFail($id);
        $folder->restore();
        return back()->with('success', 'Carpeta restaurada.');
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
