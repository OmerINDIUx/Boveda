<?php

namespace App\Http\Controllers;

use App\Mail\QuickRfiMessage;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class GeneralEmailController extends Controller
{
    public function index(Request $request)
    {
        $emails = EmailLog::with(['sender', 'project'])
            ->orderByDesc('is_important')
            ->latest()
            ->limit(120)
            ->get();

        $selectedEmail = null;
        if ($request->filled('email')) {
            $selectedEmail = $emails->firstWhere('id', (int) $request->integer('email'));
        }
        if (!$selectedEmail) {
            $selectedEmail = $emails->first();
        }
        if ($selectedEmail && !$selectedEmail->is_read) {
            $selectedEmail->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
            $selectedEmail->refresh();
        }

        $stats = [
            'total' => EmailLog::count(),
            'unread' => EmailLog::where('is_read', false)->count(),
            'important' => EmailLog::where('is_important', true)->count(),
            'today' => EmailLog::whereDate('created_at', now()->toDateString())->count(),
        ];

        $users = User::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $templates = EmailTemplate::where('context', 'general')->latest()->get();

        return view('emails.index', compact('emails', 'selectedEmail', 'stats', 'users', 'projects', 'templates'));
    }

    public function templates()
    {
        $templates = EmailTemplate::where('context', 'general')->latest()->get();
        return view('emails.templates', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'subject_template' => 'required|string|max:255',
            'form_schema' => 'required|array|min:1',
            'form_schema.*.key' => 'required|string|max:80',
            'form_schema.*.label' => 'required|string|max:120',
            'form_schema.*.type' => 'required|string|in:text,textarea,date,number',
            'form_schema.*.required' => 'nullable|boolean',
        ]);

        EmailTemplate::create([
            'name' => $data['name'],
            'context' => 'general',
            'subject_template' => $data['subject_template'],
            'body_template' => '',
            'form_schema' => $data['form_schema'],
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Plantilla guardada en Correos.');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'exists:users,id',
            'subject' => 'required|string|max:255',
            'template_id' => 'nullable|exists:email_templates,id',
            'form_payload' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $template = !empty($data['template_id']) ? EmailTemplate::find($data['template_id']) : null;
        $project = !empty($data['project_id']) ? Project::find($data['project_id']) : null;
        $senderId = Auth::id() ?? User::first()?->id;
        $recipients = User::whereIn('id', $data['recipient_ids'])->get();

        foreach ($recipients as $recipient) {
            $subject = $data['subject'];
            $body = $template
                ? $this->buildTemplateBody($template, $data['form_payload'] ?? [], (string) ($data['notes'] ?? ''), $project)
                : (string) ($data['notes'] ?? '');

            Mail::to($recipient->email)->send(new QuickRfiMessage($subject, $body));

            EmailLog::create([
                'project_id' => $project?->id,
                'sender_id' => $senderId,
                'recipient' => $recipient->email,
                'subject' => $subject,
                'body' => $body,
                'type' => 'GENERAL',
                'is_read' => false,
                'is_important' => false,
            ]);
        }

        AuditLog::create([
            'user_id' => $senderId,
            'action' => 'GENERAL_MAIL_SENT',
            'model_type' => EmailLog::class,
            'model_id' => null,
            'details' => 'Se enviaron correos manuales a ' . $recipients->count() . ' usuario(s) desde el módulo de correos.',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Correo enviado correctamente.');
    }

    private function buildTemplateBody(EmailTemplate $template, array $payload, string $notes, ?Project $project): string
    {
        $lines = [];
        $lines[] = strtoupper($template->name);
        if ($project) {
            $lines[] = 'Proyecto: ' . $project->name . ' (' . $project->code . ')';
        }
        $lines[] = '';

        foreach (($template->form_schema ?? []) as $field) {
            $key = (string) ($field['key'] ?? '');
            $label = (string) ($field['label'] ?? $key);
            $value = $payload[$key] ?? '-';
            $lines[] = $label . ': ' . (is_scalar($value) && (string) $value !== '' ? (string) $value : '-');
        }

        if (trim($notes) !== '') {
            $lines[] = '';
            $lines[] = 'Notas:';
            $lines[] = $notes;
        }

        return implode(PHP_EOL, $lines);
    }

    public function toggleImportant(EmailLog $email)
    {
        $email->update(['is_important' => !$email->is_important]);
        return back()->with('success', 'Marcación de importancia actualizada.');
    }
}
