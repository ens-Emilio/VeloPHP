<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Request;
use Core\Response;

class HomeController
{
    public function index(Request $request, Response $response): Response
    {
        return $response->html('Bem-vindo ao VeloPHP!');
    }

    public function about(Request $request, Response $response): Response
    {
        return $response->html('VeloPHP — Framework PHP customizado com arquitetura moderna e performática.');
    }
}
