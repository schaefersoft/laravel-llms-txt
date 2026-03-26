<?php

declare(strict_types=1);

namespace SchaeferSoft\LaravelLlmsTxt\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxt;
use SchaeferSoft\LaravelLlmsTxt\LlmsTxtRegistry;

class LlmsTxtController extends Controller
{
    public function index(): Response
    {
        return $this->textResponse($this->resolve()->getCached('llms-txt'));
    }

    public function full(): Response
    {
        return $this->textResponse($this->resolve()->renderFull());
    }

    public function localizedIndex(string $locale): Response
    {
        app()->setLocale($locale);

        return $this->textResponse($this->resolve()->getCached("llms-txt.{$locale}"));
    }

    public function localizedFull(string $locale): Response
    {
        app()->setLocale($locale);

        return $this->textResponse($this->resolve()->renderFull());
    }

    protected function resolve(): LlmsTxt
    {
        $locale = app()->getLocale();

        if (LlmsTxtRegistry::hasLocale($locale)) {
            return LlmsTxtRegistry::resolve($locale);
        }

        if (app()->bound(LlmsTxt::class)) {
            return app(LlmsTxt::class);
        }

        return new LlmsTxt;
    }

    protected function textResponse(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
