<?php

namespace App\Livewire;

use App\Services\ContentDeliveryService;
use Livewire\Component;

class LandingPage extends Component
{
    public $faqs;
    public $regulations;

    public function mount(ContentDeliveryService $contentService)
    {
        $this->faqs = $contentService->getFaqs();
        $this->regulations = $contentService->getRegulations();
    }

    public function render()
    {
        return view('livewire.landing-page');
    }
}
