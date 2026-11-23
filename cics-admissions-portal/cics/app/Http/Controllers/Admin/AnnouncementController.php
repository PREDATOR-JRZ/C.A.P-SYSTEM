<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index', ['announcements' => Announcement::latest()->paginate(15)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required', 'max:150'], 'body' => ['required', 'max:5000']]);
        Announcement::create($data + ['user_id' => auth()->id()]);

        return back()->with('success', 'Announcement posted.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }
}
