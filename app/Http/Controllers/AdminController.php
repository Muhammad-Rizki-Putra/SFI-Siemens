<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\ReviewLog;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        $ideas = Idea::with(['user.department', 'score'])
            ->orderByRaw("FIELD(status, 'SPS Review') DESC")
            ->latest()
            ->get();

        return Inertia::render('Admin/Ideas/Index', [
            'ideas' => $ideas
        ]);
    }

    public function create()
    {
         return Inertia::render('Admin/Ideas/Create');
    }

    public function show(Idea $idea)
    {
        $role = Auth::user()?->role;

        if (in_array($role, ['technical_manager', 'technical_reviewer'], true)) {
            return redirect()->route('admin.ideas.technical.show', $idea);
        }

        if ($role === 'financial_manager') {
            return redirect()->route('admin.ideas.financial.show', $idea);
        }

        return redirect()->route('admin.ideas.sps.show', $idea);
    }

    public function showSps(Idea $idea)
    {
        $idea->load(['user.department', 'score', 'reviewLogs.reviewer', 'teamMembers', 'attachments']);

        return Inertia::render('Ideas/Show', [
            'idea' => $idea,
            'backUrl' => route('admin.ideas.index'),
            'adminActions' => true,
        ]);
    }

    public function showTechnical(Idea $idea)
    {
        $idea->load(['user.department', 'score', 'reviewLogs.reviewer', 'teamMembers', 'attachments']);

        return Inertia::render('Admin/Ideas/TechnicalShow', [
            'idea' => $idea,
            'backUrl' => route('admin.ideas.index'),
        ]);
    }

    public function showFinancial(Idea $idea)
    {
        $idea->load(['user.department', 'score', 'reviewLogs.reviewer', 'teamMembers', 'attachments']);

        return Inertia::render('Admin/Ideas/FinancialShow', [
            'idea' => $idea,
            'backUrl' => route('admin.ideas.index'),
        ]);
    }

    public function review(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'action' => 'required|in:Approved,Rejected,Implemented',
            'reject_mode' => 'nullable|in:revise,closed',
            'comments' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $idea->status;
        $actionToLog = '';

        if ($validated['action'] === 'Rejected') {
            if (($validated['reject_mode'] ?? 'revise') === 'closed') {
                $idea->status = 'Closed';
                $idea->completion_percentage = 0;
                $idea->current_reviewer_role = null;
                $actionToLog = 'Closed';
            } else {
                $idea->status = 'Revision Requested';
                $idea->revision_checkpoint = $oldStatus;
                $idea->current_reviewer_role = null;
                $actionToLog = 'Revision Requested';
            }
        } elseif ($validated['action'] === 'Implemented') {
            $idea->status = 'Implemented';
            $idea->completion_percentage = 100;
            $idea->current_reviewer_role = null;
            $actionToLog = 'Implemented';
        } else {
            $actionToLog = $oldStatus; // Log the phase that was just completed
            if ($idea->status === 'SPS Review') {
                $idea->status = 'Technical Review';
                $idea->completion_percentage = 30;
                $idea->current_reviewer_role = 'technical_reviewer';
            } else {
                $idea->status = 'Implemented';
                $idea->completion_percentage = 100;
                $idea->current_reviewer_role = null;
            }
        }

        $idea->save();

        ReviewLog::create([
            'idea_id' => $idea->id,
            'reviewer_id' => Auth::id(),
            'action' => $actionToLog,
            'comments' => $validated['comments'] ?? '',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Review action recorded.');
    }

    public function workflow(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'action' => 'required|in:Approved,Rejected,Draft',
            'reject_mode' => 'nullable|in:revise,closed',
            'comments' => 'nullable|string|max:1000',
            'technical_pic' => 'nullable|string|max:255',
            'managerial_decision_summary' => 'nullable|string',
            'reward_request_type' => 'nullable|string|max:100',
            'reward_request_amount' => 'nullable|numeric|min:0',
            'reward_request_notes' => 'nullable|string',
            'reward_processing_status' => 'nullable|string|max:100',
            'reward_processed_at' => 'nullable|date',
            'opw_type' => 'nullable|string|max:100',
            'opw_notes' => 'nullable|string',
        ]);

        $idea->fill([
            'technical_pic' => $validated['technical_pic'] ?? null,
            'managerial_decision_summary' => $validated['managerial_decision_summary'] ?? null,
            'reward_request_type' => $validated['reward_request_type'] ?? null,
            'reward_request_amount' => $validated['reward_request_amount'] ?? null,
            'reward_request_notes' => $validated['reward_request_notes'] ?? null,
            'reward_processing_status' => $validated['reward_processing_status'] ?? null,
            'reward_processed_at' => $validated['reward_processed_at'] ?? null,
            'opw_type' => $validated['opw_type'] ?? null,
            'opw_notes' => $validated['opw_notes'] ?? null,
        ]);

        $oldStatus = $idea->status;
        $newStatus = $idea->status;
        $reviewerRole = $idea->current_reviewer_role;
        $completion = $idea->completion_percentage;
        $actionToLog = '';

        if ($validated['action'] === 'Draft') {
            $newStatus = 'Draft';
            $reviewerRole = null;
            $actionToLog = 'Draft';
        } elseif ($validated['action'] === 'Rejected') {
            if (($validated['reject_mode'] ?? 'revise') === 'closed') {
                $newStatus = 'Closed';
                $reviewerRole = null;
                $completion = 0;
                $actionToLog = 'Closed';
            } else {
                $newStatus = 'Revision Requested';
                $idea->revision_checkpoint = $oldStatus;
                $reviewerRole = null;
                $actionToLog = 'Revision Requested';
            }
        } else {
            $actionToLog = $oldStatus; // Log the phase that was just completed
            if ($idea->status === 'SPS Review' || $idea->status === 'Revision Requested') {
                $newStatus = 'Technical Review';
                $reviewerRole = 'technical_reviewer';
                $completion = 40;
            } elseif ($idea->status === 'Technical Review') {
                $newStatus = 'Managerial Review';
                $reviewerRole = 'managerial';
                $completion = 60;
            } elseif ($idea->status === 'Managerial Review') {
                $newStatus = 'Reward Processing';
                $reviewerRole = 'reward_processing';
                $completion = 80;
            } elseif ($idea->status === 'Reward Processing') {
                $newStatus = 'Implemented';
                $reviewerRole = null;
                $completion = 100;
            }
        }

        $idea->status = $newStatus;
        $idea->current_reviewer_role = $reviewerRole;
        $idea->completion_percentage = $completion;
        $idea->save();

        ReviewLog::create([
            'idea_id' => $idea->id,
            'reviewer_id' => Auth::id(),
            'action' => $actionToLog,
            'comments' => $validated['comments'] ?? '',
        ]);

        return redirect()->back();
    }

    /**
     * Intangible award lookup — piecewise linear between known anchor points.
     * Anchors: 10→300K, 15→1M, 20→2.5M, 25→5M, 30→8M, 35→12.5M,
     *          40→17.5M, 45→23M, 50→28.5M, 55→34M, 60→40M
     * Update the $anchors array once the full official table is confirmed.
     */
    private function intangibleAwardLookup(float $points): float
    {
        if ($points < 10) return 0;
        if ($points >= 60) return 40_000_000;

        $anchors = [
            [10, 300_000],
            [15, 1_000_000],
            [20, 2_500_000],
            [25, 5_000_000],
            [30, 8_000_000],
            [35, 12_500_000],
            [40, 17_500_000],
            [45, 23_000_000],
            [50, 28_500_000],
            [55, 34_000_000],
            [60, 40_000_000],
        ];

        for ($i = 0; $i < count($anchors) - 1; $i++) {
            [$p1, $r1] = $anchors[$i];
            [$p2, $r2] = $anchors[$i + 1];
            if ($points >= $p1 && $points < $p2) {
                $t = ($points - $p1) / ($p2 - $p1);
                return round($r1 + $t * ($r2 - $r1));
            }
        }

        return 40_000_000;
    }

    public function score(Request $request, Idea $idea)
    {
        $validated = $request->validate([
            'category' => 'required|in:tangible,intangible',
            'cost_savings' => 'nullable|numeric|min:0',
            'reward_percent' => 'nullable|numeric|min:0|max:100',
            'voucher_reward' => 'nullable|numeric|min:0',
            'appraisal' => 'nullable|string|max:255',
            'factor_a1' => 'required|integer|min:0|max:5',
            'factor_a2' => 'required|integer|min:0|max:5',
            'factor_a3' => 'required|integer|min:0|max:5',
            'factor_b' => 'required|numeric|min:0|max:10',
            'implementation_factor' => 'required|integer|min:0|max:5',
            'factor_c' => 'nullable|integer|min:0|max:25',
            'suggestion_factor' => 'nullable|numeric|min:0',
            'final_adjusted_reward' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        $aTotal = $validated['factor_a1'] + $validated['factor_a2'] + $validated['factor_a3'];
        $factorC = $validated['factor_c'] ?? 0;

        // Suggestion Factor:
        //   Tangible  → SF [%] = (A1+A2+A3) × B + Impl.Factor
        //   Intangible → SF [pts] = (A1+A2+A3) × B + Impl.Factor + C
        $suggestionFactor = ($aTotal * (float) $validated['factor_b']) + (int) $validated['implementation_factor'];
        if ($validated['category'] === 'intangible') {
            $suggestionFactor += $factorC;
        }
        $totalPoints = $suggestionFactor; // keep total_points aligned

        // Reward calculation
        $calculatedReward = 0;
        if ($validated['category'] === 'intangible') {
            $calculatedReward = $this->intangibleAwardLookup($suggestionFactor);
        } else {
            $costSavings  = (float) ($validated['cost_savings'] ?? 0);
            $rewardPercent = (float) ($validated['reward_percent'] ?? 0);
            // For tangible, reward_percent IS the suggestion factor (%)
            // We auto-fill it from the calculated SF if it was sent read-only
            $effectivePct = $rewardPercent > 0 ? $rewardPercent : $suggestionFactor;
            $calculatedReward = ($effectivePct / 100) * $costSavings;
        }

        $idea->score()->updateOrCreate(
            ['idea_id' => $idea->id],
            [
                'category' => $validated['category'],
                'cost_savings' => $validated['cost_savings'] ?? 0,
                'reward_percent' => $validated['reward_percent'] ?? 0,
                'voucher_reward' => $validated['voucher_reward'] ?? 0,
                'appraisal' => $validated['appraisal'],
                'factor_a1' => $validated['factor_a1'],
                'factor_a2' => $validated['factor_a2'],
                'factor_a3' => $validated['factor_a3'],
                'factor_b' => $validated['factor_b'],
                'implementation_factor' => $validated['implementation_factor'],
                'factor_c' => $validated['factor_c'] ?? 0,
                'suggestion_factor' => $suggestionFactor,
                'total_points' => $totalPoints,
                'calculated_reward' => $calculatedReward,
                'final_adjusted_reward' => $validated['final_adjusted_reward'] ?? null,
                'remark' => $validated['remark'],
            ]
        );

        return redirect()
            ->back()
            ->with('success', 'Scoring updated successfully.');
    }
    
}