<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

// TWITCH ONLINE INDICATOR
if (!isset($_COOKIE['gol_announce_gol_twitch'])) // if they haven't dissmissed it
{
	$templating->load('twitch_bar');
	$templating->block('main', 'twitch_bar');
}

$steam_id = (int) $_GET['id'];

$app_info = $dbl->run("SELECT `name` FROM `calendar` WHERE `steam_id` = ?", array($steam_id))->fetch();

if (!$app_info['name'])
{
	$_SESSION['message'] = 'none_found';
	$_SESSION['message_extra'] = 'GOL APPs with that Steam ID';
	header('Location: /steamplay/');
	die();
}

$total_ratings = $dbl->run("SELECT COUNT(*) FROM proton_reports WHERE `steam_appid` = ?", array($steam_id))->fetchOne();

$templating->set_previous('title', 'Steam Play Proton reports for ' . $app_info['name'], 1);
$templating->set_previous('meta_description', 'Steam Play Proton gaming reports for ' . $app_info['name'], 1);

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message('goty', $_SESSION['message'], $extra);
}

$templating->load('steamplay_reports');
$templating->block('top', 'steamplay_reports');
$templating->set('steam_appid', $steam_id);
$templating->set('name', $app_info['name']);
$templating->set('total', $total_ratings);

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
    $report_button = '<span class="link_button"><a href="/steamplay_submit.php?steam_id='.$steam_id.'">&plus; Submit report</a></span>';
}
else
{
    $report_button = '<span class="link_button"><a href="/index.php?module=login">Login to add report</a></span>';
}
$templating->set('new_report', $report_button);

$templating->block('help_links', 'steamplay_reports');

$links_array = array();
$links_array[] = '<a href="https://store.steampowered.com/app/'.$steam_id.'" target="_blank"><img height="15" src="/templates/default/images/golsteamplay.svg" alt="" /> Steam</a>';
$links_array[] = '<a href="https://steamdb.info/app/'.$steam_id.'/" target="_blank"><img height="15" src="/templates/default/images/steamdb.svg" alt="" /> SteamDB</a>';
$links_array[] = '<a href="https://pcgamingwiki.com/api/appid.php?appid='.$steam_id.'" target="_blank"><img height="15" src="/templates/default/images/pcgamingwiki.svg" alt="" /> PCGamingWiki</a>';
$templating->set('helpful_links', implode(' ', $links_array));

if ($total_ratings > 0)
{
    $get_ratings = $dbl->run("SELECT `single_works`, COUNT(`single_works`) AS `single_total` FROM `proton_reports` WHERE `steam_appid` = ? GROUP BY `single_works` ORDER BY `report_id` DESC LIMIT 30", array($steam_id))->fetch_All();

    $get_ratings_multi = $dbl->run("SELECT `multi_works`, COUNT(`multi_works`) AS `multi_total` FROM `proton_reports` WHERE `steam_appid` = ? GROUP BY `multi_works` ORDER BY `report_id` DESC LIMIT 30", array($steam_id))->fetch_All();

    $total_ratings_single = 0;
    $total_single_broke = 0;
    $total_single_issues = 0;
    $total_single_working = 0;

    foreach ($get_ratings as $data)
    {
        if ($data['single_works'] == 0)
        {
            $total_single_broke = $data['single_total'];
        }
        if ($data['single_works'] == 1)
        {
            $total_single_working = $data['single_total'];
        }
        if ($data['single_works'] == 2)
        {
            $total_single_issues = $data['single_total'];
        }
    }

    $total_ratings_single = $total_single_working + $total_single_broke + $total_single_issues;

    $total_ratings_multi = 0;
    $total_multi_broke = 0;
    $total_multi_issues = 0;
    $total_multi_working = 0;
    foreach ($get_ratings_multi as $data)
    {
        if ($data['multi_works'] == 0)
        {
            $total_multi_broke = $data['multi_total'];
        }
        if ($data['multi_works'] == 1)
        {
            $total_multi_working = $data['multi_total'];
        }
        if ($data['multi_works'] == 2)
        {
            $total_multi_issues = $data['multi_total'];
        }
    }

    $total_ratings_multi = $total_multi_working + $total_multi_issues + $total_multi_broke;

    /* work out rating */
    if ($total_ratings <= 5)
    {
        $core->message("Not enough reports currently to make a rating. We need at least 5. Submit yours here.");
    }
    else
    {
        $templating->block('ratings_grouped');

        /* singleplayer */
        $percent_good = round(($total_single_working / $total_ratings_single) * 100);
        $percent_issues = round(($total_single_issues / $total_ratings_single) * 100);
        $percent_probablyok = round(($total_single_issues + $total_single_working) / $total_ratings_single * 100);
       
        $percent_bad = 100 - $percent_good;

        $singleplayer_badge = '';
        if ($percent_good >= 75)
        {
            $singleplayer_badge = '<span title="Over 75% report it works" class="steamplay-icon working flex-noshrink"><img height="40" src="/templates/default/images/thumbsup.svg" alt="" /></span>';
        }
        else if ($percent_probablyok >= 50)
        {
            $singleplayer_badge = '<span title="Over 50% report it works with issues" class="steamplay-icon hasissues flex-noshrink"><img height="40" src="/templates/default/images/infoicon.svg" alt="" /></span>';
        }
        else if ($percent_probablyok <= 50)
        {
            $singleplayer_badge = '<span title="Under 40% report it works" class="steamplay-icon broken flex-noshrink"><img height="40" src="/templates/default/images/brokenicon.svg" alt="" /></span>';
        }
        $templating->set('singleplayer_badge', $singleplayer_badge);

        $issues_info = '';
        if ($percent_issues > 0)
        {
            $issues_info = '<p><strong><u>'.$percent_probablyok.'%</u></strong> say it works when taking issues into account.</p>';
        }
        $templating->set('issues_info',$issues_info);
        $templating->set('percent_good', $percent_good);

        /* multiplayer */
        $percent_multi_good = round(($total_multi_working / $total_ratings_multi) * 100);
        $percent_multi_issues = round(($total_multi_issues / $total_ratings_multi) * 100);
        $percent_multi_probablyok = round(($total_multi_issues + $total_multi_working) / $total_ratings_multi * 100);

        $multiplayer_badge = '';
        if ($percent_multi_good >= 75)
        {
            $multiplayer_badge = '<span title="Over 75% report it works" class="steamplay-icon working flex-noshrink"><img height="40" src="/templates/default/images/thumbsup.svg" alt="" /></span>';
        }
        else if ($percent_multi_probablyok >= 50)
        {
            $multiplayer_badge = '<span title="Over 50% report it works with issues" class="steamplay-icon hasissues flex-noshrink"><img height="40" src="/templates/default/images/infoicon.svg" alt="" /></span>';
        }
        else if ($percent_multi_probablyok <= 50)
        {
            $multiplayer_badge = '<span title="Under 40% report it works" class="steamplay-icon broken flex-noshrink"><img height="40" src="/templates/default/images/brokenicon.svg" alt="" /></span>';
        }
        $templating->set('multiplayer_badge', $multiplayer_badge);

        $issues_info_multi = '';
        if ($percent_multi_issues > 0)
        {
            $issues_info_multi = '<p><strong><u>'.$percent_multi_probablyok.'%</u></strong> say it works when taking issues into account.</p>';
        }
        $templating->set('issues_info_multi',$issues_info_multi);
        $templating->set('percent_good_multi', $percent_multi_good);

        /* multiplayer */
        if ($total_ratings_multi > 0)
        {
            $percent_good_multi = round(($total_multi_working / $total_ratings_multi) * 100);
            $percent_bad_multi = 100 - $percent_good_multi;

            /*core::$user_chart_js .= "<script>var multioptions = {
                tooltips: {
                    enabled: true,
                    callbacks: {
                        title: function() {return '".$app_info['name']."'},
                        label: function(tooltipItems, data) 
                        {
                            return data.datasets[tooltipItems.datasetIndex].label + ' ' + tooltipItems.xLabel + '%';
                        }
                    }
                },
                scales: {xAxes: [{ticks: {beginAtZero:true}, scaleLabel: {display: true, labelString: 'Percentage of reports (latest 30)'}, stacked: true}], yAxes: [{stacked: true}]},
                legend:{
                    display: true
                }
            };
            
            var ctx_multi = document.getElementById('multiplayer');
            ctx_multi.height = 35;
            var multiplayer = new Chart(ctx_multi, {
                type: 'horizontalBar',
                data: {
                    datasets: [{
                        label: 'Reported Working',
                        data: [".$percent_good_multi."],
                        backgroundColor: '#FFD700'
                    },{
                        label: 'Has Issues',
                        data: [".$percent_bad_multi."],
                        backgroundColor: '#FF0000'
                    }]
                },
                options: multioptions,
            });</script>";

            $rating_multi = '<h6>Multiplayer</h6><canvas id="multiplayer"></canvas>';*/
        }

        /* reports over time */

        $reports_over_time = $dbl->run("SELECT `single_works`, `multi_works`, `report_date` FROM `proton_reports` ORDER BY `report_date` ASC LIMIT 30")->fetch_all();

        $dates = array();
        $single_works = array();
        $single_issues = array();
        $single_broken = array();
        foreach ($reports_over_time as $report)
        {
            $date = date('y-m-d', strtotime($report['report_date']));

            if (!in_array("'".$date."'", $dates))
            {
                $dates[] = "'".$date."'";
            }
            // works out of the box
            if ($report['single_works'] == 1)
            {
                if (isset($single_works[$date]))
                {
                    $single_works[$date] = $single_works[$date]+1;
                }
                else
                {
                    $single_works[$date] = 1;
                }

                if (!isset($single_issues[$date]))
                {
                    $single_issues[$date] = 0;
                }
                if (!isset($single_broken[$date]))
                {
                    $single_broken[$date] = 0;
                }
            }
            // works out of the box, with issues
            if ($report['single_works'] == 2)
            {
                if (isset($single_issues[$date]))
                {
                    $single_issues[$date] = $single_issues[$date]+1;
                }
                else
                {
                    $single_issues[$date] = 1;
                }

                if (!isset($single_works[$date]))
                {
                    $single_works[$date] = 0;
                }
                if (!isset($single_broken[$date]))
                {
                    $single_broken[$date] = 0;
                }
            }
            // doesn't work out of the box, broken
            if ($report['single_works'] == 0)
            {
                if (isset($single_issues[$date]))
                {
                    $single_broken[$date] = $single_broken[$date]+1;
                }
                else
                {
                    $single_broken[$date] = 1;
                }

                if (!isset($single_works[$date]))
                {
                    $single_works[$date] = 0;
                }
                if (!isset($single_issues[$date]))
                {
                    $single_issues[$date] = 0;
                }
            }
        }

        core::$user_chart_js .= "var rating_single_time_options = {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                mode: 'label',
                enabled: true,
                callbacks: {
                    title: function() {return '".$app_info['name']."'},
                    label: function(tooltipItems, data) 
                    {
                        var value = data.datasets[tooltipItems.datasetIndex].data[tooltipItems.index];
                        return tooltipItems.xLabel + ' ' + data.datasets[tooltipItems.datasetIndex].label + ' (' + value +  ')';
                    }
                }
            },
            scales: {xAxes: [{ticks: {beginAtZero:true}, scaleLabel: {display: true, labelString: 'Number of reports per day (latest 30)'}}], yAxes: [{ticks: {precision:0}, scaleLabel: {display: true, labelString: 'Number of reports'}}]},
            legend:{
                display: true
            }
        };
        
        var ctx_single = document.getElementById('rating_single_time');
        var singleplayer = new Chart(ctx_single, {
            type: 'line',
            data: {
                labels: [".implode(", ",$dates)."],
                datasets: [{
                    label: 'Works',
                    data: ['".implode("', '",$single_works)."'],
                    backgroundColor: 'rgba(0, 234, 61, 0.5)',
					borderColor: 'rgba(0, 234, 61, 0.7)',
                    fill: false,
                    borderWidth: 5
                },{
                    label: 'Has Issues',
                    data: ['".implode("', '",$single_issues)."'],
                    backgroundColor: 'rgba(30, 139, 195, 0.5)',
                    borderColor: 'rgba(30, 139, 195, 0.7)',
                    fill: false,
                    borderWidth: 5
                },{
                    label: 'Broken',
                    data: ['".implode("', '",$single_broken)."'],
                    backgroundColor: 'rgba(207, 0, 15, 0.5)',
                    borderColor: 'rgba(207, 0, 15, 0.7)',
                    fill: false,
                    borderWidth: 5
                }]
            },
            options: rating_single_time_options,
        });";

        $rating_single_time = '<div style="height: 400px;"><canvas id="rating_single_time"></canvas></div>';

        /* proton versions */
        $protonv_single = $dbl->run("SELECT r.`proton_id`, pv.version, COUNT(r.`proton_id`) AS `total` FROM `proton_reports` r JOIN `proton_versions` pv ON pv.id = r.proton_id WHERE r.`steam_appid` = ? AND r.`single_works` = 1 GROUP BY r.`proton_id` ORDER BY r.`report_date` DESC LIMIT 30", array($_GET['id']))->fetch_all();

        $protonv_multi = $dbl->run("SELECT r.`proton_id`, pv.version, COUNT(r.`proton_id`) AS `total` FROM `proton_reports` r JOIN `proton_versions` pv ON pv.id = r.proton_id WHERE r.`steam_appid` = ? AND r.`multi_works` = 1 GROUP BY r.`proton_id` ORDER BY r.`report_date` DESC LIMIT 30", array($_GET['id']))->fetch_all();

        $versions_single = array();
        $versions = array();
        foreach ($protonv_single as $single)
        {
            // singleplayer totals
            if (!isset($versions[$single['version']]))
            {
                $versions_single[$single['version']] = $single['total'];
            }

            // version names
            if (!in_array($single['version'], $versions))
            {
                $versions[] = $single['version'];
            }
        }
        $versions_multi = array();
        foreach ($protonv_multi as $multi)
        {
            // multiplayer totals
            if (!isset($versions[$multi['version']]))
            {
                $versions_multi[$multi['version']] = $multi['total'];
            }

            // version names
            if (!in_array($multi['version'], $versions))
            {
                $versions[] = $multi['version'];
            }
        }

        core::$user_chart_js .= "var proton_versions_options = {
            tooltips: {
                enabled: true,
                callbacks: {
                    title: function() {return '".$app_info['name']."'},
                    label: function(tooltipItems, data) 
                    {
                        return data.datasets[tooltipItems.datasetIndex].label + ' ' + tooltipItems.xLabel;
                    }
                }
            },
            scales: {xAxes: [{ticks: {beginAtZero:true}, scaleLabel: {display: true, labelString: 'Number of reports (latest 30)'}, stacked: true}], yAxes: [{stacked: true}]},
            legend:{
                display: true
            }
        };

        var ctx_ver = document.getElementById('versions');
        ctx_ver.height = 150;
        var multiplayer = new Chart(ctx_ver, {
            type: 'horizontalBar',
            data: {
                labels: ['".implode("', '",$versions)."'],
                datasets: [{
                    label: 'Singleplayer',
                    data: ['".implode("', '",$versions_single)."'],
                    backgroundColor: '#33A1A2'
                },{
                    label: 'Multiplayer',
                    data: ['".implode("', '",$versions_multi)."'],
                    backgroundColor: '#6A6081'
                }]
            },
            options: proton_versions_options,
        });";

        $versions = '<canvas id="versions"></canvas>';
        //$templating->set('rating_multi', $rating_multi);
        $templating->set('proton_versions', $versions);
        $templating->set('rating_single_time', $rating_single_time);

    }

    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0 || !isset($_SESSION['user_id']))
    {
        $user_session->login_form(core::current_page_url());
    }

    if ($user->check_group([6,9]) === false)
    {
        $templating->block('patreon_comments', 'articles_full');
    }

    /* comments */
    $templating->block('comments_top', 'steamplay_reports');
    $templating->set('new_report', $report_button);
    $gamedb->render_proton_comments($steam_id, $total_ratings);
}
else if ($total_ratings == 0)
{
    $core->message('We currently have no ratings for this.');
}

$templating->block('bottom', 'steamplay_reports');
$templating->set('new_report', $report_button);

include(APP_ROOT . '/includes/footer.php');
