<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends BaseVerifier
{
  /**
  * The URIs that should be excluded from CSRF verification.
  *
  * @var array
  */
  protected $except = [
    //
  ];

  /**
  * Handle an incoming request: customized CSRF token handler
  *
  * @param \Illuminate\Http\Request $request
  * @param \Closure $next
  * @return mixed
  *
  * @throws \Illuminate\Session\TokenMismatchException
  */
  public function handle($request, \Closure $next)
  {
    // check CSRF on http GET ajax calls
    if ($request->ajax() && $request->method() == 'GET' && !$this->tokensMatch($request))
      throw new TokenMismatchException;
      
    // default CSRF check is on POST, PUT, DELETE
    return parent::handle($request, $next);
  }
}
