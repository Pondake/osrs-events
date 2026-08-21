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
        // "guild" is Discord's own API word for a server; nothing in this
        // app's UI has ever used it, and neither does Discord's. A message
        // reading "The required guild id field is required when access mode
        // is GUILD" describes a database column, not the thing the user
        // just failed to pick.
        'required_guild_id' => 'Discord server',
        'guild_id' => 'Discord server',
        'bingo_size' => 'card size',
        'start_date' => 'start date',
        'end_date' => 'end date',
        'access_mode' => 'who can join',
        'team_ids' => 'teams',
        'author_ids' => 'editors',
        'dice_roll_limit' => 'dice roll limit',
        'win_condition' => 'win condition',
        'line_bonus' => 'line bonus',
        'requires_approval' => 'host approval',
        'is_listed' => 'public listing',
        'site_lock_password' => 'shared password',
        'default_event_duration_days' => 'default event length',
    ],

    /**
     * Per-field messages, for the cases where even a corrected :attribute
     * still leaves the sentence quoting an enum value at the reader —
     * "...when access mode is GUILD" is the database's word, not a phrase
     * anybody typed.
     */
    'custom' => [
        'required_guild_id' => [
            'required_if' => 'Pick which Discord server can join this event.',
        ],
        'end_date' => [
            'required_with' => 'An event with a start date needs an end date too.',
            'after_or_equal' => 'The end date cannot be before the start date.',
        ],
    ],
];
