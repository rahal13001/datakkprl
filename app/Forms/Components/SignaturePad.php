<?php

namespace App\Forms\Components;

use App\Services\SignatureService;
use Filament\Forms\Components\Field;

/**
 * Custom Filament form field that renders an HTML5 Canvas signature pad.
 * Uses AlpineJS for interactivity. Outputs base64 PNG data.
 */
class SignaturePad extends Field
{
    protected string $view = 'forms.components.signature-pad';

    protected int $canvasWidth = 400;
    protected int $canvasHeight = 150;
    protected string $penColor = '#000000';
    protected float $penWidth = 2.0;
    protected string $backgroundColor = '#ffffff';

    protected function setUp(): void
    {
        parent::setUp();

        // When hydrating state for editing, convert stored file paths
        // back to base64 data URIs so the canvas can display them.
        $this->afterStateHydrated(function (SignaturePad $component, $state) {
            if (empty($state)) {
                return;
            }

            // If it's already a data URI, no conversion needed
            if (str_starts_with($state, 'data:image')) {
                return;
            }

            // It's an encrypted file path — decrypt to data URI
            $signatureService = app(SignatureService::class);
            $dataUri = $signatureService->retrieveAsDataUri($state);

            if ($dataUri) {
                $component->state($dataUri);
            }
        });
    }

    public function canvasWidth(int $width): static
    {
        $this->canvasWidth = $width;
        return $this;
    }

    public function canvasHeight(int $height): static
    {
        $this->canvasHeight = $height;
        return $this;
    }

    public function penColor(string $color): static
    {
        $this->penColor = $color;
        return $this;
    }

    public function penWidth(float $width): static
    {
        $this->penWidth = $width;
        return $this;
    }

    public function backgroundColor(string $color): static
    {
        $this->backgroundColor = $color;
        return $this;
    }

    public function getCanvasWidth(): int
    {
        return $this->canvasWidth;
    }

    public function getCanvasHeight(): int
    {
        return $this->canvasHeight;
    }

    public function getPenColor(): string
    {
        return $this->penColor;
    }

    public function getPenWidth(): float
    {
        return $this->penWidth;
    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }
}
