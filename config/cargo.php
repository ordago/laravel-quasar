<?php

return [

    /*
     * When enabled, Cargo will process the projections on a queue.
     */
    'queue' => true,

    /*
     * This queue will be used to generate derived and responsive images.
     * Leave empty to use the default queue.
     */
    'queue_name' => '',

    /*
     * When enabled, Cargo will delete the projections when the related model is also deleted.
     */
    // 'on_cascade_delete' => false,
];
