<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves the IndexNow key, which is how a search engine checks that a
 * submission really came from this site. `seo:indexnow` points at this URL
 * as its keyLocation.
 */
class IndexNowKeyController extends Controller
{
    public function __invoke(): Response
    {
        $key = config('services.indexnow.key');

        abort_if(blank($key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
