<?php

namespace App\Http\Controllers;

use App\Models\Discipline;
use Illuminate\Http\Request;

class DisciplineController extends Controller
{
    public function index()
    {
        $disciplines = Discipline::all();
        return view('disciplines.index', compact('disciplines'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:disciplines,name',
            'prefix' => 'required|string|max:10'
        ]);

        Discipline::create([
            'name' => $request->name,
            'prefix' => $request->prefix
        ]);

        return back()->with('success', 'Disciplina global registrada exitosamente.');
    }

    public function destroy(Discipline $discipline)
    {
        $discipline->delete();
        return back()->with('success', 'Disciplina eliminada del catálogo global.');
    }
}

