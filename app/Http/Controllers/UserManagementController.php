<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    /**
     * Only SPS admins may manage users.
     */
    private function authorise(): void
    {
        if (Auth::user()?->role !== 'sps') {
            abort(403, 'Access denied.');
        }
    }

    public function index()
    {
        $this->authorise();

        $users = User::with('department')
            ->orderByRaw("FIELD(role, 'technical_reviewer', 'rewarding_reviewer', 'user') ASC")
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'email'           => $u->email,
                'role'            => $u->role,
                'department'      => $u->department?->name ?? '—',
                'entra_id'        => $u->entra_id,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $this->authorise();

        $validated = $request->validate([
            'role' => 'required|in:user,technical_reviewer,rewarding_reviewer',
        ]);

        // Prevent changing SPS/manager accounts through this endpoint
        if (in_array($user->role, ['sps', 'technical_manager', 'financial_manager'], true)) {
            return back()->withErrors(['role' => 'Cannot change the role of an admin or manager account.']);
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "Role for {$user->name} updated to {$validated['role']}.");
    }
}
