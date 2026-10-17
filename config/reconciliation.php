<?php

return [
    // STAGING ONLY until all receipt and ledger paths are end-to-end tested.
    'bank_led' => env('HD_BANK_LED_RECONCILIATION', false),
];
