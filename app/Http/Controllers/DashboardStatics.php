<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Article;
use App\Models\Feedback;
use App\Models\Instructor;
use App\Models\Remedy;
use App\Models\RemedyType;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;


class DashboardStatics extends Controller
{
    public function index()
    {
        $users = User::count();
        $remedies = Remedy::count();
        $articles = Article::count();
        $videos = Video::count();
        $feedback = Feedback::count();
        $remedy_types = RemedyType::count();
        $instructors = Instructor::count();
        $admins = Admin::count();

        return response()->json([
            'users' => $users,
            'remedies' => $remedies,
            'articles' => $articles,
            'videos' => $videos,
            'feedback' => $feedback,
            'remedy_types' => $remedy_types,
            'instructors' => $instructors,
            'admins' => $admins,
        ]);
    }
}
