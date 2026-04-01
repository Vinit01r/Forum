<?php
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function time_ago($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    if ($diff < 2592000) return floor($diff / 86400) . "d ago";
    return date("M j, Y", $time);
}

function get_avatar_initials($name) {
    preg_match_all('/(?<=\s|^)[a-z]/i', $name, $matches);
    if(empty($matches[0])) {
        return strtoupper(substr($name, 0, 1));
    }
    $initials = strtoupper(implode('', $matches[0]));
    return substr($initials, 0, 2);
}

function paginate($total, $per_page, $current_page, $url_params = '') {
    $pages = ceil($total / $per_page);
    if ($pages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    if ($current_page > 1) {
        $html .= '<a href="?page=' . ($current_page - 1) . $url_params . '">&laquo; Prev</a>';
    }
    
    for ($i = 1; $i <= $pages; $i++) {
        $active = ($i == $current_page) ? 'class="active"' : '';
        $html .= '<a href="?page=' . $i . $url_params . '" ' . $active . '>' . $i . '</a>';
    }
    
    if ($current_page < $pages) {
        $html .= '<a href="?page=' . ($current_page + 1) . $url_params . '">Next &raquo;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

function generate_slug($string) {
    $slug = preg_replace('/[^a-zA-Z0-9-]/', '-', strtolower($string));
    return preg_replace('/-+/', '-', $slug);
}

function get_reputation_badge($score) {
    if ($score < 10) return '<span class="badge badge-secondary">Newbie</span>';
    if ($score < 50) return '<span class="badge badge-primary">Member</span>';
    return '<span class="badge badge-success">Senior</span>';
}
?>
