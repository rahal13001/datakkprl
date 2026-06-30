<?php

return [
    // High-volume session and activity tracking is intentionally opt-in.
    // Group visitor identification remains active when this is disabled.
    'detailed_tracking_enabled' => (bool) env('LEARNING_DETAILED_TRACKING_ENABLED', false),
];
