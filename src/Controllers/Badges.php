<?php

namespace Koseu\Controllers;

use CourseBase;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Grades\GradeUtil;
use Tsugi\UI\LessonsOrchestrator;
use Tsugi\UI\LessonsUIHelper;
use Tsugi\Util\U;

class Badges
{

    const ROUTE = '/badges';

    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix, 'Badges@get');
        $app->router->get($prefix . '/', 'Badges@get');
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if (!isset($CFG->lessons)) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Set login redirect
        $path = U::rest_path();
        $_SESSION["login_return"] = $path->full;

        // Load all Lessons
        $referenceList = \Tsugi\UI\LessonsOrchestrator::getLessonsReference();

        /** @var CourseBase[] */
        $lessonsList = [];

        foreach ($referenceList as $key => $reference) {
            $l = \Tsugi\UI\LessonsOrchestrator::getLessons($key);
            array_push($lessonsList, $l);
        }

        // Load all the Grades so far
        $allgrades = array();
        if (isset($_SESSION['id']) && isset($_SESSION['context_id'])) {
            $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
            foreach ($rows as $row) {
                $allgrades[$row['resource_link_id']] = $row['grade'];
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot . '/../nav.php')) {
            include $CFG->dirroot . '/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $twig = LessonsUIHelper::twig();

        $allBadgeData = [];
        $allAwardedData = [];
        foreach ($lessonsList as $l) {
            $response = $l->getBadgeData($l);
            $allBadgeData[] = $response;
            $allAwardedData = array_merge($allAwardedData, $response->awarded);
        }
        $twig->addFunction(new \Twig\TwigFunction('getModuleByRlid', function ($course, $resource_link_id) {
            return LessonsOrchestrator::getModuleByRlid($course, $resource_link_id);
        }));
        $twig->addFunction(new \Twig\TwigFunction('getLtiByRlid', function ($course, $resource_link_id) {
            return LessonsOrchestrator::getLtiByRlid($course, $resource_link_id);
        }));

        // Render browse page
        echo $twig->render('badges.twig', [
            'allBadges' => $allBadgeData,
            'allAwarded' => $allAwardedData,
            'badgeUrl' => $CFG->badge_url,
            'allGrades' => $allgrades,
            'loggedIn' => isset($_SESSION['id']) && isset($_SESSION['context_id'])
        ]);
        $OUTPUT->footer();
    }
}
