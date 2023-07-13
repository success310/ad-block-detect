<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePasteRequest;
use App\Models\Key;
use App\Models\Paste;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdService;
use App\Services\PasteService;
use App\Services\StatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasteController extends Controller
{
    private $pasteService, $statService, $adService;
    public function __construct(PasteService $pasteService)
    {
        $this->pasteService = $pasteService;
        $this->statService = new StatService();
        $this->adService = new AdService();
        $this->middleware('isBotRequest')->only(['getUserStats']);
        $this->middleware('auth:sanctum')->except(['view', 'preview', 'store', 'getUserStats', 'raw', 'unlock', 'createPaste']);;
        $this->middleware('optional.sanctum')->only(['store']);
        $this->middleware('api.key')->only(['createPaste']);
    }
    public function show(Request $request)
    {
        $pastes = $this->pasteService->getAll($request->limit, $request->search);
        $today = $this->statService->getTodayViews();
        $week = $this->statService->getWeekViews();
        $total = $this->statService->getTotalViews();
        return [
            'pastes' => $pastes,
            'user' => auth()->user(),
            'stats' => [
                'today' => $today,
                'week' => $week,
                'total' => $total
            ]
        ];
    }
    public function store(StorePasteRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $paste = $this->pasteService->create($request->slug, $validated['title'], $validated['isLinksClickable'], $validated['allowEmbedding'], $validated['allowRaw'], $validated['textContent'],  $validated['bgColor'], $validated['textColor'], $validated['boxColor'], $validated['detailsColor'], $validated['expiration'], $validated['timezone'], $validated['password'], $validated['videoEmbed'], $user);
        return $paste;
    }
    public function update(StorePasteRequest $request, Paste $paste)
    {

        if (!Gate::allows('update-paste', $paste))
            return response(['success' => false, 'message' => 'unauthorized',], 500);

        $validated = $request->validated();
        $slug = array_key_exists('slug', $validated) ? $validated['slug'] : null;
        $paste = $this->pasteService->update($paste, $validated['title'], $validated['isLinksClickable'], $validated['allowEmbedding'], $validated['allowRaw'], $validated['textContent'],  $validated['bgColor'], $validated['textColor'], $validated['boxColor'], $validated['detailsColor'], $validated['expiration'], $validated['timezone'], $slug, $validated['password'], $validated['videoEmbed']);
        return $paste;
    }
    public function delete(Paste $paste)
    {
        if (!Gate::allows('delete-paste', $paste))
            return response(['success' => false, 'message' => 'unauthorized'], 500);

        $paste->delete();
        return response([
            'success' => true,
        ]);
    }

    public function view($slug, Request $request)
    {
        $paste = $this->pasteService->findBySlug($slug);
        if (!$paste)
            return response(['message' => 'Paste not found', 'code' => 'paste_not_found'], 404);
        if ($paste->isLinkExpired())
            return response(['message' => 'Paste expired', 'code' => 'paste_expired'], 500);
        if ($paste->isProtected) {
            return response(['message' => 'Paste protected', 'code' => 'paste_protected'], 500);
        }

        if ($paste->user) {
            $paste->user->makeHidden([
                'isAdmin', 'email',
                'updated_at', 'created_at',
                'email_verified_at', 'earnings'
            ]);
        }
        $adlink = $this->adService->getUserAdlink($paste->user_id);
        $cookie = $this->pasteService->recordView($request, $paste);
        $response = response()->json([
            'paste' => $paste,
            'ad' => $adlink,
        ]);
        return $cookie ? $response->withCookie($cookie) : $response;
    }
    public function displayUserStats(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required',
            'period' => 'sometimes',
            'start' => 'sometimes|date',
            'end' => 'sometimes|date'
        ]);
        if (!Gate::allows('is-admin')) return response(['success' => false, 'message' => 'unauthorized'], 403);

        $user = User::where('username', $fields['username'])->first();

        if (!$user) return response(['success' => false, 'message' => 'user not found'], 404);

        if (!$request->period && !$request->start && !$request->end) {
            $today = $this->statService->getTodayViews($user);
            $week = $this->statService->getWeekViews($user);
            $total = $this->statService->getTotalViews($user);
            return [
                'user' => $user,
                'stats' => [
                    'today' => $today,
                    'week' => $week,
                    'total' => $total
                ]
            ];
        }
        $startDate = $request->start;
        $endDate = $request->end;
        $periods = [
            'today' => 0,
            'yesterday' => 1,
            'last_28_days' => 28,
        ];

        if ($request->period) {
            $startDate = Carbon::today()->subDays($periods[$request->period]);
            $endDate = now();
        }
        $total = $this->statService->getTotalViews($user, $startDate, $endDate);
        return [
            'user' => $user,
            'stats' => [
                'total' => $total
            ]
        ];
    }
    public function getUserStats(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required',
            'period' => 'sometimes',
            'start' => 'sometimes|date',
            'end' => 'sometimes|date'
        ]);

        $user = User::where('username', $fields['username'])->first();

        if (!$user) return response(['success' => false, 'message' => 'user not found'], 404);

        $user->makeHidden('earnings');

        if (!$request->period && !$request->start && !$request->end) {
            $today = $this->statService->getTodayViews($user);
            $week = $this->statService->getWeekViews($user);
            $total = $this->statService->getTotalViews($user);
            return [
                'user' => $user,
                'stats' => [
                    'today' => $today,
                    'week' => $week,
                    'total' => $total
                ]
            ];
        }
        $startDate = $request->start;
        $endDate = $request->end;
        $periods = [
            'today' => 0,
            'yesterday' => 1,
            'last_28_days' => 28,
        ];

        if ($request->period) {
            $startDate = Carbon::today()->subDays($periods[$request->period]);
            $endDate = now();
        }
        $total = $this->statService->getTotalViews($user, $startDate, $endDate);
        return [
            'user' => $user,
            'stats' => [
                'total' => $total
            ]
        ];
    }
    public function raw($slug, Request $request)
    {
        $paste = $this->pasteService->findBySlug($slug);
        $headers = ['Content-Type' => 'text/plain'];
        if (!$paste) {
            return new Response('Paste not found', Response::HTTP_NOT_FOUND, $headers);
        }

        if ($paste->isLinkExpired()) {
            return new Response('Paste expired', Response::HTTP_BAD_REQUEST, $headers);
        }
        $adlink = $this->adService->getUserAdlink($paste->user_id);
        $cookie = $this->pasteService->recordView($request, $paste);
        $headScript = Setting::where('key', 'headScript')->first();
        $bodyScript = Setting::where('key', 'bodyScript')->first();
        $response = response()
            ->view('raw', [
                'headScript' => $headScript ? $headScript->value : '',
                'bodyScript' => $bodyScript ? $bodyScript->value : '',
                'content' => $paste->content, 'adlink' => $adlink, 'title' => $paste->title === "" ? "Blank title" : $paste->title
            ])
            ->header('Content-Type', 'text/html');

        return $cookie ? $response->withCookie($cookie) : $response;
    }

    public function unlock($slug, Request $request)
    {
        $validated = $request->validate([
            'password' => 'required'
        ]);
        $paste = $this->pasteService->findBySlug($slug);
        if (!$paste) {
            return response()->json(['message' => 'Paste not found'], Response::HTTP_NOT_FOUND);
        }
        if (!Hash::check($validated['password'], $paste->password)) {
            return response()->json(['message' => 'Wrong password'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $adlink = $this->adService->getUserAdlink($paste->user_id);
        $cookie = $this->pasteService->recordView($request, $paste);
        $response = response()->json([
            'paste' => $paste,
            'ad' => $adlink,
        ]);
        return $cookie ? $response->withCookie($cookie) : $response;
    }
    public function createPaste(StorePasteRequest $request)
    {
        $validated = $request->validated();
        $apiKey = $request->header('API-Key');
        $key = Key::where('key', $apiKey)->first();
        $expiration = $validated['expiration'] ?? null;
        $title = $validated['title'] ?? '';
        $videoEmbed = $validated['videoEmbed'] ?? null;
        $password = $validated['password'] ?? null;
        $timezone = $validated['timezone'] ?? null;
        $paste = $this->pasteService->create($request->slug, $title, $validated['isLinksClickable'], $validated['allowEmbedding'], $validated['allowRaw'], $validated['textContent'],  $validated['bgColor'], $validated['textColor'], $validated['boxColor'], $validated['detailsColor'], $expiration, $timezone, $password,  $videoEmbed, $key->user);
        return $paste;
    }

    public function preview(Request $request)
    {
        $headers = ['Content-Type' => 'text/html'];
        try {
            // Get url from query string
            $base64EncodedUrl = $request->query('url');

            // Decode base64 url
            $url = base64_decode($base64EncodedUrl);

            // Check if the decoded string is a valid URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return new Response('Invalid URL', Response::HTTP_BAD_REQUEST, $headers);
            }

            // Initialize a new curl resource
            $ch = curl_init();

            // Set the url
            curl_setopt($ch, CURLOPT_URL, $url);

            // Return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // Set the encoding to handle different character sets
            curl_setopt($ch, CURLOPT_ENCODING, '');

            // $output contains the output string
            $output = curl_exec($ch);

            // Check if any error occurred
            if (curl_errno($ch)) {
                $errorMessage = 'Curl error: ' . curl_error($ch);
                curl_close($ch);
                return new Response($errorMessage, Response::HTTP_INTERNAL_SERVER_ERROR, $headers);
            }

            // Close curl resource to free up system resources
            curl_close($ch);

            // If no error, return the content
            return new Response($output, Response::HTTP_OK, $headers);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('An error occurred: ' . $e->getMessage());

            // Return a generic error response
            return response()->json(['error' => 'An error occurred. Please try again later.'], 500);
        }
    }
}
