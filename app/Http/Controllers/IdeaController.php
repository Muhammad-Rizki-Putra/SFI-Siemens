<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\IdeaAttachment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;

class IdeaController extends Controller
{
    public function index()
    {
        $ideasQuery = Idea::with(['user.department', 'score'])->latest();

        if (Auth::check()) {
            $ideasQuery->where('user_id', Auth::id());
        }

        $ideas = $ideasQuery->get();

        return Inertia::render('Ideas/Index', [
            'ideas' => $ideas
        ]);
    }

    public function create()
    {
        $employees = User::orderBy('name')->get(['id', 'name']);
        $currentUserName = Auth::user()?->name;

        return Inertia::render('Ideas/Create', [
            'employees' => $employees,
            'currentUserName' => $currentUserName,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type_of_improvement' => 'required|string|max:255',
            'problem_description' => 'required|string',
            'solution_description' => 'required|string',
            'area_of_application' => 'required|string|max:255',
            'implementation_date' => 'nullable|date',
            'team_members' => 'required|array|min:1',
            'team_members.*' => 'required|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => ['file', function ($attribute, $file, $fail) {
                $limits = $this->attachmentLimits();
                $extension = strtolower($file->getClientOriginalExtension());

                $type = $this->attachmentTypeForExtension($extension);
                if (!$type) {
                    $fail('Unsupported file type.');
                    return;
                }

                $maxSizeKb = $limits[$type]['max_kb'];
                if (($file->getSize() / 1024) > $maxSizeKb) {
                    $fail("File is too large. Max {$maxSizeKb} KB.");
                }
            }],
        ]);

        $now = Carbon::now();
        $calendarMonth = (int) $now->format('m');
        $calendarYear = (int) $now->format('Y');
        $fyYear = $calendarMonth >= 10 ? $calendarYear + 1 : $calendarYear;
        $fyShort = substr((string) $fyYear, -2);
        $fyPeriod = $calendarMonth >= 10 ? $calendarMonth - 9 : $calendarMonth + 3;

        $periodStart = $now->copy()->startOfMonth();
        $periodEnd = $now->copy()->endOfMonth();
        $count = Idea::whereBetween('created_at', [$periodStart, $periodEnd])->count() + 1;
        $sequence = str_pad((string) $count, 2, '0', STR_PAD_LEFT);
        $submissionCode = sprintf('SFI-FY%s-P%02d-%s', $fyShort, $fyPeriod, $sequence);

        DB::transaction(function () use ($request, $validated, $submissionCode, $calendarMonth, $calendarYear) {
            $idea = Idea::create([
                'submission_code' => $submissionCode,
                'user_id' => Auth::id() ?: null,
                'title' => $validated['title'],
                'type_of_improvement' => $validated['type_of_improvement'],
                'problem_description' => $validated['problem_description'],
                'solution_description' => $validated['solution_description'],
                'area_of_application' => $validated['area_of_application'],
                'implementation_date' => $validated['implementation_date'] ?? null,
                'month' => $calendarMonth,
                'year' => $calendarYear,
                'status' => 'SPS Review',
                'completion_percentage' => 10,
                'current_reviewer_role' => 'sps',
            ]);

            $submitterName = Auth::user()?->name;

            $teamMembers = collect($validated['team_members'] ?? [])
                ->when($submitterName, fn ($members) => $members->push($submitterName))
                ->map(fn ($name) => trim($name))
                ->filter()
                ->unique()
                ->values();

            if ($teamMembers->isNotEmpty()) {
                $idea->teamMembers()->createMany(
                    $teamMembers->map(fn ($name) => ['name' => $name])->all()
                );
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachmentData = $this->storeAttachment($file, $idea);
                    $idea->attachments()->create($attachmentData);
                }
            }

            \App\Models\ReviewLog::create([
                'idea_id' => $idea->id,
                'reviewer_id' => Auth::id(),
                'action' => 'Submitted',
                'comments' => 'Idea submitted and sent for SPS Review.',
            ]);
        });

        return redirect()->route('ideas.index')->with('success', 'Idea submitted successfully and sent to SPS for review.');
    }

    public function show(Idea $idea)
    {
        if (Auth::check() && $idea->user_id !== Auth::id()) {
            abort(403);
        }

        $idea->load(['user.department', 'score', 'reviewLogs.reviewer', 'teamMembers', 'attachments']);

        if ($idea->status === 'Revision Requested' && Auth::id() === $idea->user_id) {
            $alreadyViewed = \App\Models\ReviewLog::where('idea_id', $idea->id)
                ->where('reviewer_id', Auth::id())
                ->where('action', 'Revision Viewed')
                ->exists();
            
            if (!$alreadyViewed) {
                \App\Models\ReviewLog::create([
                    'idea_id' => $idea->id,
                    'reviewer_id' => Auth::id(),
                    'action' => 'Revision Viewed',
                    'comments' => 'User viewed the revision request.',
                ]);
            }
        }

        return Inertia::render('Ideas/Show', [
            'idea' => $idea,
            'backUrl' => route('ideas.index'),
            'adminActions' => false,
        ]);
    }

    public function edit(Idea $idea)
    {
        $idea->load(['teamMembers', 'attachments']);
        $employees = User::orderBy('name')->get(['id', 'name']);
        $currentUserName = Auth::user()?->name;

        return Inertia::render('Ideas/Edit', [
            'idea' => $idea,
            'employees' => $employees,
            'currentUserName' => $currentUserName,
        ]);
    }

    public function update(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type_of_improvement' => 'required|string|max:255',
            'problem_description' => 'required|string',
            'solution_description' => 'required|string',
            'area_of_application' => 'required|string|max:255',
            'implementation_date' => 'nullable|date',
            'team_members' => 'required|array|min:1',
            'team_members.*' => 'required|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => ['file', function ($attribute, $file, $fail) {
                $limits = $this->attachmentLimits();
                $extension = strtolower($file->getClientOriginalExtension());

                $type = $this->attachmentTypeForExtension($extension);
                if (!$type) {
                    $fail('Unsupported file type.');
                    return;
                }

                $maxSizeKb = $limits[$type]['max_kb'];
                if (($file->getSize() / 1024) > $maxSizeKb) {
                    $fail("File is too large. Max {$maxSizeKb} KB.");
                }
            }],
        ]);

        DB::transaction(function () use ($request, $validated, $idea) {
            $idea->update([
                'title' => $validated['title'],
                'type_of_improvement' => $validated['type_of_improvement'],
                'problem_description' => $validated['problem_description'],
                'solution_description' => $validated['solution_description'],
                'area_of_application' => $validated['area_of_application'],
                'implementation_date' => $validated['implementation_date'] ?? null,
            ]);

            if (in_array($idea->status, ['Revision Requested', 'Draft'], true)) {
                $checkpoint = $idea->revision_checkpoint ?? 'SPS Review';
                $idea->status = $checkpoint;
                $idea->revision_checkpoint = null;
                
                if ($checkpoint === 'Technical Review') {
                    $idea->current_reviewer_role = 'technical_reviewer';
                    $idea->completion_percentage = 40;
                } elseif ($checkpoint === 'Managerial Review') {
                    $idea->current_reviewer_role = 'managerial';
                    $idea->completion_percentage = 60;
                } elseif ($checkpoint === 'Reward Processing') {
                    $idea->current_reviewer_role = 'reward_processing';
                    $idea->completion_percentage = 80;
                } else {
                    $idea->current_reviewer_role = 'sps';
                    $idea->completion_percentage = 20;
                }
                
                $idea->save();

                \App\Models\ReviewLog::create([
                    'idea_id' => $idea->id,
                    'reviewer_id' => Auth::id(),
                    'action' => 'Resubmitted',
                    'comments' => 'Submission updated after revision request.',
                ]);
            }

            $submitterName = Auth::user()?->name;

            $teamMembers = collect($validated['team_members'] ?? [])
                ->when($submitterName, fn ($members) => $members->push($submitterName))
                ->map(fn ($name) => trim($name))
                ->filter()
                ->unique()
                ->values();

            $idea->teamMembers()->delete();
            if ($teamMembers->isNotEmpty()) {
                $idea->teamMembers()->createMany(
                    $teamMembers->map(fn ($name) => ['name' => $name])->all()
                );
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachmentData = $this->storeAttachment($file, $idea);
                    $idea->attachments()->create($attachmentData);
                }
            }
        });

        return redirect()
            ->route('ideas.show', $idea)
            ->with('success', 'Idea updated successfully.');
    }

    private function attachmentLimits(): array
    {
        return [
            'image' => ['extensions' => ['jpg', 'jpeg', 'png', 'webp'], 'max_kb' => 5120],
            'document' => ['extensions' => ['pdf', 'docx', 'xlsx', 'pptx'], 'max_kb' => 10240],
            'video' => ['extensions' => ['mp4', 'mov'], 'max_kb' => 51200],
        ];
    }

    private function attachmentTypeForExtension(string $extension): ?string
    {
        foreach ($this->attachmentLimits() as $type => $config) {
            if (in_array($extension, $config['extensions'], true)) {
                return $type;
            }
        }

        return null;
    }

    private function storeAttachment(UploadedFile $file, Idea $idea): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $isVideo = $this->attachmentTypeForExtension($extension) === 'video';

        $relativeDir = "idea-attachments/{$idea->id}";
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $relativePath = $relativeDir . '/' . $fileName;

        $isCompressed = false;
        $compressionRatio = null;

        if ($isVideo && $this->ffmpegAvailable()) {
            $outputPath = Storage::disk('public')->path($relativePath);
            $compressed = $this->compressVideo($file->getRealPath(), $outputPath);

            if ($compressed) {
                $isCompressed = true;
                $compressedSize = Storage::disk('public')->size($relativePath);
                $compressionRatio = $file->getSize() > 0 ? round($compressedSize / $file->getSize(), 2) : null;

                if ($compressionRatio !== null && $compressionRatio >= 1) {
                    Storage::disk('public')->delete($relativePath);
                    $isCompressed = false;
                    $compressionRatio = null;
                }
            }
        }

        if (!$isCompressed) {
            $file->storeAs($relativeDir, $fileName, 'public');
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => $relativePath,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => Storage::disk('public')->size($relativePath),
            'is_video' => $isVideo,
            'is_compressed' => $isCompressed,
            'compression_ratio' => $compressionRatio,
        ];
    }

    private function ffmpegAvailable(): bool
    {
        $process = new Process(['ffmpeg', '-version']);
        $process->setTimeout(5);
        $process->run();

        return $process->isSuccessful();
    }

    private function compressVideo(string $inputPath, string $outputPath): bool
    {
        $process = new Process([
            'ffmpeg',
            '-y',
            '-i',
            $inputPath,
            '-c:v',
            'libx264',
            '-preset',
            'medium',
            '-crf',
            '28',
            '-c:a',
            'aac',
            '-b:a',
            '128k',
            $outputPath,
        ]);
        $process->setTimeout(120);
        $process->run();

        return $process->isSuccessful() && file_exists($outputPath);
    }

    public function leaderboard()
    {
        $daily = Idea::selectRaw('DATE(created_at) as period, COUNT(*) as total')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $monthly = Idea::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $yearly = Idea::selectRaw('YEAR(created_at) as period, COUNT(*) as total')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $topSubmitters = Idea::selectRaw('user_id, COUNT(*) as total')
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('Ideas/Leaderboard', [
            'daily' => $daily,
            'monthly' => $monthly,
            'yearly' => $yearly,
            'topSubmitters' => $topSubmitters,
        ]);
    }

    public function attachment(IdeaAttachment $attachment)
    {
        $path = Storage::disk('public')->path($attachment->storage_path);
        if (!file_exists($path)) {
            abort(404);
        }

        $mimeType = $attachment->mime_type ?: Storage::disk('public')->mimeType($attachment->storage_path);
        $fileName = $attachment->original_name ?: basename($path);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            $mimeType = 'application/pdf';
        }

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function attachmentPreview(IdeaAttachment $attachment)
    {
        $fileName = $attachment->original_name ?: basename($attachment->storage_path);

        return view('pdf-preview', [
            'fileUrl' => route('attachments.show', $attachment),
            'fileName' => $fileName,
        ]);
    }

    public function attachmentDelete(IdeaAttachment $attachment)
    {
        Storage::disk('public')->delete($attachment->storage_path);
        $attachment->delete();

        return response()->json(['ok' => true]);
    }
}