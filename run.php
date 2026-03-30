<?php
use Illuminate\Support\Facades\Request;

\ = App\Models\User::first();
Auth::login(\);
\ = Request::create('/request-history', 'GET');
\->setUserResolver(function() use (\) { return \; });
\ = new App\Http\Controllers\EndUserJobRequestController();
\ = \->history(\);
dump(\->toResponse(\));
