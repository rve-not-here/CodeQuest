<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAnnouncementStoreRequest;
use App\Http\Requests\AdminAnnouncementUpdateRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Admin announcement management (US-807, §30.0–§33.0): the admin-owned
 * system-announcement surface — list, create, edit, publish, archive. The
 * controller stays thin; business logic (lifecycle guards, audit rows, the
 * first-publish notification fan-out) lives in AnnouncementService.
 *
 * Create always lands as a draft; publish and archive are the only lifecycle
 * transitions (POST state changes on the listing/editor). A refused
 * transition is flashed back as an error; the audit trail already carries the
 * failed row before the exception is caught (success/failed discipline shared
 * with US-704..US-708).
 */
class AdminAnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementService $announcements) {}

    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.announcements.index', [
            'role' => $user->role,
            'announcements' => $this->announcements->index(),
        ]);
    }

    public function create(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.announcements.create', ['role' => $user->role]);
    }

    public function store(AdminAnnouncementStoreRequest $request): RedirectResponse
    {
        $payload = $request->safe(['title', 'message', 'audience']);

        /** @var User $actor */
        $actor = auth()->user();

        $this->announcements->create($actor, $payload);

        return redirect()->route('admin.announcements')
            ->with('status', 'Announcement saved as draft.');
    }

    public function edit(Announcement $announcement): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.announcements.edit', [
            'role' => $user->role,
            'announcement' => $announcement,
        ]);
    }

    public function update(AdminAnnouncementUpdateRequest $request, Announcement $announcement): RedirectResponse
    {
        $payload = $request->safe(['title', 'message', 'audience']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->announcements->update($actor, $announcement, $payload);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.announcements')
            ->with('status', 'Announcement updated.');
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->announcements->publish($actor, $announcement);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.announcements')
            ->with('status', 'Announcement published and delivered to its audience.');
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->announcements->archive($actor, $announcement);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.announcements')
            ->with('status', 'Announcement archived.');
    }
}
