<?php

return [
    /*
     * Business hours used to validate new appointments.
     */
    'start_time' => '08:00',
    'end_time' => '18:00',

    /*
     * ISO weekday numbers: 1 = Monday ... 7 = Sunday.
     */
    'weekdays' => [1, 2, 3, 4, 5],

    'slot_minutes' => 30,
    'min_duration_minutes' => 15,
    'max_duration_minutes' => 240,
];
