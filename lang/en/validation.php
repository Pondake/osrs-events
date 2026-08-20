<?php

return [
    /*
     * Field names substituted into :attribute in validation messages.
     *
     * This is the one thing Laravel's flat JSON translations (lang/en.json)
     * can't carry — the :attribute replacement is looked up as
     * `validation.attributes.<field>` through the PHP loader, so a JSON key
     * of that name is never consulted. Without an entry here the message
     * humanizes the column name and reads "The osrs username field is
     * required", which is not what the field is called anywhere in the UI.
     *
     * Only override where the humanized column name is actually wrong; the
     * rest ("email", "password") already read correctly.
     */
    'attributes' => [
        'osrs_username' => 'OSRS username',
        'discord_username' => 'Discord username',
    ],
];
