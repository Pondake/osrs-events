<?php
use App\Models\User;
foreach (['hans' => 'hans.lumby', 'aggie' => 'aggie_brews', 'duke' => 'the duke', 'gnome' => 'gnomeballer', 'bob' => 'bob_axes', 'wise' => 'wiseoldbanker', 'ned' => 'ned.ropes', 'juliet' => 'julz'] as $k => $n) {
    User::where('discord_username', 'demo_'.$k)->update(['nickname' => $n]);
}
echo "NICK\n";
