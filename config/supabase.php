<?php
function get_supabase_config(): array
{
    return [
        'url' => getenv('SUPABASE_URL') ?: '',
        'anon_key' => getenv('SUPABASE_ANON_KEY') ?: '',
        'service_role_key' => getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ''
    ];
}

function has_supabase_config(): bool
{
    $cfg = get_supabase_config();
    return $cfg['url'] !== '' && $cfg['anon_key'] !== '' && $cfg['service_role_key'] !== '';
}
?>
