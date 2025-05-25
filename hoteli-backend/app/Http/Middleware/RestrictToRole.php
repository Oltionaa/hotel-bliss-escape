<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Kontrollo nëse përdoruesi është i autentifikuar
        if (!auth()->check()) {
            return abort(401, 'Unauthenticated.'); // Ose redirect('/login');
        }

        $user = auth()->user();

        // Kontrollo nëse përdoruesi ka ndonjë nga rolet e specifikuara
        foreach ($roles as $role) {
            // Kujdes: "role" duhet të jetë emri i kolonës/atributit që mban rolin e përdoruesit
            if ($user->role === $role) {
                return $next($request); // Lejo kërkesën
            }
        }

        // Nëse përdoruesi nuk ka asnjë nga rolet e lejuara
        return abort(403, 'You do not have permission to access this resource.');
    }
}