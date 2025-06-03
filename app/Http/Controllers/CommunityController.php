<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Community;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request)
    {

        $communities = Community::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $communities,
        ]);
    }
}
