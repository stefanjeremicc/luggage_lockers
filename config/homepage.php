<?php

/*
 * Content shown on the public home page that is edited in code, not in Settings.
 * Previously lived as a JSON blob in the `settings` table (how_it_works_steps),
 * which was clunky to manage. Now authored here.
 */

return [
    'how_it_works' => [
        [
            'icon' => 'computer',
            'title' => 'Book Online or Scan QR',
            'desc' => "Reserve your locker through our website. You'll receive a confirmation email with your locker number and access code.",
        ],
        [
            'icon' => 'search',
            'title' => 'Arrive & Find Your Locker',
            'desc' => "We're open 24/7 — come whenever it suits you. Locate the locker number from your confirmation email.",
        ],
        [
            'icon' => 'lock',
            'title' => 'Enter Your Code',
            'desc' => 'Use the keypad to enter your access code. The locker will unlock — store your luggage safely inside.',
        ],
        [
            'icon' => 'smile',
            'title' => 'Done!',
            'desc' => 'Close the door and enjoy your day — your belongings are safe and secure.',
        ],
    ],
];
