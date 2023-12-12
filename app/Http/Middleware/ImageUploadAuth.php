<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Shop;

class ImageUploadAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $auth = $request->header('X-Cake-Auth');

        if (!$auth) {
            return response([
                'error' => "X-Cake-Auth header is not present"
            ], 401);
        }

        $date_str = date('Y-m-d');

        $shop_str = !empty($request->shop) ? $request->shop : "DEFAULT_SHOP";
        $shop = Shop::where('shop', $shop_str)->first();

        if (!$shop) {
            return response([
                'error' => "Invalid shop parameter \"$shop_str\""
            ], 400);
        }


        $request->attributes->set('shop', $shop);

        $calculated_hash = hash_hmac('sha256', "{$shop->shop}:{$date_str}", $shop->secret);

        if ($calculated_hash != $auth) {
            return response([
                'error' => "Invalid X-Cake-Auth header"
            ], 401);
        }

        return $next($request);
    }
}
