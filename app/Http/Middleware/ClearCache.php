<?php

namespace App\Http\Middleware;

use Closure;

class ClearCache
{
  /**
  * Handle an incoming request.
  *
  * @param  \Illuminate\Http\Request  $request
  * @param  \Closure  $next
  * @return mixed
  */
  public function handle($request, Closure $next)
  {
    /**
     * reset Cache: prevents flashed Session variables (= toast_msg) not clearing on browsers' Back/Forward buttons
     */
    if (session()->has('toast_msg')) {
      header('Cache-Control: no-store, private, no-cache, must-revalidate');
    }
    /*
    header('Cache-Control: pre-check=0, post-check=0, max-age=0, max-stale = 0', false);
    header('Pragma: public');
    header('Expires: '.date('D, j M Y H:i:s T', strtotime('-1 hour'))); 
    header('Expires: 0', false); 
    header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: no-cache');
    */
    return $next($request);
  }
}
