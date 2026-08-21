<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit log retention
    |--------------------------------------------------------------------------
    |
    | How many days an audit row is kept before `model:prune` deletes it (see
    | routes/console.php, which schedules that daily).
    |
    | These rows record admin actions against named users and deliberately
    | keep the name after the account is gone, so this is a privacy setting as
    | much as a housekeeping one — it is the answer to "how long do you keep
    | this?" in the privacy policy.
    |
    */

    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 90),

];
