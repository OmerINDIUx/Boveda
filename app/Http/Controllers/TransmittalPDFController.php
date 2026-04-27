<?php

namespace App\Http\Controllers;

use App\Models\Transmittal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TransmittalPDFController extends Controller
{
    public function download(Transmittal $transmittal)
    {
        $transmittal->load(['project', 'items.revision.document']);
        
        $pdf = Pdf::loadView('projects.transmittal_pdf', compact('transmittal'));
        
        return $pdf->download("Transmittal_{$transmittal->code}.pdf");
    }
}
