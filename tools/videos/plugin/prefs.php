<?php
use App\Models\User;
$p = json_decode(trim(file_get_contents(__DIR__.'/prefs.txt')), true);
User::where('discord_username', 'demo_iron')->update(['display_preferences' => $p ? json_encode($p) : null]);
echo "PREFS\n";
