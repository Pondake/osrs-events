<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * First-run flow state. The flow itself is a client-side modal
 * (Components/OnboardingModal.vue) that reuses the existing board-create
 * endpoint for its one real action — this controller only records that the
 * user is through it, so it doesn't reappear on every page load.
 */
class OnboardingController extends Controller
{
    public function complete(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return back();
    }

    /** Re-open the flow on demand ("show me that tour again"). */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => null]);

        return back();
    }
}
